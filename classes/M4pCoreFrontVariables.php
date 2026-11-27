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
 * Template variables of the M4P theme: $m4p_shop (sales model), $m4p_header (header strip, account and
 * mini cart), $m4p_footer (sales department, payments, delivery, company line), $priceDisplay,
 * $m4p_order_tones (order status labels) and the second price line.
 */
class M4pCoreFrontVariables
{
    /** @var Context */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Assigns the variables to Smarty; called while the front controller builds its template variables,
     * the AJAX refresh of the mini cart included.
     */
    public function assign(?string $pageName): void
    {
        $mode = $this->shopMode();
        $smarty = $this->context->smarty;
        $smarty->assign('m4p_shop', [
            'mode' => $mode,
            'b2b' => $mode === 'b2b',
            'b2c' => $mode === 'b2c',
            'prices' => $mode === 'b2c' || $this->context->customer->isLogged(),
        ]);
        $smarty->assign('m4p_header', $this->headerVariables());
        $smarty->assign('m4p_footer', $this->footerVariables());
        $smarty->assign('m4p_newsletter', [
            'title' => trim((string) M4pCoreSettings::get('NEWSLETTER_TITLE', '')),
            'text' => trim((string) M4pCoreSettings::get('NEWSLETTER_TEXT', '')),
            'image' => M4pCoreLogoMark::url(M4pCoreLogoMark::NEWSLETTER_KEY),
            'image_size' => M4pCoreLogoMark::size(M4pCoreLogoMark::NEWSLETTER_KEY),
        ]);
        $smarty->assign('priceDisplay', Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer));
        if ($pageName === 'index') {
            $smarty->assign('m4p_featured_tile', $this->featuredTile());
        }
        if (in_array($pageName, ['my-account', 'history', 'order-detail', 'guest-tracking'], true)) {
            $smarty->assign('m4p_order_tones', $this->orderStateTones());
        }
    }

    /**
     * The text tile of the home page Featured row (Home page: Featured section); empty without a title and a description.
     *
     * @return array{kicker: string, title: string, text: string, link_text: string, link_url: string}|array{}
     */
    private function featuredTile(): array
    {
        $tile = [
            'kicker' => trim((string) M4pCoreSettings::get('FEATURED_TILE_KICKER', '')),
            'title' => trim((string) M4pCoreSettings::get('FEATURED_TILE_TITLE', '')),
            'text' => trim((string) M4pCoreSettings::get('FEATURED_TILE_TEXT', '')),
            'link_text' => trim((string) M4pCoreSettings::get('FEATURED_TILE_LINK_TEXT', '')),
            'link_url' => trim((string) M4pCoreSettings::get('FEATURED_TILE_LINK_URL', '')),
        ];

        return $tile['title'] === '' && $tile['text'] === '' ? [] : $tile;
    }

    /**
     * Unit price of a product or cart line with or without tax, for the second price line next to the one
     * the customer group displays ({hook h='displayM4pPrice' product=$product tax=true}).
     *
     * @param array{product?: array|ArrayAccess, tax?: bool} $params
     */
    public function price(array $params): string
    {
        $product = $params['product'] ?? null;
        if ((!is_array($product) && !$product instanceof ArrayAccess) || empty($product['id_product'])) {
            return '';
        }

        $amount = Product::getPriceStatic(
            (int) $product['id_product'],
            (bool) ($params['tax'] ?? true),
            isset($product['id_product_attribute']) ? (int) $product['id_product_attribute'] : null
        );

        return (new PrestaShop\PrestaShop\Adapter\Product\PriceFormatter())->format($amount);
    }

    /**
     * Tone of the theme's status label for each order state; unpaid states not listed here read as awaiting payment.
     *
     * @return array<int, string> id_order_state => success|info|warning|danger|neutral
     */
    public function orderStateTones(): array
    {
        $tones = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id, false) as $state) {
            $tones[(int) $state['id_order_state']] = $state['paid'] ? 'success' : 'warning';
        }
        $byKey = [
            'PS_OS_PREPARATION' => 'info',
            'PS_OS_SHIPPING' => 'info',
            'PS_OS_DELIVERED' => 'success',
            'PS_OS_REFUND' => 'neutral',
            'PS_OS_CANCELED' => 'danger',
            'PS_OS_ERROR' => 'danger',
        ];
        foreach ($byKey as $key => $tone) {
            $idState = (int) Configuration::get($key);
            if ($idState > 0) {
                $tones[$idState] = $tone;
            }
        }

        return $tones;
    }

    /**
     * @return array<string, mixed>
     */
    public function headerVariables(): array
    {
        $context = $this->context;
        $idLang = (int) $context->language->id;
        $customer = $context->customer;
        $logged = Validate::isLoadedObject($customer) && $customer->isLogged();
        $cutoff = $this->headerCutoff();
        $now = new DateTimeImmutable('now');
        $deadline = $this->headerDeadline($now, $cutoff);
        $delivery = $this->headerDeliveryDay($deadline);
        $minutesLeft = max(0, (int) ceil(($deadline->getTimestamp() - $now->getTimestamp()) / 60));
        $b2c = $this->shopMode() === 'b2c';
        $guestText = trim((string) Configuration::get($b2c ? 'M4P_CORE_HEADER_STRIP_B2C' : 'M4P_CORE_HEADER_STRIP_GUEST', $idLang));
        if ($guestText === '') {
            $guestText = $b2c
                ? $this->context->getTranslator()->trans('Delivery cost and time are shown in the cart before you order.', [], 'Shop.Theme.Global')
                : $this->context->getTranslator()->trans('Net prices are shown after logging in. Delivery cost and time are shown in the cart.', [], 'Shop.Theme.Global');
        }
        $phone = $this->headerSalesPhone();

        return [
            'strip' => (bool) $this->headerSetting('HEADER_STRIP', '1'),
            'compact' => (bool) $this->headerSetting('HEADER_COMPACT', '1'),
            'after_add' => M4pCoreSettings::get('CART_AFTER_ADD', 'minicart') === 'modal' ? 'modal' : 'minicart',
            'strip_guest' => strtr($guestText, ['{cutoff}' => $cutoff]),
            'cutoff' => $cutoff,
            'delivery_day' => $this->headerDayName($delivery, 'EEEE') . ' ' . $delivery->format('d.m'),
            'delivery_day_short' => $this->headerDayName($delivery, 'EEE') . ' ' . $delivery->format('d.m'),
            'deadline' => $deadline->getTimestamp() * 1000,
            'hours_left' => intdiv($minutesLeft, 60),
            'minutes_left' => $minutesLeft % 60,
            'phone' => $phone,
            'phone_href' => preg_replace('/[^0-9+]/', '', $phone),
            'hours' => $this->headerSalesHours($idLang),
            'registration_url' => $context->link->getPageLink('registration', true),
            'wishlist_url' => Module::isEnabled('blockwishlist') ? $context->link->getModuleLink('blockwishlist', 'lists', [], true) : '',
            'account' => $logged ? $this->headerAccount($customer) : null,
            'cart' => $logged || $b2c ? $this->headerCart($context->cart) : null,
        ];
    }

    /** @return string 'b2b' or 'b2c', from the "Sales model" */
    public function shopMode(): string
    {
        return M4pCoreShopMode::current($this->context);
    }

    /** @return mixed */
    private function headerSetting(string $key, $default)
    {
        $value = Configuration::get('M4P_CORE_' . $key);

        return $value === false || $value === null || $value === '' ? $default : $value;
    }

    private function headerCutoff(): string
    {
        $cutoff = trim((string) $this->headerSetting('ORDER_CUTOFF', '14:00'));

        return preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $cutoff) ? $cutoff : '14:00';
    }

    /** @return int[] ISO weekdays with deliveries, which are the working days as well */
    private function headerDeliveryDays(): array
    {
        $days = array_values(array_intersect(range(1, 7), array_map('intval', explode(',', (string) $this->headerSetting('DELIVERY_DAYS', '1,2,3,4,5')))));

        return $days ?: [1, 2, 3, 4, 5];
    }

    /** The next cut-off still ahead, the same rule as m4p_homeintro uses. */
    private function headerDeadline(DateTimeImmutable $now, string $cutoff): DateTimeImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $cutoff));
        $days = $this->headerDeliveryDays();
        $day = $now->setTime($hour, $minute);
        for ($i = 0; $i < 14; ++$i) {
            if ($day > $now && in_array((int) $day->format('N'), $days, true)) {
                return $day;
            }
            $day = $day->modify('+1 day');
        }

        return $day;
    }

    private function headerDeliveryDay(DateTimeImmutable $deadline): DateTimeImmutable
    {
        $days = $this->headerDeliveryDays();
        $day = $deadline->modify('+1 day');
        for ($i = 0; $i < 14 && !in_array((int) $day->format('N'), $days, true); ++$i) {
            $day = $day->modify('+1 day');
        }

        return $day;
    }

    /** Planned delivery of an order placed at $placedAt, as "czwartek 27.11", or '' once that day has passed. */
    public function deliveryDayFor(DateTimeImmutable $placedAt): string
    {
        $day = $this->headerDeliveryDay($this->headerDeadline($placedAt, $this->headerCutoff()));
        if ($day->format('Y-m-d') < (new DateTimeImmutable())->format('Y-m-d')) {
            return '';
        }

        return $this->headerDayName($day, 'EEEE') . ' ' . $day->format('d.m');
    }

    private function headerDayName(DateTimeImmutable $day, string $pattern): string
    {
        if (!class_exists('IntlDateFormatter')) {
            return $day->format('D');
        }
        $formatter = new IntlDateFormatter((string) $this->context->language->locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, $day->getTimezone()->getName(), null, $pattern);

        return (string) $formatter->format($day);
    }

    private function headerSalesPhone(): string
    {
        return M4pCoreSales::phone();
    }

    /** Weekly hours of the sales department as "pn–pt 7:30–16:00", or the widget's free text when no week is set. */
    private function headerSalesHours(int $idLang): string
    {
        $lines = M4pCoreSales::lines($this->dayNames());
        if ($lines) {
            return implode(', ', $lines);
        }

        return Module::isEnabled('m4p_contactwidget') ? trim((string) Configuration::get('M4P_CONTACTWIDGET_HOURS', $idLang)) : '';
    }

    /**
     * @return array<int, string> ISO day number => short name in the shop language
     */
    private function dayNames(): array
    {
        $translator = $this->context->getTranslator();

        return [
            1 => $translator->trans('Mon', [], 'Shop.Theme.Global'),
            2 => $translator->trans('Tue', [], 'Shop.Theme.Global'),
            3 => $translator->trans('Wed', [], 'Shop.Theme.Global'),
            4 => $translator->trans('Thu', [], 'Shop.Theme.Global'),
            5 => $translator->trans('Fri', [], 'Shop.Theme.Global'),
            6 => $translator->trans('Sat', [], 'Shop.Theme.Global'),
            7 => $translator->trans('Sun', [], 'Shop.Theme.Global'),
        ];
    }

    /**
     * The footer of the M4P theme: sales department, payments and delivery, company line (parts of segments that
     * do not break inside: name and address lines, then each labelled number).
     *
     * @return array<string, mixed>
     */
    public function footerVariables(): array
    {
        $phone = M4pCoreSales::phone();
        $lines = static function (string $key): array {
            return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) M4pCoreSettings::get($key, '')) ?: [])));
        };
        $labelled = [
            'COMPANY_TAX_ID' => $this->context->getTranslator()->trans('Tax ID', [], 'Shop.Theme.Global'),
            'COMPANY_KRS' => 'KRS',
            'COMPANY_COURT' => '',
            'COMPANY_CAPITAL' => $this->context->getTranslator()->trans('Share capital', [], 'Shop.Theme.Global'),
            'COMPANY_VET' => $this->context->getTranslator()->trans('Veterinary approval number', [], 'Shop.Theme.Global'),
        ];
        $company = [];
        $identity = array_values(array_filter(array_merge([trim((string) M4pCoreSettings::get('COMPANY_NAME', ''))], $lines('COMPANY_ADDRESS'))));
        if ($identity) {
            $company[] = $identity;
        }
        foreach ($labelled as $key => $label) {
            $value = trim((string) M4pCoreSettings::get($key, ''));
            if ($value !== '') {
                $company[] = [trim($label . ' ' . $value)];
            }
        }

        return [
            'logo' => M4pCoreLogoMark::url(M4pCoreLogoMark::LIGHT_KEY),
            'phone' => $phone,
            'phone_href' => preg_replace('/[^0-9+]/', '', $phone),
            'hours' => M4pCoreSales::lines($this->dayNames()),
            'email' => M4pCoreSales::email(),
            'payments' => $lines('FOOTER_PAYMENTS'),
            'delivery' => trim((string) M4pCoreSettings::get('FOOTER_DELIVERY', '')),
            'pickup' => trim((string) M4pCoreSettings::get('FOOTER_PICKUP', '')),
            'company' => $company,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function headerAccount(Customer $customer): array
    {
        $name = trim($customer->firstname . ' ' . $customer->lastname);
        $nip = trim((string) $customer->siret);
        if ($nip === '') {
            $nip = trim((string) Db::getInstance()->getValue(
                'SELECT `vat_number` FROM `' . _DB_PREFIX_ . 'address` WHERE `id_customer` = ' . (int) $customer->id
                . ' AND `deleted` = 0 AND `vat_number` <> \'\' ORDER BY `id_address` DESC'
            ));
        }
        $manager = null;
        if (Module::isEnabled('m4p_customermanager') && Configuration::get('M4P_CUSTOMERMANAGER_SHOW_NAV')) {
            $module = Module::getInstanceByName('m4p_customermanager');
            $manager = $module && method_exists($module, 'getManagerForCustomer') ? $module->getManagerForCustomer((int) $customer->id) : null;
        }

        $b2c = $this->shopMode() === 'b2c';

        return [
            'company' => $b2c ? $name : (trim((string) $customer->company) ?: $name),
            'name' => $name,
            'nip' => $b2c ? '' : $nip,
            'manager' => $manager ? $manager + ['phone_href' => preg_replace('/[^0-9+]/', '', (string) $manager['phone'])] : null,
        ];
    }

    /**
     * Net amounts of the cart for the header button and the mini cart: line totals by product key,
     * the products total and the progress to the minimum order.
     *
     * @return array<string, mixed>
     */
    private function headerCart(Cart $cart): array
    {
        $locale = $this->context->getCurrentLocale();
        $iso = $this->context->currency->iso_code;
        $lines = [];
        $packs = [];
        $products = Validate::isLoadedObject($cart) ? $cart->getProducts() : [];
        $packFeature = $this->headerPackFeature();
        $gross = $this->shopMode() === 'b2c';
        if ($products && $packFeature) {
            $ids = implode(',', array_map('intval', array_column($products, 'id_product')));
            foreach (Db::getInstance()->executeS(
                'SELECT fp.`id_product`, fvl.`value` FROM `' . _DB_PREFIX_ . 'feature_product` fp
                JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON fvl.`id_feature_value` = fp.`id_feature_value` AND fvl.`id_lang` = ' . (int) $this->context->language->id . '
                WHERE fp.`id_feature` = ' . $packFeature . ' AND fp.`id_product` IN (' . $ids . ')'
            ) ?: [] as $row) {
                $packs[(int) $row['id_product']] = (string) $row['value'];
            }
        }
        $units = $this->headerUnits($products);
        foreach ($products as $product) {
            $key = (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'] . '-' . (int) $product['id_customization'];
            $unit = $units[(int) $product['id_product']] ?? '';
            $lines[$key] = [
                'total' => $locale->formatPrice((float) $product[$gross ? 'total_wt' : 'total'], $iso),
                'pack' => $packs[(int) $product['id_product']] ?? '',
                'unit' => $unit,
                'weighed' => mb_strtolower(trim($unit)) === 'kg',
            ];
        }
        $total = $products ? (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) : 0.0;
        $shownTotal = $gross && $products ? (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) : $total;
        $minimum = (float) Tools::convertPrice((float) Configuration::get('PS_PURCHASE_MINIMUM'), $this->context->currency);

        return [
            'lines' => $lines,
            'total' => $locale->formatPrice($shownTotal, $iso),
            'gross' => $gross,
            'minimum' => $minimum > 0 ? $locale->formatPrice($minimum, $iso) : '',
            'missing' => $minimum > $total ? $locale->formatPrice($minimum - $total, $iso) : '',
            'progress' => $minimum > 0 ? (int) min(100, floor($total / $minimum * 100)) : 100,
        ];
    }

    /**
     * Sale unit of the cart products, from the feature chosen in the Product page section.
     *
     * @param array<int, array<string, mixed>> $products
     *
     * @return array<int, string> id_product => unit
     */
    private function headerUnits(array $products): array
    {
        $feature = (int) M4pCoreSettings::get(M4pCoreProductView::UNIT, 0);
        if (!$products || $feature <= 0) {
            return [];
        }
        $units = [];
        $ids = implode(',', array_map('intval', array_column($products, 'id_product')));
        foreach (Db::getInstance()->executeS(
            'SELECT fp.`id_product`, fvl.`value` FROM `' . _DB_PREFIX_ . 'feature_product` fp
            JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON fvl.`id_feature_value` = fp.`id_feature_value` AND fvl.`id_lang` = ' . (int) $this->context->language->id . '
            WHERE fp.`id_feature` = ' . $feature . ' AND fp.`id_product` IN (' . $ids . ')'
        ) ?: [] as $row) {
            $units[(int) $row['id_product']] = (string) $row['value'];
        }

        return $units;
    }

    /** The product feature holding the pack size: m4p_core PACK_FEATURE, else the feature named so. */
    private function headerPackFeature(): int
    {
        $id = (int) $this->headerSetting('PACK_FEATURE', 0);
        if ($id > 0) {
            return $id;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_feature` FROM `' . _DB_PREFIX_ . 'feature_lang` WHERE LOWER(`name`) IN (\'opakowanie\', \'packaging\') ORDER BY `id_feature`'
        );
    }
}
