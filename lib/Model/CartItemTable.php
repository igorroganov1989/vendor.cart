<?php
declare(strict_types=1);

namespace Vendor\Cart\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

final class CartItemTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_cart_item';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\IntegerField('CART_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('PRODUCT_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('QUANTITY'))
                ->configureRequired(),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
            (new Fields\DatetimeField('UPDATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
            (new Reference(
                'CART',
                CartTable::class,
                Join::on('this.CART_ID', 'ref.ID'),
            ))->configureJoinType('INNER'),
        ];
    }
}
