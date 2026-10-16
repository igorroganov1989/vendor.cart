<?php
declare(strict_types=1);

namespace Vendor\Cart\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Vendor\Cart\Application\Service\CartService;

final class Cart extends Controller
{
    #[HttpMethod([HttpMethod::METHOD_GET])]
    #[DisablePrefilters([ActionFilter\Authentication::class])]
    public function listAction(CartService $cartService, CurrentUser $currentUser): array
    {
        return $this->respond($cartService->list((int)$currentUser->getId()));
    }

    #[HttpMethod([HttpMethod::METHOD_POST])]
    #[DisablePrefilters([ActionFilter\Authentication::class])]
    public function addAction(
        int $productId,
        int $quantity,
        CartService $cartService,
        CurrentUser $currentUser,
    ): array {
        return $this->respond(
            $cartService->add((int)$currentUser->getId(), $productId, $quantity),
        );
    }

    #[HttpMethod([HttpMethod::METHOD_POST])]
    #[DisablePrefilters([ActionFilter\Authentication::class])]
    public function removeAction(
        int $itemId,
        CartService $cartService,
        CurrentUser $currentUser,
    ): array {
        return $this->respond(
            $cartService->remove((int)$currentUser->getId(), $itemId),
        );
    }

    private function respond(\Bitrix\Main\Result $result): array
    {
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return [];
        }

        return $result->getData();
    }
}
