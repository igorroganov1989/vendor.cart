<?php
declare(strict_types=1);

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_CART_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('VENDOR_CART_COMPONENT_DESCRIPTION'),
    'PATH' => [
        'ID' => 'e-store',
        'CHILD' => [
            'ID' => 'vendor',
            'NAME' => 'Vendor',
        ],
    ],
    'CACHE_PATH' => 'N',
    'COMPLEX' => 'N',
];
