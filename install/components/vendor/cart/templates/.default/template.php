<?php
declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
?>
<div class="vendor-cart" data-vendor-cart>
    <div class="vendor-cart__error" data-vendor-cart-errors>
        <?php if (!empty($arResult['ERRORS'])): ?>
            <?= htmlspecialcharsbx(implode(' ', (array)$arResult['ERRORS'])) ?>
        <?php endif; ?>
    </div>

    <ul class="vendor-cart__items">
        <?php foreach ((array)$arResult['ITEMS'] as $item): ?>
            <?php
            $itemId = (int)($item['id'] ?? 0);
            $productId = (int)($item['productId'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            ?>
            <li class="vendor-cart__item" data-cart-item-id="<?= $itemId ?>">
                <span>Товар #<?= $productId ?> — <?= $quantity ?> шт.</span>
                <button type="button" data-vendor-cart-remove="<?= $itemId ?>">Удалить</button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<script>
BX.ready(function () {
    document.querySelectorAll('[data-vendor-cart]').forEach(function (root) {
        window.VendorCart.bind(root);
    });
});
</script>
