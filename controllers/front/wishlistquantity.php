<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Quantity of a product on a shopping list of blockwishlist, set from the account's lists page: JSON {success}.
 * The list must belong to the signed-in customer; blockwishlist's own update action cannot save a quantity.
 */
class M4p_CoreWishlistquantityModuleFrontController extends ModuleFrontController
{
    public $auth = true;

    public $guestAllowed = false;

    public function init(): void
    {
        $this->ajax = true;
        parent::init();
    }

    public function displayAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        header('X-LiteSpeed-Cache-Control: no-cache');

        $isTokenValid = Tools::getIsset('token') && hash_equals(Tools::getToken(false), (string) Tools::getValue('token'));
        $quantity = (int) Tools::getValue('quantity');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$isTokenValid || $quantity < 1 || $quantity > M4pCoreWishlistView::MAX_QUANTITY) {
            http_response_code(400);
            $this->ajaxRender((string) json_encode(['success' => false]));

            return;
        }

        $success = M4pCoreWishlistView::setQuantity(
            $this->context,
            (int) Tools::getValue('id_wishlist'),
            (int) Tools::getValue('id_product'),
            (int) Tools::getValue('id_product_attribute'),
            $quantity
        );
        if (!$success) {
            http_response_code(404);
        }
        $this->ajaxRender((string) json_encode(['success' => $success]));
    }
}
