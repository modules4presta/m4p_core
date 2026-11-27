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
 * Delivery address picked in the checkout: JSON {success}.
 */
class M4p_CoreCheckoutaddressModuleFrontController extends ModuleFrontController
{
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
        if (!$isTokenValid || !Tools::isSubmit('id_address')) {
            http_response_code(403);
            $this->ajaxRender((string) json_encode(['success' => false]));

            return;
        }

        $success = M4pCoreCheckoutAddress::useForDelivery(
            $this->context,
            (int) Tools::getValue('id_address'),
            (int) Tools::getValue('id_address_invoice')
        );
        $this->ajaxRender((string) json_encode(['success' => $success]));
    }
}
