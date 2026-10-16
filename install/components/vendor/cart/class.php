<?php
declare(strict_types=1);

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Vendor\Cart\Application\Service\CartService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

final class VendorCartComponent extends CBitrixComponent
{
    public function executeComponent(): void
    {
        $this->arResult = ['ITEMS' => [], 'ERRORS' => []];

        if (!Loader::includeModule('vendor.cart'))
        {
            $this->arResult['ERRORS'][] = 'Модуль vendor.cart не установлен.';
            $this->includeComponentTemplate();
            return;
        }

        /** @var CartService $cartService */
        $cartService = ServiceLocator::getInstance()->get(CartService::class);
        $result = $cartService->list((int)CurrentUser::get()->getId());

        if ($result->isSuccess())
        {
            $this->arResult['ITEMS'] = $result->getData()['items'] ?? [];
        }
        else
        {
            $this->arResult['ERRORS'] = $result->getErrorMessages();
        }

        Extension::load('vendor.cart');
        $this->includeComponentTemplate();
    }
}
