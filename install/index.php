<?php
declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Vendor\Cart\Internals\EventHandler\CartAuditEventHandler;
use Vendor\Cart\Model\CartAuditTable;
use Vendor\Cart\Model\CartItemTable;
use Vendor\Cart\Model\CartTable;
use Vendor\Cart\Public\Event\Cart\CartItemAddedEvent;
use Vendor\Cart\Public\Event\Cart\CartItemRemovedEvent;

Loc::loadMessages(__FILE__);

final class vendor_cart extends CModule
{
    public $MODULE_ID = 'vendor.cart';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI = '';

    public function __construct()
    {
        $arModuleVersion = [];
        require __DIR__ . '/version.php';

        $this->MODULE_VERSION = (string)($arModuleVersion['VERSION'] ?? '');
        $this->MODULE_VERSION_DATE = (string)($arModuleVersion['VERSION_DATE'] ?? '');
        $this->MODULE_NAME = (string)Loc::getMessage('VENDOR_CART_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('VENDOR_CART_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = (string)Loc::getMessage('VENDOR_CART_PARTNER_NAME');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            $APPLICATION->ThrowException((string)Loc::getMessage('VENDOR_CART_INSTALL_DENIED'));
            return;
        }

        if (!ModuleManager::isModuleInstalled($this->MODULE_ID))
        {
            ModuleManager::registerModule($this->MODULE_ID);
        }

        Loader::includeModule($this->MODULE_ID);
        $this->installDb();
        $this->installEvents();
        $this->installFiles();
    }

    public function DoUninstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            $APPLICATION->ThrowException((string)Loc::getMessage('VENDOR_CART_UNINSTALL_DENIED'));
            return;
        }

        Loader::includeModule($this->MODULE_ID);
        $this->uninstallEvents();
        $this->uninstallDb();
        $this->uninstallFiles();

        if (ModuleManager::isModuleInstalled($this->MODULE_ID))
        {
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }

    private function installDb(): void
    {
        $connection = Application::getConnection();

        foreach ([CartTable::class, CartItemTable::class, CartAuditTable::class] as $tableClass)
        {
            if (!$connection->isTableExists($tableClass::getTableName()))
            {
                $tableClass::getEntity()->createDbTable();
            }
        }
    }

    private function uninstallDb(): void
    {
        $connection = Application::getConnection();

        foreach ([CartAuditTable::class, CartItemTable::class, CartTable::class] as $tableClass)
        {
            if ($connection->isTableExists($tableClass::getTableName()))
            {
                $connection->dropTable($tableClass::getTableName());
            }
        }
    }

    private function installEvents(): void
    {
        $manager = EventManager::getInstance();

        foreach ($this->eventMap() as [$eventType, $method])
        {
            $manager->unRegisterEventHandler(
                $this->MODULE_ID,
                $eventType,
                $this->MODULE_ID,
                CartAuditEventHandler::class,
                $method,
            );
            $manager->registerEventHandler(
                $this->MODULE_ID,
                $eventType,
                $this->MODULE_ID,
                CartAuditEventHandler::class,
                $method,
            );
        }
    }

    private function uninstallEvents(): void
    {
        $manager = EventManager::getInstance();

        foreach ($this->eventMap() as [$eventType, $method])
        {
            $manager->unRegisterEventHandler(
                $this->MODULE_ID,
                $eventType,
                $this->MODULE_ID,
                CartAuditEventHandler::class,
                $method,
            );
        }
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function eventMap(): array
    {
        return [
            [CartItemAddedEvent::class, 'onItemAdded'],
            [CartItemRemovedEvent::class, 'onItemRemoved'],
        ];
    }

    private function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/components/vendor/cart',
            Application::getDocumentRoot() . '/local/components/vendor/cart',
            true,
            true,
        );
        CopyDirFiles(
            __DIR__ . '/js/vendor/cart',
            Application::getDocumentRoot() . '/local/js/vendor/cart',
            true,
            true,
        );

        $logDirectory = Application::getDocumentRoot() . '/local/var/log';
        if (!is_dir($logDirectory))
        {
            mkdir($logDirectory, 0750, true);
        }
        $accessFile = $logDirectory . '/.htaccess';
        if (!is_file($accessFile))
        {
            file_put_contents(
                $accessFile,
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n",
            );
        }
    }

    private function uninstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor/cart');
        DeleteDirFilesEx('/local/js/vendor/cart');
    }
}
