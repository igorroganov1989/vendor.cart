<?php
declare(strict_types=1);

namespace Vendor\Cart\Infrastructure\Logger;

use Bitrix\Main\Application;
use Bitrix\Main\Diag\FileLogger;

final class CartFileLogger extends FileLogger implements CartLoggerInterface
{
    public function __construct()
    {
        parent::__construct(
            Application::getDocumentRoot() . '/local/var/log/vendor.cart.log',
            10 * 1024 * 1024,
        );
    }
}
