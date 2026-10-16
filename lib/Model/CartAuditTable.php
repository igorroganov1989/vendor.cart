<?php
declare(strict_types=1);

namespace Vendor\Cart\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

final class CartAuditTable extends DataManager
{
    public const ACTION_ADD = 'ADD';
    public const ACTION_REMOVE = 'REMOVE';

    public static function getTableName(): string
    {
        return 'vendor_cart_audit';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\EnumField('ACTION'))
                ->configureRequired()
                ->configureValues([self::ACTION_ADD, self::ACTION_REMOVE]),
            (new Fields\StringField('EVENT_ID'))
                ->configureRequired()
                ->configureSize(64)
                ->addValidator(new LengthValidator(1, 64)),
            (new Fields\StringField('REQUEST_ID'))
                ->configureRequired()
                ->configureSize(64)
                ->addValidator(new LengthValidator(1, 64)),
            (new Fields\IntegerField('USER_ID'))
                ->configureNullable(),
            (new Fields\IntegerField('CART_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('ITEM_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('PRODUCT_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('QUANTITY_BEFORE'))
                ->configureRequired(),
            (new Fields\IntegerField('QUANTITY_AFTER'))
                ->configureRequired(),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
        ];
    }
}
