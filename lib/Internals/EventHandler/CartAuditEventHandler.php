<?php
declare(strict_types=1);

namespace Vendor\Cart\Internals\EventHandler;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Vendor\Cart\Application\Service\CartAuditService;
use Vendor\Cart\Infrastructure\Logger\CartLoggerInterface;
use Vendor\Cart\Model\CartAuditTable;
use Vendor\Cart\Public\Event\Cart\CartItemAddedEvent;
use Vendor\Cart\Public\Event\Cart\CartItemRemovedEvent;

final class CartAuditEventHandler
{
    public static function onItemAdded(Event $event): EventResult
    {
        if (!$event instanceof CartItemAddedEvent)
        {
            return new EventResult(EventResult::UNDEFINED);
        }

        return self::record($event, CartAuditTable::ACTION_ADD);
    }

    public static function onItemRemoved(Event $event): EventResult
    {
        if (!$event instanceof CartItemRemovedEvent)
        {
            return new EventResult(EventResult::UNDEFINED);
        }

        return self::record($event, CartAuditTable::ACTION_REMOVE);
    }

    private static function record(
        CartItemAddedEvent|CartItemRemovedEvent $event,
        string $action,
    ): EventResult {
        try
        {
            /** @var CartAuditService $auditService */
            $auditService = ServiceLocator::getInstance()->get(CartAuditService::class);
            $result = $auditService->record($event, $action);

            return new EventResult(
                $result->isSuccess() ? EventResult::SUCCESS : EventResult::ERROR,
                $result->getErrorMessages(),
            );
        }
        catch (\Throwable $exception)
        {
            try
            {
                /** @var CartLoggerInterface $logger */
                $logger = ServiceLocator::getInstance()->get(CartLoggerInterface::class);
                $logger->error(
                    'Cart audit handler failed for event {eventId}: {exception}',
                    ['eventId' => $event->eventId, 'exception' => $exception],
                );
            }
            catch (\Throwable)
            {
            }

            return new EventResult(EventResult::ERROR, ['Не удалось записать аудит корзины.']);
        }
    }
}
