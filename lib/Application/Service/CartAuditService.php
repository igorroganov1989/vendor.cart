<?php
declare(strict_types=1);

namespace Vendor\Cart\Application\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Vendor\Cart\Infrastructure\Logger\CartLoggerInterface;
use Vendor\Cart\Model\CartAuditTable;
use Vendor\Cart\Public\Event\Cart\CartItemAddedEvent;
use Vendor\Cart\Public\Event\Cart\CartItemRemovedEvent;

final class CartAuditService
{
    public function __construct(
        private readonly CartLoggerInterface $logger,
    ) {
    }

    public function record(
        CartItemAddedEvent|CartItemRemovedEvent $event,
        string $action,
    ): Result {
        $result = new Result();

        try
        {
            $existing = CartAuditTable::query()
                ->setSelect(['ID'])
                ->where('EVENT_ID', $event->eventId)
                ->setLimit(1)
                ->fetch();
            if ($existing !== false)
            {
                return $result;
            }

            $addResult = CartAuditTable::add([
                'ACTION' => $action,
                'EVENT_ID' => $event->eventId,
                'REQUEST_ID' => $event->requestId,
                'USER_ID' => $event->userId,
                'CART_ID' => $event->cartId,
                'ITEM_ID' => $event->itemId,
                'PRODUCT_ID' => $event->productId,
                'QUANTITY_BEFORE' => $event->quantityBefore,
                'QUANTITY_AFTER' => $event->quantityAfter,
            ]);

            if (!$addResult->isSuccess())
            {
                $this->logger->error('Cart audit write failed for event {eventId}', [
                    'eventId' => $event->eventId,
                    'errors' => $addResult->getErrorMessages(),
                ]);
                $result->addErrors($addResult->getErrors());
            }
        }
        catch (\Throwable $exception)
        {
            $this->logger->error('Cart audit exception for event {eventId}: {exception}', [
                'eventId' => $event->eventId,
                'exception' => $exception,
            ]);
            $result->addError(new Error('Не удалось записать аудит корзины.', 'CART_AUDIT_WRITE_FAILED'));
        }

        return $result;
    }
}
