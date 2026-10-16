<?php
declare(strict_types=1);

namespace Vendor\Cart\Application\Service;

use Bitrix\Main\Application;

final class CartIdentityService
{
    private const SESSION_KEY = 'vendor.cart.guest_key';

    /** @return array{userId: int|null, guestKey: string|null} */
    public function resolve(int $currentUserId): array
    {
        if ($currentUserId > 0)
        {
            return ['userId' => $currentUserId, 'guestKey' => null];
        }

        $session = Application::getInstance()->getSession();
        $guestKey = (string)$session->get(self::SESSION_KEY);

        if (!preg_match('/^[a-f0-9]{64}$/D', $guestKey))
        {
            $guestKey = bin2hex(random_bytes(32));
            $session->set(self::SESSION_KEY, $guestKey);
        }

        return ['userId' => null, 'guestKey' => $guestKey];
    }
}
