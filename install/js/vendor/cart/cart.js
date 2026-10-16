(function (window) {
    'use strict';

    var ACTION_PREFIX = 'vendor:cart.cart.';

    function errorMessage(error) {
        if (error && Array.isArray(error.errors) && error.errors.length > 0) {
            return error.errors
                .map(function (item) { return String(item.message || 'Ошибка корзины'); })
                .join('\n');
        }

        return 'Не удалось выполнить операцию с корзиной.';
    }

    function run(action, options, changed) {
        return BX.ajax.runAction(ACTION_PREFIX + action, options || {})
            .then(function (response) {
                var data = response && response.data ? response.data : {};
                if (changed) {
                    document.dispatchEvent(new CustomEvent('vendor:cart:changed', {
                        detail: {action: action, data: data},
                    }));
                }
                return data;
            })
            .catch(function (error) {
                var safeError = new Error(errorMessage(error));
                safeError.code = 'VENDOR_CART_REQUEST_FAILED';
                throw safeError;
            });
    }

    function render(root, data) {
        var list = root.querySelector('.vendor-cart__items');
        if (!list) {
            return;
        }

        list.replaceChildren();
        (data.items || []).forEach(function (item) {
            var row = document.createElement('li');
            var text = document.createElement('span');
            var button = document.createElement('button');

            row.className = 'vendor-cart__item';
            row.dataset.cartItemId = String(item.id);
            text.textContent = 'Товар #' + item.productId + ' — ' + item.quantity + ' шт.';
            button.type = 'button';
            button.dataset.vendorCartRemove = String(item.id);
            button.textContent = 'Удалить';

            row.append(text, document.createTextNode(' '), button);
            list.append(row);
        });
    }

    function showError(root, error) {
        var container = root.querySelector('[data-vendor-cart-errors]');
        if (container) {
            container.textContent = error && error.message
                ? error.message
                : 'Не удалось обновить корзину.';
        }
    }

    var api = {
        list: function () {
            return run('list', {method: 'GET'}, false);
        },
        add: function (productId, quantity) {
            return run('add', {
                method: 'POST',
                data: {
                    productId: Number(productId),
                    quantity: Number(quantity || 1),
                },
            }, true);
        },
        remove: function (itemId) {
            return run('remove', {
                method: 'POST',
                data: {itemId: Number(itemId)},
            }, true);
        },
        bind: function (root) {
            if (!root || root.dataset.vendorCartBound === 'true') {
                return;
            }

            root.dataset.vendorCartBound = 'true';
            root.addEventListener('click', function (event) {
                var button = event.target.closest('[data-vendor-cart-remove]');
                if (!button || !root.contains(button)) {
                    return;
                }

                api.remove(Number(button.dataset.vendorCartRemove))
                    .catch(function (error) { showError(root, error); });
            });

            document.addEventListener('vendor:cart:changed', function () {
                api.list()
                    .then(function (data) { render(root, data); })
                    .catch(function (error) { showError(root, error); });
            });
        },
    };

    window.VendorCart = Object.freeze(api);
})(window);
