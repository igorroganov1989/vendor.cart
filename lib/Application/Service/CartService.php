<?php
declare(strict_types=1);

namespace Vendor\Cart\Application\Service;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Vendor\Cart\Infrastructure\Logger\CartLoggerInterface;
use Vendor\Cart\Model\CartItemTable;
use Vendor\Cart\Model\CartTable;
use Vendor\Cart\Public\Event\Cart\CartItemAddedEvent;
use Vendor\Cart\Public\Event\Cart\CartItemRemovedEvent;

final class CartService
{
    private const MAX_ADD_QUANTITY = 10000;
    private const MAX_ITEM_QUANTITY = 999999;

    public function __construct(
        private readonly CartIdentityService $identityService,
        private readonly CartLoggerInterface $logger,
    ) {
    }

    public function list(int $currentUserId): Result
    {
        $result = new Result();
        if ($currentUserId < 0)
        {
            return $this->error($result, 'Некорректный пользователь.', 'CART_INVALID_USER');
        }

        try
        {
            $owner = $this->identityService->resolve($currentUserId);
            $cart = $this->findOwnedCart($owner);
            if ($cart === null)
            {
                return $result->setData(['cartId' => null, 'items' => []]);
            }

            $rows = CartItemTable::query()
                ->setSelect(['ID', 'PRODUCT_ID', 'QUANTITY'])
                ->where('CART_ID', (int)$cart['ID'])
                ->setOrder(['ID' => 'ASC'])
                ->fetchAll();

            $items = array_map(
                static fn (array $row): array => [
                    'id' => (int)$row['ID'],
                    'productId' => (int)$row['PRODUCT_ID'],
                    'quantity' => (int)$row['QUANTITY'],
                ],
                $rows,
            );

            return $result->setData(['cartId' => (int)$cart['ID'], 'items' => $items]);
        }
        catch (\Throwable $exception)
        {
            $this->logger->error('Cart list failed: {exception}', ['exception' => $exception]);
            return $this->error($result, 'Не удалось получить корзину.', 'CART_LIST_FAILED');
        }
    }

    public function add(int $currentUserId, int $productId, int $quantity): Result
    {
        $result = new Result();
        if ($currentUserId < 0)
        {
            return $this->error($result, 'Некорректный пользователь.', 'CART_INVALID_USER');
        }
        if ($productId <= 0)
        {
            return $this->error($result, 'Некорректный товар.', 'CART_INVALID_PRODUCT');
        }
        if ($quantity <= 0 || $quantity > self::MAX_ADD_QUANTITY)
        {
            return $this->error($result, 'Некорректное количество.', 'CART_INVALID_QUANTITY');
        }

        $connection = Application::getConnection();
        $event = null;

        try
        {
            $owner = $this->identityService->resolve($currentUserId);
            $connection->startTransaction();

            $cart = $this->findOwnedCart($owner);
            if ($cart === null)
            {
                $cartAdd = CartTable::add([
                    'USER_ID' => $owner['userId'],
                    'GUEST_KEY' => $owner['guestKey'],
                ]);
                if (!$cartAdd->isSuccess())
                {
                    $connection->rollbackTransaction();
                    $result->addErrors($cartAdd->getErrors());
                    return $result;
                }
                $cartId = (int)$cartAdd->getId();
            }
            else
            {
                $cartId = (int)$cart['ID'];
            }

            $item = CartItemTable::query()
                ->setSelect(['ID', 'QUANTITY'])
                ->where('CART_ID', $cartId)
                ->where('PRODUCT_ID', $productId)
                ->setLimit(1)
                ->fetch();

            $quantityBefore = $item === false ? 0 : (int)$item['QUANTITY'];
            $quantityAfter = $quantityBefore + $quantity;
            if ($quantityAfter > self::MAX_ITEM_QUANTITY)
            {
                $connection->rollbackTransaction();
                return $this->error($result, 'Превышено допустимое количество.', 'CART_QUANTITY_LIMIT');
            }

            if ($item === false)
            {
                $write = CartItemTable::add([
                    'CART_ID' => $cartId,
                    'PRODUCT_ID' => $productId,
                    'QUANTITY' => $quantityAfter,
                ]);
                $itemId = (int)$write->getId();
            }
            else
            {
                $itemId = (int)$item['ID'];
                $write = CartItemTable::update($itemId, [
                    'QUANTITY' => $quantityAfter,
                    'UPDATED_AT' => new DateTime(),
                ]);
            }

            if (!$write->isSuccess())
            {
                $connection->rollbackTransaction();
                $result->addErrors($write->getErrors());
                return $result;
            }

            $cartUpdate = CartTable::update($cartId, ['UPDATED_AT' => new DateTime()]);
            if (!$cartUpdate->isSuccess())
            {
                $connection->rollbackTransaction();
                $result->addErrors($cartUpdate->getErrors());
                return $result;
            }
            $event = new CartItemAddedEvent(
                $this->id(),
                $this->id(),
                $owner['userId'],
                $cartId,
                $itemId,
                $productId,
                $quantityBefore,
                $quantityAfter,
            );
            $connection->commitTransaction();
            $result->setData([
                'cartId' => $cartId,
                'item' => ['id' => $itemId, 'productId' => $productId, 'quantity' => $quantityAfter],
            ]);
        }
        catch (\Throwable $exception)
        {
            $this->rollback($connection);
            $this->logger->error('Cart add failed for product {productId}: {exception}', [
                'productId' => $productId,
                'exception' => $exception,
            ]);
            return $this->error($result, 'Не удалось добавить товар.', 'CART_ADD_FAILED');
        }

        $this->sendAfterCommit($event);
        return $result;
    }

    public function remove(int $currentUserId, int $itemId): Result
    {
        $result = new Result();
        if ($currentUserId < 0)
        {
            return $this->error($result, 'Некорректный пользователь.', 'CART_INVALID_USER');
        }
        if ($itemId <= 0)
        {
            return $this->error($result, 'Некорректная позиция.', 'CART_INVALID_ITEM');
        }

        $connection = Application::getConnection();
        $event = null;

        try
        {
            $owner = $this->identityService->resolve($currentUserId);
            $connection->startTransaction();
            $cart = $this->findOwnedCart($owner);
            if ($cart === null)
            {
                $connection->rollbackTransaction();
                return $this->error($result, 'Позиция не найдена.', 'CART_ITEM_NOT_FOUND');
            }

            $cartId = (int)$cart['ID'];
            $item = CartItemTable::query()
                ->setSelect(['ID', 'PRODUCT_ID', 'QUANTITY'])
                ->where('ID', $itemId)
                ->where('CART_ID', $cartId)
                ->setLimit(1)
                ->fetch();
            if ($item === false)
            {
                $connection->rollbackTransaction();
                return $this->error($result, 'Позиция не найдена.', 'CART_ITEM_NOT_FOUND');
            }

            $delete = CartItemTable::delete($itemId);
            if (!$delete->isSuccess())
            {
                $connection->rollbackTransaction();
                $result->addErrors($delete->getErrors());
                return $result;
            }

            $cartUpdate = CartTable::update($cartId, ['UPDATED_AT' => new DateTime()]);
            if (!$cartUpdate->isSuccess())
            {
                $connection->rollbackTransaction();
                $result->addErrors($cartUpdate->getErrors());
                return $result;
            }
            $event = new CartItemRemovedEvent(
                $this->id(),
                $this->id(),
                $owner['userId'],
                $cartId,
                $itemId,
                (int)$item['PRODUCT_ID'],
                (int)$item['QUANTITY'],
                0,
            );
            $connection->commitTransaction();
            $result->setData(['cartId' => $cartId, 'removedItemId' => $itemId]);
        }
        catch (\Throwable $exception)
        {
            $this->rollback($connection);
            $this->logger->error('Cart remove failed for item {itemId}: {exception}', [
                'itemId' => $itemId,
                'exception' => $exception,
            ]);
            return $this->error($result, 'Не удалось удалить позицию.', 'CART_REMOVE_FAILED');
        }

        $this->sendAfterCommit($event);
        return $result;
    }

    /**
     * @param array{userId: int|null, guestKey: string|null} $owner
     * @return array<string, mixed>|null
     */
    private function findOwnedCart(array $owner): ?array
    {
        $query = CartTable::query()
            ->setSelect(['ID', 'USER_ID', 'GUEST_KEY'])
            ->setLimit(1);

        if ($owner['userId'] !== null)
        {
            $query->where('USER_ID', $owner['userId']);
        }
        else
        {
            $query->where('GUEST_KEY', (string)$owner['guestKey']);
        }

        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    private function sendAfterCommit(CartItemAddedEvent|CartItemRemovedEvent|null $event): void
    {
        if ($event === null)
        {
            return;
        }

        try
        {
            $event->send();
        }
        catch (\Throwable $exception)
        {
            $this->logger->error('Post-commit cart audit event failed: {exception}', [
                'eventId' => $event->eventId,
                'exception' => $exception,
            ]);
        }
    }

    private function rollback(Connection $connection): void
    {
        try
        {
            $connection->rollbackTransaction();
        }
        catch (\Throwable)
        {
        }
    }

    private function error(Result $result, string $message, string $code): Result
    {
        $result->addError(new Error($message, $code));
        return $result;
    }

    private function id(): string
    {
        return bin2hex(random_bytes(16));
    }
}
