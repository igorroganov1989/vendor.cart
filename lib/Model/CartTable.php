<?php
declare(strict_types=1);

namespace Vendor\Cart\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

final class CartTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_cart_cart';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\IntegerField('USER_ID'))
                ->configureNullable(),
            (new Fields\StringField('GUEST_KEY'))
                ->configureNullable()
                ->configureSize(64)
                ->addValidator(new LengthValidator(null, 64)),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
            (new Fields\DatetimeField('UPDATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
        ];
    }
}
