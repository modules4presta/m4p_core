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

require_once __DIR__ . '/classes/M4pCoreSettings.php';
require_once __DIR__ . '/classes/M4pCoreCategoryImage.php';
require_once __DIR__ . '/classes/M4pCoreSections.php';
require_once __DIR__ . '/classes/M4pCoreTheme.php';
require_once __DIR__ . '/classes/M4pCoreCmsDates.php';
require_once __DIR__ . '/classes/M4pCoreCmsLayout.php';
require_once __DIR__ . '/classes/M4pCoreLogoMark.php';
require_once __DIR__ . '/classes/M4pCoreSales.php';
require_once __DIR__ . '/classes/M4pCoreImageLimit.php';
require_once __DIR__ . '/classes/M4pCoreProductView.php';
require_once __DIR__ . '/classes/M4pCoreCartView.php';
require_once __DIR__ . '/classes/M4pCoreMotion.php';
require_once __DIR__ . '/classes/M4pCoreShopMode.php';
require_once __DIR__ . '/classes/M4pCoreFrontVariables.php';
require_once __DIR__ . '/classes/M4pCoreAccountView.php';
require_once __DIR__ . '/classes/M4pCoreOrderHistory.php';
require_once __DIR__ . '/classes/M4pCoreCmsTables.php';
require_once __DIR__ . '/classes/M4pCoreCmsToc.php';
require_once __DIR__ . '/classes/M4pCoreCategoryView.php';
require_once __DIR__ . '/classes/M4pCoreBestsellers.php';
require_once __DIR__ . '/classes/M4pCoreUnits.php';
require_once __DIR__ . '/classes/M4pCoreOrderView.php';
require_once __DIR__ . '/classes/M4pCoreWishlistView.php';
require_once __DIR__ . '/classes/M4pCoreDiscountView.php';
require_once __DIR__ . '/classes/M4pCoreSlipView.php';
require_once __DIR__ . '/classes/M4pCoreMethodIcons.php';
require_once __DIR__ . '/classes/M4pCoreCheckoutCountry.php';
require_once __DIR__ . '/classes/M4pCoreCheckoutAddress.php';

class M4p_Core extends Module
{
    public const TAB_PARENT = 'AdminM4pCore';
    public const TAB_DASHBOARD = 'AdminM4pCoreDashboard';
    public const TAB_SETTINGS = 'AdminM4pCoreSettings';

    /** The hook other modules use to add their own section of settings. */
    public const HOOK_SECTIONS = 'actionM4pCoreSections';

    /** Runs after the core writes a product image and its thumbnails: upload, import, thumbnail regeneration. */
    public const IMAGE_HOOKS = ['actionWatermark'];

    public const FRONT_HOOKS = ['displayHeader', 'filterCmsContent', 'actionObjectCmsAddAfter', 'actionObjectCmsUpdateAfter', 'actionFrontControllerSetVariables', 'displayM4pPrice', 'actionFrontControllerDetectContextCountryAfter'];

    public const CMS_FORM_HOOKS = [
        'actionCmsPageFormBuilderModifier',
        'actionAfterCreateCmsPageFormHandler',
        'actionAfterUpdateCmsPageFormHandler',
        'actionObjectCmsDeleteAfter',
    ];

    public function __construct()
    {
        $this->name = 'm4p_core';
        $this->tab = 'administration';
        $this->version = '1.25.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('M4P Core', [], 'Modules.M4pcore.Admin');
        $this->description = $this->trans(
            'One place for the settings the Modules4Presta modules share: company data, measurement, addresses and the look of the theme. Every module works without it and uses its own values when it is not installed.',
            [],
            'Modules.M4pcore.Admin'
        );
    }

    public function install(): bool
    {
        if (!parent::install() || !$this->installHook() || !$this->installTabs() || !$this->installFront()
            || !$this->registerHook(self::IMAGE_HOOKS)) {
            return false;
        }

        Configuration::updateGlobalValue(M4pCoreSettings::SCHEMA_KEY, M4pCoreSettings::SCHEMA);

        return true;
    }

    /**
     * Removes the pages and the marker other modules read, and leaves the stored
     * values alone: they belong to the shop, and a module reinstalled later finds
     * them where they were.
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        $this->uninstallTabs();
        $this->uninstallHook();
        Configuration::deleteByName(M4pCoreSettings::SCHEMA_KEY);

        return true;
    }

    /**
     * Hooks and tables of the front and the CMS page form; also run by the upgrades.
     *
     * @return bool
     */
    public function installFront(): bool
    {
        return $this->registerHook(array_merge(self::FRONT_HOOKS, self::CMS_FORM_HOOKS))
            && M4pCoreCmsDates::install()
            && M4pCoreCmsLayout::install();
    }

    /**
     * Template variables of the M4P theme ($m4p_shop, $m4p_header, $priceDisplay, $m4p_order_tones, $cms_toc on CMS
     * pages); the hook runs for every front page and for the AJAX refresh of the mini cart.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionFrontControllerSetVariables(array $params): void
    {
        M4pCoreUnits::registerModifier($this->context->smarty);
        $controller = $this->context->controller;
        (new M4pCoreFrontVariables($this->context))->assign($controller instanceof FrontController ? (string) $controller->php_self : null);
        if ($controller instanceof CmsController) {
            M4pCoreCmsToc::assign($this->context);
        }
        if ($controller instanceof ProductListingFrontController) {
            $perPage = (int) Tools::getValue('resultsPerPage');
            $this->context->smarty->assign('m4p_listing', [
                'per_page' => $perPage > 0 ? $perPage : (int) Configuration::get('PS_PRODUCTS_PER_PAGE'),
                'per_page_options' => [8, 12, 24, 48],
            ]);
        }
        if ($controller instanceof FrontController && in_array($controller->php_self, ['search', 'pagenotfound'], true)) {
            $this->context->smarty->assign('m4p_bestsellers', M4pCoreBestsellers::presented($this->context, 4));
        }
        if ($controller instanceof ProductController) {
            $this->context->smarty->assign('m4p_product', M4pCoreProductView::of($this->context, (int) Tools::getValue('id_product')));
        }
        // the one-page checkout re-renders its payment options after a carrier change (ps_onepagecheckout SetCarrier)
        $isCheckoutModule = $controller instanceof ModuleFrontController && $controller->module instanceof Module && $controller->module->name === 'ps_onepagecheckout';
        if ($controller instanceof CartController || $controller instanceof OrderController || $isCheckoutModule) {
            $this->context->smarty->assign('m4p_cart', M4pCoreCartView::of($this->context));
        }
        if ($controller instanceof OrderConfirmationController) {
            $this->context->smarty->assign('m4p_order', M4pCoreOrderView::of($this->context, (int) Tools::getValue('id_cart')));
        }
        if ($controller instanceof OrderConfirmationController || $controller instanceof OrderController || $isCheckoutModule) {
            $this->context->smarty->assign('m4p_methods', M4pCoreMethodIcons::forFront($this->context));
        }
        if ($controller instanceof MyAccountController) {
            $this->context->smarty->assign('m4p_account', M4pCoreAccountView::of($this->context));
        }
        if ($controller instanceof OrderDetailController) {
            $order = new Order((int) Tools::getValue('id_order'));
            if (Validate::isLoadedObject($order) && (int) $order->id_customer === (int) $this->context->customer->id) {
                $this->assignOrderDetail($order);
            }
        }
        if ($controller instanceof GuestTrackingController && Tools::getValue('order_reference') && Tools::getValue('email')) {
            // The same check as the core's guest tracking: order number and the customer's e-mail.
            $order = Order::getByReferenceAndEmail(current(explode('#', (string) Tools::getValue('order_reference'))), (string) Tools::getValue('email'));
            if (Validate::isLoadedObject($order)) {
                $this->assignOrderDetail($order);
            }
        }
        if ($controller instanceof HistoryController) {
            $this->context->smarty->assign('m4p_history', M4pCoreOrderHistory::of(
                $this->context,
                (string) Tools::getValue('status'),
                (string) Tools::getValue('q'),
                (int) Tools::getValue('page', 1)
            ));
        }
        if ($controller instanceof ModuleFrontController && get_class($controller) === 'BlockWishlistListsModuleFrontController') {
            $this->context->smarty->assign('m4p_wishlists', M4pCoreWishlistView::of($this->context));
        }
        if ($controller instanceof DiscountController) {
            $this->context->smarty->assign('m4p_discounts', M4pCoreDiscountView::of($this->context));
        }
        if ($controller instanceof OrderSlipController) {
            $this->context->smarty->assign('m4p_slips', M4pCoreSlipView::of($this->context));
        }
        if ($controller instanceof CategoryController) {
            $this->context->smarty->assign('m4p_category', M4pCoreCategoryView::of($this->context, (int) Tools::getValue('id_category')));
        }
    }

    /**
     * The account's order page: the order view of the confirmation ($m4p_order) and, while a bank transfer is still to
     * be paid, the bank details of ps_wirepayment ($m4p_order_transfer); the caller has checked the order is the visitor's.
     */
    private function assignOrderDetail(Order $order): void
    {
        $this->context->smarty->assign('m4p_order', M4pCoreOrderView::of($this->context, (int) $order->id_cart));
        $toPay = in_array((int) $order->getCurrentState(), M4pCoreOrderHistory::states(M4pCoreOrderHistory::TO_PAY), true);
        $wire = (int) Module::getModuleIdByName('ps_wirepayment');
        $transfer = $toPay && $order->module === 'ps_wirepayment' && $wire
            ? (string) Hook::exec('displayPaymentReturn', ['order' => $order], $wire)
            : '';
        $this->context->smarty->assign('m4p_order_transfer', $transfer);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionFrontControllerDetectContextCountryAfter(array $params): void
    {
        M4pCoreCheckoutCountry::apply($this->context, $params['controller'] ?? null);
    }

    /**
     * Fits the original and the thumbnails of the product image in the size limit (Images section).
     *
     * @param array<string, mixed> $params
     */
    public function hookActionWatermark(array $params): void
    {
        $idImage = (int) ($params['id_image'] ?? 0);
        $over = M4pCoreImageLimit::apply($idImage);
        if ($over) {
            PrestaShopLogger::addLog(
                'm4p_core: product image files above the size limit at the lowest quality: ' . implode(', ', array_map('basename', $over)),
                2,
                null,
                'Image',
                $idImage,
                true
            );
        }
    }

    /**
     * Second price line of a product or cart line: {hook h='displayM4pPrice' product=$product tax=true}.
     *
     * @param array<string, mixed> $params
     */
    public function hookDisplayM4pPrice(array $params): string
    {
        return (new M4pCoreFrontVariables($this->context))->price($params);
    }

    public function hookDisplayHeader(): string
    {
        M4pCoreUnits::registerModifier($this->context->smarty);
        $tabBlurTitle = (string) M4pCoreSettings::get('TAB_BLUR_TITLE', '');
        if ($tabBlurTitle !== '') {
            Media::addJsDef(['m4pTabBlurTitle' => $tabBlurTitle]);
        }
        $this->context->smarty->assign('m4p_logo_mark', M4pCoreLogoMark::url());

        return M4pCoreTheme::head() . M4pCoreMotion::head();
    }

    /**
     * Adds `date_upd`, the date of the last content change, and `layout`, the layout
     * chosen in the page form, to the page the theme renders.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function hookFilterCmsContent(array $params): array
    {
        $object = $params['object'];
        $idCms = (int) ($object['id'] ?? $object['id_cms'] ?? 0);
        if ($idCms > 0) {
            $object['date_upd'] = M4pCoreCmsDates::of($idCms);
            $object['layout'] = M4pCoreCmsLayout::of($idCms);
        }

        return ['object' => $object];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionObjectCmsAddAfter(array $params): void
    {
        M4pCoreCmsDates::track($params['object']);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionObjectCmsUpdateAfter(array $params): void
    {
        M4pCoreCmsDates::track($params['object']);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionObjectCmsDeleteAfter(array $params): void
    {
        M4pCoreCmsLayout::delete((int) $params['object']->id);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionCmsPageFormBuilderModifier(array $params): void
    {
        M4pCoreCmsLayout::addField($this, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionAfterCreateCmsPageFormHandler(array $params): void
    {
        M4pCoreCmsLayout::save((int) $params['id'], (string) ($params['form_data'][M4pCoreCmsLayout::FIELD] ?? ''));
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookActionAfterUpdateCmsPageFormHandler(array $params): void
    {
        $this->hookActionAfterCreateCmsPageFormHandler($params);
    }

    /**
     * Creates the hook row, so another module can register for it.
     *
     * @return bool
     */
    public function installHook(): bool
    {
        if ((int) Hook::getIdByName(self::HOOK_SECTIONS) > 0) {
            return true;
        }

        $hook = new Hook();
        $hook->name = self::HOOK_SECTIONS;
        $hook->title = 'M4P Core: sections of shared settings';
        $hook->description = 'Added by the m4p_core module.';

        return (bool) $hook->add();
    }

    /**
     * Removes the hook row, and only when no other module is registered for it:
     * the row is shared and another module may still need it.
     *
     * @return bool
     */
    public function uninstallHook(): bool
    {
        $idHook = (int) Hook::getIdByName(self::HOOK_SECTIONS);
        if ($idHook === 0) {
            return true;
        }

        $used = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'hook_module` WHERE `id_hook` = ' . $idHook,
            false
        );

        if ($used > 0) {
            return true;
        }

        $hook = new Hook($idHook);

        return (bool) $hook->delete();
    }

    /**
     * The menu of the module: one entry in the back office with two pages below it.
     *
     * @return bool
     */
    public function installTabs(): bool
    {
        $pages = [
            self::TAB_PARENT => ['M4P', 0, 'extension'],
            self::TAB_DASHBOARD => [$this->trans('Overview', [], 'Modules.M4pcore.Admin'), self::TAB_PARENT, 'dashboard'],
            self::TAB_SETTINGS => [$this->trans('Shared settings', [], 'Modules.M4pcore.Admin'), self::TAB_PARENT, 'settings'],
        ];

        foreach ($pages as $className => $page) {
            [$name, $parent, $icon] = $page;

            if (Tab::getIdFromClassName($className)) {
                continue;
            }

            $tab = new Tab();
            $tab->class_name = $className;
            $tab->module = $this->name;
            $tab->id_parent = $parent === 0 ? 0 : (int) Tab::getIdFromClassName($parent);
            $tab->active = 1;
            $tab->icon = $icon;

            foreach (Language::getLanguages(false) as $language) {
                $tab->name[(int) $language['id_lang']] = $name;
            }

            if (!$tab->add()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function uninstallTabs(): bool
    {
        foreach ([self::TAB_SETTINGS, self::TAB_DASHBOARD, self::TAB_PARENT] as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab === 0) {
                continue;
            }

            $tab = new Tab($idTab);
            $tab->delete();
        }

        return true;
    }

    /** The module's own page points at the two pages in the menu. */
    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('m4p_core_repair')) {
            $done = $this->installHook() && $this->installTabs() && $this->installFront();
            Configuration::updateGlobalValue(M4pCoreSettings::SCHEMA_KEY, M4pCoreSettings::SCHEMA);
            $output .= $done
                ? $this->displayConfirmation($this->trans('The pages are in place.', [], 'Modules.M4pcore.Admin'))
                : $this->displayError($this->trans('The pages could not be added.', [], 'Modules.M4pcore.Admin'));
        }

        $this->context->smarty->assign([
            'm4p_core_dashboard' => $this->pageLink(self::TAB_DASHBOARD),
            'm4p_core_settings' => $this->pageLink(self::TAB_SETTINGS),
            'm4p_core_sections' => M4pCoreSections::all($this),
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function pageLink(string $className): string
    {
        return (int) Tab::getIdFromClassName($className) > 0
            ? $this->context->link->getAdminLink($className)
            : '';
    }

    /**
     * Makes `trans()` reachable from the classes that build the sections.
     *
     * @param string $id
     * @param array<int|string, mixed> $parameters
     * @param string|null $domain
     * @param string|null $locale
     *
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return parent::trans($id, $parameters, $domain, $locale);
    }
}
