<?php
declare(strict_types=1);

namespace Vendor\Cart\Public\Event\Cart;

use Bitrix\Main\Event;

final class CartItemAddedEvent extends Event
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $requestId,
        public readonly ?int $userId,
        public readonly int $cartId,
        public readonly int $itemId,
        public readonly int $productId,
        public readonly int $quantityBefore,
        public readonly int $quantityAfter,
    ) {
        parent::__construct('vendor.cart', self::class, [
            'eventId' => $eventId,
            'requestId' => $requestId,
            'userId' => $userId,
            'cartId' => $cartId,
            'itemId' => $itemId,
            'productId' => $productId,
            'quantityBefore' => $quantityBefore,
            'quantityAfter' => $quantityAfter,
        ]);
    }
}
