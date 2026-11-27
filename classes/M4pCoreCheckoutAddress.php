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
 * A saved address picked as the delivery address in the one-page checkout moves the cart onto it at once, so the
 * carriers, shipping costs and taxes of the page follow it before the order is placed.
 */
class M4pCoreCheckoutAddress
{
    public static function useForDelivery(Context $context, int $idAddress, int $idBillingAddress): bool
    {
        $cart = $context->cart;
        $customer = $context->customer;
        if (!Validate::isLoadedObject($cart) || !$customer->isLogged() || !self::isOwnAddress((int) $customer->id, $idAddress)) {
            return false;
        }
        if ((int) $cart->id_address_delivery === $idAddress) {
            return true;
        }

        $cart->id_address_delivery = $idAddress;
        if (!(int) $cart->id_address_invoice) {
            $cart->id_address_invoice = self::isOwnAddress((int) $customer->id, $idBillingAddress) ? $idBillingAddress : $idAddress;
        }
        if (!$cart->update()) {
            return false;
        }
        // cart lines carry their own delivery address
        $cart->setNoMultishipping();

        return true;
    }

    private static function isOwnAddress(int $idCustomer, int $idAddress): bool
    {
        if ($idAddress <= 0) {
            return false;
        }
        $address = new Address($idAddress);

        return Validate::isLoadedObject($address) && !$address->deleted && (int) $address->id_customer === $idCustomer;
    }
}
