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
 * Delivery country typed in the one-page checkout before the cart has an address. The core prices carriers for a
 * cart without an address by the context country (the shop default), so a guest from another zone would see carriers
 * and shipping costs of the shop's own country and be refused at the order. The country chosen in the address form
 * becomes the context country until the cart gets its own address, which the core then puts first.
 */
class M4pCoreCheckoutCountry
{
    private const COOKIE_KEY = 'm4p_checkout_country';
    private const ADDRESS_FORM_CONTROLLER = 'Ps_OnepagecheckoutAddressFormModuleFrontController';

    public static function apply(Context $context, $controller): void
    {
        if ($controller instanceof ModuleFrontController && get_class($controller) === self::ADDRESS_FORM_CONTROLLER) {
            self::remember($context);
        }
        $idCountry = (int) $context->cookie->{self::COOKIE_KEY};
        if ($idCountry <= 0) {
            return;
        }
        $country = new Country($idCountry, (int) $context->language->id);
        if (!Validate::isLoadedObject($country) || !$country->active) {
            unset($context->cookie->{self::COOKIE_KEY});

            return;
        }
        $context->country = $country;
    }

    /**
     * The delivery address of the form is the `invoice_` one unless "same address" is ticked.
     */
    private static function remember(Context $context): void
    {
        $sameAddress = (string) Tools::getValue('use_same_address', '1') !== '0';
        $idCountry = (int) Tools::getValue($sameAddress ? 'id_country' : 'invoice_id_country');
        if ($idCountry <= 0 || !(new Country($idCountry))->active) {
            return;
        }
        $context->cookie->{self::COOKIE_KEY} = $idCountry;
        // Cart::getTotalShippingCost() reads this one as the country of a cart without an address.
        $context->cookie->id_country = $idCountry;
        // The address form answers with JSON and exits; a cookie written at shutdown would come after the output.
        $context->cookie->write();
    }
}
