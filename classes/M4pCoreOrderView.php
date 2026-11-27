<?php

/**
 * m4p_core
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Template data of the order confirmation page ($m4p_order): lines and amounts in the price display of the customer
 * who placed the order (the visitor is often logged out by then), the kind of customer, the payment and its state,
 * and the steps after the order.
 */
class M4pCoreOrderView
{
    public const WIRE = 'wire';
    public const CASH_ON_DELIVERY = 'cod';
    public const ONLINE = 'online';

    /** Payment modules by the kind of payment; any other module counts as paid online once its state is paid. */
    private const PAYMENT_KINDS = [
        'ps_wirepayment' => self::WIRE,
        'ps_checkpayment' => self::WIRE,
        'ps_cashondelivery' => self::CASH_ON_DELIVERY,
    ];

    /**
     * @return array<string, mixed>
     */
    public static function of(Context $context, int $idCart): array
    {
        $order = new Order((int) Order::getIdByCartId($idCart));
        if (!Validate::isLoadedObject($order)) {
            return [];
        }
        $customer = new Customer((int) $order->id_customer);
        $net = Group::getPriceDisplayMethod((int) $customer->id_default_group) === PS_TAX_EXC;
        $locale = $context->getCurrentLocale();
        $iso = (new Currency((int) $order->id_currency))->iso_code;
        $price = static fn (float $amount): string => $locale->formatPrice($amount, $iso);
        $invoice = new Address((int) $order->id_address_invoice);
        $delivery = new Address((int) $order->id_address_delivery);
        $company = trim((string) $invoice->company) ?: trim((string) $customer->company);

        $lines = self::lines($context, $order, $net, $price);
        $state = $order->getCurrentOrderState();
        $kind = self::PAYMENT_KINDS[$order->module] ?? self::ONLINE;
        $paid = $state && $state->paid;

        return [
            'net' => $net,
            'customer' => $customer->is_guest ? 'guest' : ($company !== '' ? 'company' : 'private'),
            'company' => $company,
            'vat_number' => trim((string) $invoice->vat_number),
            'reference' => (string) $order->reference,
            'date' => date('d.m.Y, H:i', strtotime($order->date_add)),
            'lines' => $lines,
            'weighed' => in_array(true, array_column($lines, 'weighed'), true),
            'amounts' => [
                'products' => $price((float) ($net ? $order->total_products : $order->total_products_wt)),
                'shipping' => $price((float) ($net ? $order->total_shipping_tax_excl : $order->total_shipping_tax_incl)),
                'discount' => (float) $order->total_discounts_tax_incl > 0
                    ? '−' . $price((float) ($net ? $order->total_discounts_tax_excl : $order->total_discounts_tax_incl)) : '',
                'vat' => $price((float) $order->total_paid_tax_incl - (float) $order->total_paid_tax_excl),
                'total' => $price((float) $order->total_paid_tax_incl),
            ],
            'payment' => ['kind' => $kind, 'paid' => $paid, 'name' => (string) $order->payment, 'paid_at' => $paid ? self::paidAt($order) : ''],
            'carrier' => (new Carrier((int) $order->id_carrier, (int) $context->language->id))->name,
            'carrier_icon' => M4pCoreMethodIcons::carrierIcon(new Carrier((int) $order->id_carrier)),
            'payment_icon' => M4pCoreMethodIcons::paymentIcon((string) $order->module),
            'delivery_name' => trim(($delivery->company ?: $delivery->firstname . ' ' . $delivery->lastname)),
            'delivery_address' => self::addressLine($delivery),
            'invoice_name' => $company !== '' ? $company : trim($invoice->firstname . ' ' . $invoice->lastname),
            'invoice_address' => self::addressLine($invoice),
            'tracking' => self::tracking($order),
            'steps' => self::steps($order),
            'manager' => self::manager((int) $customer->id),
        ];
    }

    /**
     * @return array<int, array{name: string, reference: string, quantity: int, unit: string, weighed: bool, image: string, image_webp: string, value: string, unit_price: string}>
     */
    private static function lines(Context $context, Order $order, bool $net, callable $price): array
    {
        $lines = [];
        foreach ($order->getProducts() as $row) {
            $idProduct = (int) $row['product_id'];
            $view = M4pCoreProductView::of($context, $idProduct);
            $quantity = (int) $row['product_quantity'];
            $image = '';
            $cover = Product::getCover($idProduct);
            if ($cover) {
                $rewrite = (string) Db::getInstance()->getValue('SELECT link_rewrite FROM ' . _DB_PREFIX_ . 'product_lang WHERE id_product = '
                    . $idProduct . ' AND id_lang = ' . (int) $context->language->id);
                $image = $context->link->getImageLink($rewrite, (string) $cover['id_image'], 'cart_default');
            }
            $lines[] = [
                'name' => (string) $row['product_name'],
                'reference' => (string) $row['product_reference'],
                'quantity' => $quantity,
                'unit' => $view['unit'],
                'weighed' => $view['weighed'],
                'image' => $image,
                'image_webp' => $image !== '' ? preg_replace('/\.(jpe?g|png)$/i', '.webp', $image) : '',
                'value' => $price((float) ($net ? $row['total_price_tax_excl'] : $row['total_price_tax_incl'])),
                'unit_price' => $price((float) ($net ? $row['unit_price_tax_excl'] : $row['unit_price_tax_incl'])),
            ];
        }

        return $lines;
    }

    private static function addressLine(Address $address): string
    {
        if (!Validate::isLoadedObject($address)) {
            return '';
        }

        return trim($address->address1 . ($address->address2 ? ' ' . $address->address2 : '') . ', ' . $address->postcode . ' ' . $address->city);
    }

    /**
     * The tracking number of the shipment and the carrier's tracking page, if the carrier has one.
     *
     * @return array{number: string, url: string}|null
     */
    private static function tracking(Order $order): ?array
    {
        $orderCarrier = new OrderCarrier((int) $order->getIdOrderCarrier());
        $number = trim((string) $orderCarrier->tracking_number);
        if ($number === '') {
            return null;
        }
        $url = (string) (new Carrier((int) $order->id_carrier))->url;

        return ['number' => $number, 'url' => $url !== '' ? str_replace('@', rawurlencode($number), $url) : ''];
    }

    /**
     * The steps of the order with the date each was reached, from the status history: received, paid, on the way,
     * delivered; cash on delivery is paid on delivery; a cancelled order ends after "received".
     *
     * @return array<int, array{key: string, state: string, date: string}> state: done, now, next or stopped
     */
    private static function steps(Order $order): array
    {
        $history = Db::getInstance()->executeS('SELECT oh.`id_order_state`, oh.`date_add`, os.`paid`, os.`shipped`
            FROM `' . _DB_PREFIX_ . 'order_history` oh
            JOIN `' . _DB_PREFIX_ . 'order_state` os ON os.`id_order_state` = oh.`id_order_state`
            WHERE oh.`id_order` = ' . (int) $order->id . ' ORDER BY oh.`date_add`, oh.`id_order_history`') ?: [];
        $first = static function (callable $test) use ($history): string {
            foreach ($history as $row) {
                if ($test($row)) {
                    return date('d.m, H:i', strtotime((string) $row['date_add']));
                }
            }

            return '';
        };
        $stateIs = static fn (string $key): callable => static fn (array $row): bool => (int) $row['id_order_state'] === (int) Configuration::get($key);
        $received = date('d.m, H:i', strtotime((string) $order->date_add));
        $current = (int) $order->getCurrentState();

        foreach (['PS_OS_CANCELED', 'PS_OS_ERROR'] as $key) {
            if ($current === (int) Configuration::get($key)) {
                return [['key' => 'received', 'state' => 'done', 'date' => $received], ['key' => 'cancelled', 'state' => 'stopped', 'date' => $first($stateIs($key))]];
            }
        }
        $delivered = $first($stateIs('PS_OS_DELIVERED'));
        $shipped = $first(static fn (array $row): bool => (bool) $row['shipped']) ?: $delivered;
        $steps = $order->module === 'ps_cashondelivery'
            ? ['received' => $received, 'shipped' => $shipped, 'delivered_paid' => $delivered]
            : ['received' => $received, 'paid' => $first(static fn (array $row): bool => (bool) $row['paid']) ?: $shipped, 'shipped' => $shipped, 'delivered' => $delivered];

        $out = [];
        $nowGiven = false;
        foreach ($steps as $key => $date) {
            $state = $date !== '' ? 'done' : ($nowGiven ? 'next' : 'now');
            $nowGiven = $nowGiven || $state === 'now';
            $out[] = ['key' => $key, 'state' => $state, 'date' => $date];
        }

        return $out;
    }

    /** When the order first reached a paid state, as "d.m, H:i". */
    private static function paidAt(Order $order): string
    {
        $date = Db::getInstance()->getValue('SELECT MIN(oh.date_add) FROM ' . _DB_PREFIX_ . 'order_history oh
            JOIN ' . _DB_PREFIX_ . 'order_state os ON os.id_order_state = oh.id_order_state
            WHERE os.paid = 1 AND oh.id_order = ' . (int) $order->id);

        return $date ? date('d.m, H:i', strtotime((string) $date)) : '';
    }

    /**
     * The sales manager of a company customer (m4p_customermanager), or null.
     *
     * @return array<string, string>|null
     */
    private static function manager(int $idCustomer): ?array
    {
        if (!Module::isEnabled('m4p_customermanager')) {
            return null;
        }
        $module = Module::getInstanceByName('m4p_customermanager');

        return $module && method_exists($module, 'getManagerForCustomer') ? $module->getManagerForCustomer($idCustomer) : null;
    }
}
