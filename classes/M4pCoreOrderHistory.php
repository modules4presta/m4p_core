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
 * Template data of the order list in the customer account ($m4p_history): the orders with their status group
 * ("to pay", "in progress", closed) and search text, filtered by group and by an order number or a product name or
 * reference, a page at a time. Up to one page of orders the list is "live": every order is on the page and the
 * theme filters the rows in place; the same filters work from the address without scripts.
 */
class M4pCoreOrderHistory
{
    public const TO_PAY = 'pay';
    public const IN_PROGRESS = 'progress';

    public const KEY_TO_PAY = 'ORDERS_TO_PAY';
    public const KEY_IN_PROGRESS = 'ORDERS_IN_PROGRESS';
    public const KEY_PER_PAGE = 'ORDERS_PER_PAGE';
    public const DEFAULT_PER_PAGE = 20;

    private const QUERY_MAX_LENGTH = 64;

    /** States that end an order; payment modules name theirs with these endings (PS_CHECKOUT_STATE_VOIDED). */
    private const CLOSED_KEYS = ['PS_OS_DELIVERED', 'PS_OS_CANCELED', 'PS_OS_REFUND', 'PS_OS_ERROR'];
    private const CLOSED_KEY_PATTERN = '/_(CANCELED|CANCELLED|REFUNDED|PARTIALLY_REFUNDED|VOIDED|ERROR)$/';
    private const AUTHORIZED_KEY_PATTERN = '/_AUTHORIZED$/';

    /**
     * @return array<string, mixed>
     */
    public static function of(Context $context, string $status, string $query, int $page): array
    {
        $customer = $context->customer;
        if (!$customer || !$customer->isLogged() || $customer->is_guest) {
            return [];
        }
        $status = in_array($status, [self::TO_PAY, self::IN_PROGRESS], true) ? $status : '';
        $query = mb_substr(trim($query), 0, self::QUERY_MAX_LENGTH);
        $needle = mb_strtolower($query);
        $rows = Order::getCustomerOrders((int) $customer->id, false, $context);
        $texts = self::searchTexts(array_map(static fn (array $row): int => (int) $row['id_order'], $rows));
        $groups = [self::TO_PAY => self::states(self::TO_PAY), self::IN_PROGRESS => self::states(self::IN_PROGRESS)];

        $all = [];
        foreach ($rows as $row) {
            $state = (int) $row['id_order_state'];
            $inGroups = array_keys(array_filter($groups, static fn (array $states): bool => in_array($state, $states, true)));
            $search = mb_strtolower((string) $row['reference']) . ' ' . ($texts[(int) $row['id_order']] ?? '');
            $all[] = [
                'row' => $row,
                'groups' => $inGroups,
                'search' => $search,
                'match' => ($status === '' || in_array($status, $inGroups, true)) && ($needle === '' || strpos($search, $needle) !== false),
            ];
        }
        $matching = array_values(array_filter($all, static fn (array $order): bool => $order['match']));
        $perPage = self::perPage();
        $live = count($all) <= $perPage;
        $pages = $live ? 1 : max(1, (int) ceil(count($matching) / $perPage));
        $page = min(max(1, $page), $pages);
        $shown = $live ? $all : array_slice($matching, ($page - 1) * $perPage, $perPage);

        $net = Group::getPriceDisplayMethod((int) $customer->id_default_group) === PS_TAX_EXC;
        $orders = [];
        foreach ($shown as $order) {
            $orders[] = M4pCoreAccountView::orderRow($context, $order['row'], $net) + [
                'groups' => $order['groups'],
                'groups_attr' => implode(' ', $order['groups']),
                'search' => $order['search'],
                'match' => $order['match'],
                'transfer_url' => in_array(self::TO_PAY, $order['groups'], true) ? M4pCoreAccountView::transferUrl($context, (int) $order['row']['id_order']) : '',
            ];
        }

        return [
            'orders' => $orders,
            'count' => count($all),
            'matching' => count($matching),
            'status' => $status,
            'query' => $query,
            'live' => $live,
            'page' => $page,
            'pages' => $pages,
            'net' => $net,
        ];
    }

    /**
     * The states of a group: the M4P Core setting, or the automatic choice while nothing is set.
     *
     * @return int[]
     */
    public static function states(string $group): array
    {
        $saved = (string) M4pCoreSettings::get($group === self::TO_PAY ? self::KEY_TO_PAY : self::KEY_IN_PROGRESS, '');

        return array_map('intval', array_filter(explode(',', $saved !== '' ? $saved : self::defaultStates($group))));
    }

    /**
     * Automatic groups from the state flags: "to pay" – not paid and not yet counted as a sale, except cash on delivery
     * and an authorized card; "in progress" – every other state that does not end the order.
     */
    public static function defaultStates(string $group): string
    {
        $closed = self::closedStates();
        $authorized = self::statesByKey(self::AUTHORIZED_KEY_PATTERN);
        $cashOnDelivery = (int) Configuration::get('PS_OS_COD_VALIDATION');
        $ids = [];
        foreach (self::activeStates() as $state) {
            $id = (int) $state['id_order_state'];
            if (in_array($id, $closed, true)) {
                continue;
            }
            $toPay = !$state['paid'] && !$state['logable'] && $id !== $cashOnDelivery && !in_array($id, $authorized, true);
            if ($toPay === ($group === self::TO_PAY)) {
                $ids[] = $id;
            }
        }

        return implode(',', $ids);
    }

    /**
     * @return array<int, string> id => name of every order state in use, for the setting
     */
    public static function stateChoices(): array
    {
        $choices = [];
        foreach (self::activeStates() as $state) {
            $choices[(int) $state['id_order_state']] = (string) $state['name'];
        }

        return $choices;
    }

    public static function perPage(): int
    {
        return min(100, max(5, (int) M4pCoreSettings::get(self::KEY_PER_PAGE, self::DEFAULT_PER_PAGE)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function activeStates(): array
    {
        return array_values(array_filter(
            OrderState::getOrderStates((int) Context::getContext()->language->id, false),
            static fn (array $state): bool => empty($state['deleted'])
        ));
    }

    /**
     * @return int[]
     */
    private static function closedStates(): array
    {
        $ids = array_map(static fn (string $key): int => (int) Configuration::get($key), self::CLOSED_KEYS);

        return array_values(array_unique(array_merge($ids, self::statesByKey(self::CLOSED_KEY_PATTERN))));
    }

    /**
     * Order states stored under configuration keys of payment modules matching $pattern (PS_CHECKOUT_STATE_VOIDED).
     *
     * @return int[]
     */
    private static function statesByKey(string $pattern): array
    {
        $rows = Db::getInstance()->executeS('SELECT `name`, `value` FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` LIKE \'%STATE%\'');
        $ids = [];
        foreach ($rows ?: [] as $row) {
            if (preg_match($pattern, (string) $row['name']) && ctype_digit((string) $row['value'])) {
                $ids[] = (int) $row['value'];
            }
        }

        return $ids;
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, string> id_order => product names and references, lower case
     */
    private static function searchTexts(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $rows = Db::getInstance()->executeS('SELECT `id_order`, GROUP_CONCAT(CONCAT(`product_name`, \' \', `product_reference`) SEPARATOR \' \') AS `text`
            FROM `' . _DB_PREFIX_ . 'order_detail` WHERE `id_order` IN (' . implode(',', array_map('intval', $ids)) . ') GROUP BY `id_order`');
        $texts = [];
        foreach ($rows ?: [] as $row) {
            $texts[(int) $row['id_order']] = mb_strtolower((string) $row['text']);
        }

        return $texts;
    }
}
