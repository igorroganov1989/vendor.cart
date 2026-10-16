<?php
declare(strict_types=1);

use Vendor\Cart\Application\Service\CartAuditService;
use Vendor\Cart\Application\Service\CartIdentityService;
use Vendor\Cart\Application\Service\CartService;
use Vendor\Cart\Infrastructure\Logger\CartFileLogger;
use Vendor\Cart\Infrastructure\Logger\CartLoggerInterface;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Cart\\Infrastructure\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            CartLoggerInterface::class => [
                'className' => CartFileLogger::class,
            ],
            CartIdentityService::class => [
                'className' => CartIdentityService::class,
            ],
            CartAuditService::class => [
                'className' => CartAuditService::class,
            ],
            CartService::class => [
                'className' => CartService::class,
            ],
        ],
        'readonly' => true,
    ],
];
