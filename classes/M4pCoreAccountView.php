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
 * Template data of the account dashboard ($m4p_account): the last order with its planned delivery, the orders before
 * it and the newest order still waiting for a bank transfer. Amounts follow the price display of the customer's group.
 */
class M4pCoreAccountView
{
    private const PREVIOUS = 4;

    /** States after which no delivery is planned any more. */
    private const CLOSED_STATES = ['PS_OS_DELIVERED', 'PS_OS_CANCELED', 'PS_OS_ERROR', 'PS_OS_REFUND'];

    /**
     * @return array<string, mixed>
     */
    public static function of(Context $context): array
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest) {
            return [];
        }
        $rows = Order::getCustomerOrders((int) $customer->id, false, $context);
        if (!$rows) {
            return ['count' => 0, 'last' => null, 'previous' => [], 'awaiting' => null];
        }
        $net = Group::getPriceDisplayMethod((int) $customer->id_default_group) === PS_TAX_EXC;
        $orders = [];
        foreach (array_slice($rows, 0, 1 + self::PREVIOUS) as $row) {
            $orders[] = self::orderRow($context, $row, $net);
        }
        $orders[0]['delivery_day'] = self::deliveryDay($context, $rows[0]);

        return [
            'count' => count($rows),
            'net' => $net,
            'last' => $orders[0],
            'previous' => array_slice($orders, 1),
            'awaiting' => self::awaitingTransfer($context, $rows),
        ];
    }

    /**
     * One order of the dashboard and of the order list.
     *
     * @param array<string, mixed> $row a row of Order::getCustomerOrders()
     *
     * @return array<string, mixed>
     */
    public static function orderRow(Context $context, array $row, bool $net): array
    {
        $order = new Order((int) $row['id_order']);

        return [
            'id' => (int) $order->id,
            'reference' => (string) $order->reference,
            'date' => date('d.m.Y', strtotime((string) $order->date_add)),
            'lines' => (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'order_detail` WHERE `id_order` = ' . (int) $order->id),
            'total' => self::price($context, $order, (float) ($net ? $order->total_paid_tax_excl : $order->total_paid_tax_incl)),
            'payment' => (string) $order->payment,
            'payment_icon' => M4pCoreMethodIcons::paymentIcon((string) $order->module),
            'state' => (int) $row['id_order_state'],
            'state_name' => (string) $row['order_state'],
            'details_url' => $context->link->getPageLink('order-detail', true, null, ['id_order' => (int) $order->id]),
            'reorder_url' => HistoryController::getUrlToReorder((int) $order->id, $context),
            'invoice_url' => HistoryController::getUrlToInvoice($order, $context),
        ];
    }

    private static function price(Context $context, Order $order, float $amount): string
    {
        return $context->getCurrentLocale()->formatPrice($amount, (new Currency((int) $order->id_currency))->iso_code);
    }

    /**
     * Planned delivery of an order still on its way: the delivery day the order made with the cut-off (M4P Core,
     * Header), or '' once it is closed or that day has passed.
     *
     * @param array<string, mixed> $row
     */
    private static function deliveryDay(Context $context, array $row): string
    {
        $closed = array_map(static fn (string $key): int => (int) Configuration::get($key), self::CLOSED_STATES);
        if (in_array((int) $row['id_order_state'], $closed, true)) {
            return '';
        }

        return (new M4pCoreFrontVariables($context))->deliveryDayFor(new DateTimeImmutable((string) $row['date_add']));
    }

    /**
     * The newest order waiting for a bank transfer, with the link to its transfer details (the order confirmation page).
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private static function awaitingTransfer(Context $context, array $rows): ?array
    {
        $toPay = M4pCoreOrderHistory::states(M4pCoreOrderHistory::TO_PAY);
        foreach ($rows as $row) {
            if (!in_array((int) $row['id_order_state'], $toPay, true) || $row['module'] !== 'ps_wirepayment') {
                continue;
            }
            $order = new Order((int) $row['id_order']);

            return [
                'is_last' => (int) $order->id === (int) $rows[0]['id_order'],
                'reference' => (string) $order->reference,
                'total' => self::price($context, $order, (float) $order->total_paid_tax_incl),
                'details_url' => self::transferUrl($context, (int) $order->id),
            ];
        }

        return null;
    }

    /**
     * The order confirmation page of a bank transfer order, which shows the transfer details; '' for other payments.
     */
    public static function transferUrl(Context $context, int $idOrder): string
    {
        $order = new Order($idOrder);
        if ($order->module !== 'ps_wirepayment') {
            return '';
        }

        return $context->link->getPageLink('order-confirmation', true, null, [
            'id_cart' => (int) $order->id_cart,
            'id_module' => (int) Module::getModuleIdByName((string) $order->module),
            'id_order' => (int) $order->id,
            'key' => (string) $order->secure_key,
        ]);
    }
}
