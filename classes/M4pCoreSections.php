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
 * The sections of settings the back office shows. The module ships the ones every
 * shop needs; any other module adds its own on the `actionM4pCoreSections` hook,
 * which is what makes this a place to put shared settings rather than a fixed
 * list.
 */
class M4pCoreSections
{
    /**
     * @param Module $module for translating the built-in sections
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(Module $module): array
    {
        $sections = self::builtIn($module) + M4pCoreTheme::sections($module);

        Hook::exec('actionM4pCoreSections', ['sections' => &$sections]);

        return self::clean($sections);
    }

    /**
     * @param Module $module
     *
     * @return array<string, array<string, mixed>>
     */
    private static function builtIn(Module $module): array
    {
        return [
            'shop_mode' => [
                'title' => $module->trans('Sales model', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Who the shop sells to. Companies see net prices after logging in, a company account and the minimum order. Consumers see gross prices without logging in and a personal account.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-users',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'SHOP_MODE', 'label' => $module->trans('The shop sells to', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => M4pCoreShopMode::B2C, 'options' => [
                            M4pCoreShopMode::B2B => $module->trans('Companies (B2B)', [], 'Modules.M4pcore.Admin'),
                            M4pCoreShopMode::B2C => $module->trans('Consumers (B2C)', [], 'Modules.M4pcore.Admin'),
                            M4pCoreShopMode::MIXED => $module->trans('Companies and consumers', [], 'Modules.M4pcore.Admin'),
                        ]],
                    ['key' => 'B2B_GROUPS', 'label' => $module->trans('Company customer groups', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => implode(',', M4pCoreShopMode::defaultCompanyGroups()), 'options' => self::customerGroups(),
                        'hint' => $module->trans('With "Companies and consumers": customers in these groups see the company view, everyone else the consumer view.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'company' => [
                'title' => $module->trans('Company data', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Shown by the modules that print contact details, and used in structured data.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-building',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'COMPANY_NAME', 'label' => $module->trans('Legal name', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_ADDRESS', 'label' => $module->trans('Address', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea'],
                    ['key' => 'COMPANY_TAX_ID', 'label' => $module->trans('Tax number', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_KRS', 'label' => $module->trans('Court register number (KRS)', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_COURT', 'label' => $module->trans('Registry court', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_CAPITAL', 'label' => $module->trans('Share capital', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('With the currency, for example 50 000 zł', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'COMPANY_VET', 'label' => $module->trans('Veterinary approval number', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'COMPANY_PHONE', 'label' => $module->trans('Phone', [], 'Modules.M4pcore.Admin'), 'type' => 'tel'],
                    ['key' => 'COMPANY_EMAIL', 'label' => $module->trans('E-mail', [], 'Modules.M4pcore.Admin'), 'type' => 'email'],
                    ['key' => 'COMPANY_HOURS', 'label' => $module->trans('Opening hours', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('For example 7:30–16:00', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'PRIVACY_CMS', 'label' => $module->trans('Privacy policy page', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'options' => self::cmsPages($module),
                        'hint' => $module->trans('Linked in the checkout footer and next to the terms consent.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'sales' => [
                'title' => $module->trans('Sales department', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The phone, hours and e-mail in the strip above the header, in the footer and in the contact widget.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-phone',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'SALES_PHONE', 'label' => $module->trans('Phone', [], 'Modules.M4pcore.Admin'), 'type' => 'tel',
                        'hint' => $module->trans('Empty: the phone from the shop contact details.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'SALES_HOURS', 'label' => $module->trans('Hours', [], 'Modules.M4pcore.Admin'), 'type' => 'week',
                        'hint' => $module->trans('For example 7:30-16:00. An empty day is closed.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'SALES_EMAIL', 'label' => $module->trans('E-mail', [], 'Modules.M4pcore.Admin'), 'type' => 'email',
                        'hint' => $module->trans('Empty: the shop e-mail.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'footer' => [
                'title' => $module->trans('Footer', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The logo, payments and delivery in the footer. The link columns are edited in Link List, the company line in Company data.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-list-alt',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'LOGO_LIGHT', 'label' => $module->trans('Light logo', [], 'Modules.M4pcore.Admin'), 'type' => 'file',
                        'accept' => '.png,.webp,.jpg,.jpeg', 'store' => 'logo_light',
                        'hint' => $module->trans('PNG or WebP with a transparent background, about 360 × 80 px. Without it the footer shows the shop name.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'LOGO_LIGHT_REMOVE', 'label' => $module->trans('Remove the light logo', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 0],
                    ['key' => 'FOOTER_PAYMENTS', 'label' => $module->trans('Payment methods', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea',
                        'hint' => $module->trans('One per line. Empty: the group is hidden.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'FOOTER_DELIVERY', 'label' => $module->trans('Delivery', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('For example: Own transport', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'FOOTER_PICKUP', 'label' => $module->trans('Pickup', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('For example: Personal pickup. Empty: not shown.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'measurement' => [
                'title' => $module->trans('Measurement', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Identifiers and switches the modules use when they report what visitors do.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-bar-chart',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'TRACK_GTM_ID', 'label' => $module->trans('Google Tag Manager container', [], 'Modules.M4pcore.Admin'),
                        'type' => 'text', 'placeholder' => 'GTM-XXXXXXX'],
                    ['key' => 'TRACK_EVENTS', 'label' => $module->trans('Report events', [], 'Modules.M4pcore.Admin'),
                        'type' => 'switch', 'default' => 1],
                    ['key' => 'TRACK_UTM_SOURCE', 'label' => $module->trans('utm_source for outbound links', [], 'Modules.M4pcore.Admin'),
                        'type' => 'text'],
                ],
            ],
            'links' => [
                'title' => $module->trans('Addresses', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans('Where the modules send a visitor who wants to know more.', [], 'Modules.M4pcore.Admin'),
                'icon' => 'icon-link',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'LINK_SHOP', 'label' => $module->trans('Module shop', [], 'Modules.M4pcore.Admin'), 'type' => 'url',
                        'placeholder' => 'https://'],
                    ['key' => 'LINK_SUPPORT', 'label' => $module->trans('Support page', [], 'Modules.M4pcore.Admin'), 'type' => 'url',
                        'placeholder' => 'https://'],
                ],
            ],
            'newsletter' => [
                'title' => $module->trans('Newsletter', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans('What the theme shows next to the newsletter sign-up.', [], 'Modules.M4pcore.Admin'),
                'icon' => 'icon-envelope',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'NEWSLETTER_RULES_CMS', 'label' => $module->trans('Page with the sending and unsubscribing rules', [], 'Modules.M4pcore.Admin'),
                        'type' => 'select', 'options' => self::cmsPages($module),
                        'hint' => $module->trans('Linked under the consent as "Sending and unsubscribing".', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'NEWSLETTER_TITLE', 'label' => $module->trans('Title', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('Empty: "News and offers by email".', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'NEWSLETTER_TEXT', 'label' => $module->trans('Text under the title', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea',
                        'hint' => $module->trans('What the customer gets and how often. Empty: the standard text.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'NEWSLETTER_IMAGE', 'label' => $module->trans('Photo', [], 'Modules.M4pcore.Admin'), 'type' => 'file',
                        'accept' => '.png,.webp,.jpg,.jpeg', 'store' => 'newsletter_image',
                        'hint' => $module->trans('WebP or JPG, about 1280 × 724 px, up to 1 MB. Shown next to the form; without it the form takes the whole width.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'NEWSLETTER_IMAGE_REMOVE', 'label' => $module->trans('Remove the photo', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 0],
                ],
            ],
            'delivery' => [
                'title' => $module->trans('Delivery', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The order cut-off and the delivery days shown in the header bar, the cart and on the home page. An order placed by the cut-off arrives on the next delivery day.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-truck',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'ORDER_CUTOFF', 'label' => $module->trans('Order cut-off', [], 'Modules.M4pcore.Admin'), 'type' => 'time',
                        'default' => '14:00', 'placeholder' => '14:00',
                        'hint' => $module->trans('Hours and minutes, for example 14:00', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'DELIVERY_DAYS', 'label' => $module->trans('Delivery days', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => '1,2,3,4,5', 'options' => self::weekdays($module)],
                ],
            ],
            'header' => [
                'title' => $module->trans('Header', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The strip above the header, the low header after scrolling and the category panel.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-columns',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'HEADER_STRIP', 'label' => $module->trans('Strip above the header', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 1],
                    ['key' => 'HEADER_STRIP_GUEST', 'label' => $module->trans('Strip text for visitors', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('{cutoff} shows the order cut-off from the Delivery section. Empty: the standard text. Logged-in customers see the delivery day instead.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'HEADER_STRIP_B2C', 'label' => $module->trans('Strip text in the consumer view', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('{cutoff} shows the order cut-off. Empty: the standard text without net prices.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'HEADER_COMPACT', 'label' => $module->trans('Low header after scrolling', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 1,
                        'hint' => $module->trans('On phones. On computers the header stays the same after scrolling.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'HEADER_SCRIM', 'label' => $module->trans('Dim the page under the category panel', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 1],
                    ['key' => 'MEGA_PROMO', 'label' => $module->trans('Recommended product in the category panel', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 1],
                    ['key' => 'CATEGORY_TILES', 'label' => $module->trans('Subcategory tiles on the category page', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 4, 'options' => [0 => '0', 2 => '2', 3 => '3', 4 => '4', 6 => '6', 8 => '8'],
                        'hint' => $module->trans('The subcategories with the most products. The others are in the category tree. 0: no tiles.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'PACK_FEATURE', 'label' => $module->trans('Product feature with the pack size', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::features($module),
                        'hint' => $module->trans('Shown next to the reference in the mini cart.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'LOGO_MARK', 'label' => $module->trans('Logo sign for phones and the low header', [], 'Modules.M4pcore.Admin'), 'type' => 'file',
                        'accept' => '.png,.webp,.jpg,.jpeg', 'store' => 'logo_mark',
                        'hint' => $module->trans('PNG, WebP or JPG, about 80 × 68 px. Without it the normal logo is shown.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'LOGO_MARK_REMOVE', 'label' => $module->trans('Remove the logo sign', [], 'Modules.M4pcore.Admin'), 'type' => 'switch', 'default' => 0],
                ],
            ],
            'home_featured' => [
                'title' => $module->trans('Home page: Featured', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The text tile that opens the Featured row. Leave the title and the description empty to hide it.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-star',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'FEATURED_TILE_KICKER', 'label' => $module->trans('Label above the title', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('For example the month: December.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'FEATURED_TILE_TITLE', 'label' => $module->trans('Title', [], 'Modules.M4pcore.Admin'), 'type' => 'text'],
                    ['key' => 'FEATURED_TILE_TEXT', 'label' => $module->trans('Description', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea',
                        'hint' => $module->trans('One or two sentences: why these products now.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'FEATURED_TILE_LINK_TEXT', 'label' => $module->trans('Link text', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('Empty: no link.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'FEATURED_TILE_LINK_URL', 'label' => $module->trans('Link address', [], 'Modules.M4pcore.Admin'), 'type' => 'url',
                        'hint' => $module->trans('Empty: the same page as "View all" of the section.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'cart' => [
                'title' => $module->trans('Cart', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'What the customer sees right after adding a product to the cart, on the product page and on every product card.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-shopping-cart',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'CART_AFTER_ADD', 'label' => $module->trans('After adding to the cart', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 'minicart', 'options' => [
                            'minicart' => $module->trans('Open the mini cart', [], 'Modules.M4pcore.Admin'),
                            'modal' => $module->trans('Window with a summary', [], 'Modules.M4pcore.Admin'),
                        ],
                        'hint' => $module->trans('The mini cart opens at the cart icon (on phones from the bottom) and closes by itself after a few seconds.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'motion' => M4pCoreMotion::section($module),
            'product_page' => [
                'title' => $module->trans('Product page', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Product features shown at the top of the product page: the origin under the name, the sale unit after the price and up to three facts with an icon. A feature shown here is left out of the Specifications.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-file-text',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => M4pCoreProductView::ORIGIN, 'label' => $module->trans('Feature with the origin', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module),
                        'hint' => $module->trans('Shown with a pin next to the reference, under the product name.', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::UNIT, 'label' => $module->trans('Feature with the sale unit', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module),
                        'hint' => $module->trans('Shown after the price, for example / kg. With kg and a product weight the page also gives the price of one pack.', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::GUEST_TEXT, 'label' => $module->trans('Text for visitors instead of the price', [], 'Modules.M4pcore.Admin'), 'type' => 'textarea',
                        'hint' => $module->trans('Under "Net prices after logging in", above the login buttons. Empty: the standard text.', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::factKey(1), 'label' => $module->trans('Fact 1: feature', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module)],
                    ['key' => M4pCoreProductView::iconKey(1), 'label' => $module->trans('Fact 1: icon', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 'info', 'options' => array_combine(M4pCoreProductView::ICONS, M4pCoreProductView::ICONS)],
                    ['key' => M4pCoreProductView::factKey(2), 'label' => $module->trans('Fact 2: feature', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module)],
                    ['key' => M4pCoreProductView::iconKey(2), 'label' => $module->trans('Fact 2: icon', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 'info', 'options' => array_combine(M4pCoreProductView::ICONS, M4pCoreProductView::ICONS)],
                    ['key' => M4pCoreProductView::factKey(3), 'label' => $module->trans('Fact 3: feature', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module)],
                    ['key' => M4pCoreProductView::iconKey(3), 'label' => $module->trans('Fact 3: icon', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 'info', 'options' => array_combine(M4pCoreProductView::ICONS, M4pCoreProductView::ICONS)],
                ],
            ],
            'product_content' => [
                'title' => $module->trans('Product page: content cards', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Product features shown in the cards under the gallery: Composition and allergens, and the Nutrition table. Every other feature that the top of the page does not show goes to the Logistics card. A card without data for a product is not shown.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-list',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => M4pCoreProductView::INGREDIENTS, 'label' => $module->trans('Feature with the ingredients', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module)],
                    ['key' => M4pCoreProductView::ALLERGENS, 'label' => $module->trans('Feature with the allergens', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module),
                        'hint' => $module->trans('Allergens separated with commas; each one is highlighted as a label.', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::FREE_FROM, 'label' => $module->trans('Feature with "Free from"', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module)],
                    ['key' => M4pCoreProductView::NUTRITION_BASIS, 'label' => $module->trans('Feature with the nutrition basis', [], 'Modules.M4pcore.Admin'), 'type' => 'select',
                        'default' => 0, 'options' => self::featureChoices($module),
                        'hint' => $module->trans('For example 100 g: the table reads "Nutrition per 100 g".', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::NUTRITION, 'label' => $module->trans('Rows of the nutrition table', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => '', 'optional' => true, 'options' => self::featureList(),
                        'hint' => $module->trans('In the order of the features (Catalog › Attributes & Features › Features).', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreProductView::NUTRITION_SUB, 'label' => $module->trans('Indented rows ("of which")', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => '', 'optional' => true, 'options' => self::featureList()],
                ],
            ],
            'method_icons' => [
                'title' => $module->trans('Payment and delivery methods', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The icon next to every carrier and payment method in the checkout and on the order confirmation, and the name and the line under it of every payment method in the checkout. Without a choice the icon follows the carrier name or the payment module.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-truck',
                'owner' => 'm4p_core',
                'fields' => self::methodIconFields($module),
            ],
            'account_orders' => [
                'title' => $module->trans('Customer account: orders', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The "To pay" and "In progress" filters of the order list in the customer account. Without a choice the statuses follow their settings in Orders › Statuses.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-list',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => M4pCoreOrderHistory::KEY_TO_PAY, 'label' => $module->trans('Statuses "To pay"', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => M4pCoreOrderHistory::defaultStates(M4pCoreOrderHistory::TO_PAY), 'optional' => true, 'options' => M4pCoreOrderHistory::stateChoices(),
                        'hint' => $module->trans('Orders the customer still has to pay for. An order waiting for a bank transfer also gets the "Transfer details" link.', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreOrderHistory::KEY_IN_PROGRESS, 'label' => $module->trans('Statuses "In progress"', [], 'Modules.M4pcore.Admin'), 'type' => 'days',
                        'default' => M4pCoreOrderHistory::defaultStates(M4pCoreOrderHistory::IN_PROGRESS), 'optional' => true, 'options' => M4pCoreOrderHistory::stateChoices(),
                        'hint' => $module->trans('Orders accepted and not yet delivered. Delivered, cancelled and refunded orders show only under "All".', [], 'Modules.M4pcore.Admin')],
                    ['key' => M4pCoreOrderHistory::KEY_PER_PAGE, 'label' => $module->trans('Orders per page', [], 'Modules.M4pcore.Admin'), 'type' => 'number',
                        'default' => M4pCoreOrderHistory::DEFAULT_PER_PAGE, 'min' => 5, 'max' => 100],
                ],
            ],
            'images' => [
                'title' => $module->trans('Product images', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'Applied to every product photo after it is uploaded or its thumbnails are regenerated.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-picture',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'IMAGE_MAX_KB', 'label' => $module->trans('Largest file, in KB', [], 'Modules.M4pcore.Admin'), 'type' => 'number',
                        'default' => M4pCoreImageLimit::DEFAULT_MAX_KB, 'min' => 0, 'max' => 5000,
                        'hint' => $module->trans('The original and every thumbnail are compressed until they fit. 0: no limit.', [], 'Modules.M4pcore.Admin')],
                    ['key' => 'IMAGE_MAX_PX', 'label' => $module->trans('Longest side of the original, in pixels', [], 'Modules.M4pcore.Admin'), 'type' => 'number',
                        'default' => M4pCoreImageLimit::DEFAULT_MAX_PX, 'min' => 400, 'max' => 4000,
                        'hint' => $module->trans('Larger photos are scaled down after the thumbnails are made. Keep it at least the size of the largest image type.', [], 'Modules.M4pcore.Admin')],
                ],
            ],
            'browser_tab' => [
                'title' => $module->trans('Browser tab', [], 'Modules.M4pcore.Admin'),
                'description' => $module->trans(
                    'The title the browser tab shows while a customer is on another tab.',
                    [],
                    'Modules.M4pcore.Admin'
                ),
                'icon' => 'icon-bookmark',
                'owner' => 'm4p_core',
                'fields' => [
                    ['key' => 'TAB_BLUR_TITLE', 'label' => $module->trans('Title on another tab', [], 'Modules.M4pcore.Admin'), 'type' => 'text',
                        'hint' => $module->trans('Leave empty to keep the page title', [], 'Modules.M4pcore.Admin')],
                ],
            ],
        ];
    }

    /**
     * @return array<int, string> group id => name, without the visitor and guest groups
     */
    private static function customerGroups(): array
    {
        $skip = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP'), (int) Configuration::get('PS_GUEST_GROUP')];
        $groups = [];
        foreach (Group::getGroups((int) Context::getContext()->language->id) as $group) {
            if (!in_array((int) $group['id_group'], $skip, true)) {
                $groups[(int) $group['id_group']] = (string) $group['name'];
            }
        }

        return $groups;
    }

    /**
     * @return array<int, string> ISO weekday => name, 1 = Monday
     */
    private static function weekdays(Module $module): array
    {
        return [
            1 => $module->trans('Monday', [], 'Modules.M4pcore.Admin'),
            2 => $module->trans('Tuesday', [], 'Modules.M4pcore.Admin'),
            3 => $module->trans('Wednesday', [], 'Modules.M4pcore.Admin'),
            4 => $module->trans('Thursday', [], 'Modules.M4pcore.Admin'),
            5 => $module->trans('Friday', [], 'Modules.M4pcore.Admin'),
            6 => $module->trans('Saturday', [], 'Modules.M4pcore.Admin'),
            7 => $module->trans('Sunday', [], 'Modules.M4pcore.Admin'),
        ];
    }

    /**
     * @return array<int, string> feature id => name, with 0 for none
     */
    private static function featureChoices(Module $module): array
    {
        $features = [0 => $module->trans('None', [], 'Modules.M4pcore.Admin')];
        foreach (Feature::getFeatures((int) Context::getContext()->language->id) as $feature) {
            $features[(int) $feature['id_feature']] = (string) $feature['name'];
        }

        return $features;
    }

    /**
     * One icon field per active carrier and per installed payment module, the automatic choice as the default.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function methodIconFields(Module $module): array
    {
        $options = array_combine(M4pCoreMethodIcons::ICONS, M4pCoreMethodIcons::ICONS);
        $fields = [];
        foreach (Carrier::getCarriers((int) Context::getContext()->language->id, true, false, false, null, Carrier::ALL_CARRIERS) as $row) {
            $fields[] = ['key' => M4pCoreMethodIcons::carrierKey((int) $row['id_reference']),
                'label' => $module->trans('Carrier: %name%', ['%name%' => $row['name']], 'Modules.M4pcore.Admin'), 'type' => 'select',
                'default' => M4pCoreMethodIcons::carrierDefault((string) $row['name'], (string) $row['external_module_name']), 'options' => $options];
        }
        foreach (M4pCoreMethodIcons::paymentModules() as $name) {
            $payment = Module::getInstanceByName($name);
            $paymentName = $payment ? $payment->displayName : $name;
            $fields[] = ['key' => M4pCoreMethodIcons::paymentKey($name),
                'label' => $module->trans('Payment: %name%', ['%name%' => $paymentName], 'Modules.M4pcore.Admin'), 'type' => 'select',
                'default' => M4pCoreMethodIcons::paymentDefault($name), 'options' => $options];
            $fields[] = ['key' => M4pCoreMethodIcons::paymentNameKey($name),
                'label' => $module->trans('Name in the checkout: %name%', ['%name%' => $paymentName], 'Modules.M4pcore.Admin'), 'type' => 'text',
                'hint' => $module->trans('Leave empty to show the name of the payment module.', [], 'Modules.M4pcore.Admin')];
            $fields[] = ['key' => M4pCoreMethodIcons::paymentNoteKey($name),
                'label' => $module->trans('Line under the name: %name%', ['%name%' => $paymentName], 'Modules.M4pcore.Admin'), 'type' => 'text',
                'hint' => $module->trans('For example: bank details in the order confirmation', [], 'Modules.M4pcore.Admin')];
        }

        return $fields;
    }

    /**
     * @return array<int, string> every product feature by id, for a multiple choice
     */
    private static function featureList(): array
    {
        $features = [];
        foreach (Feature::getFeatures((int) Context::getContext()->language->id) as $feature) {
            $features[(int) $feature['id_feature']] = (string) $feature['name'];
        }

        return $features;
    }

    /**
     * @return array<int, string> feature id => name, with 0 for the feature named "Opakowanie" or "Packaging"
     */
    private static function features(Module $module): array
    {
        $features = [0 => $module->trans('The feature named "Opakowanie" or "Packaging"', [], 'Modules.M4pcore.Admin')];
        foreach (Feature::getFeatures((int) Context::getContext()->language->id) as $feature) {
            $features[(int) $feature['id_feature']] = (string) $feature['name'];
        }

        return $features;
    }

    /**
     * @return array<int, string> CMS page id => title, with 0 for no page
     */
    private static function cmsPages(Module $module): array
    {
        $pages = [0 => $module->trans('No link', [], 'Modules.M4pcore.Admin')];
        foreach (CMS::getCMSPages((int) Context::getContext()->language->id, null, true) as $page) {
            $pages[(int) $page['id_cms']] = (string) $page['meta_title'];
        }

        return $pages;
    }

    /**
     * Keeps only what the back office can render: a section needs a title and at
     * least one field, and a field needs a key and a known type. A section added
     * by another module cannot break this page by sending something else.
     *
     * @param array<int|string, mixed> $sections
     *
     * @return array<string, array<string, mixed>>
     */
    private static function clean(array $sections): array
    {
        $types = ['text', 'textarea', 'email', 'tel', 'url', 'number', 'switch', 'select', 'color', 'css', 'file', 'time', 'days', 'week'];
        $out = [];

        foreach ($sections as $id => $section) {
            $id = preg_replace('/[^a-z0-9_-]/i', '', (string) $id);
            if ($id === '' || !is_array($section) || trim((string) ($section['title'] ?? '')) === '') {
                continue;
            }

            $fields = [];
            foreach ((array) ($section['fields'] ?? []) as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $key = strtoupper(preg_replace('/[^A-Z0-9_]/i', '', (string) ($field['key'] ?? '')));
                $type = (string) ($field['type'] ?? 'text');
                if ($key === '' || !in_array($type, $types, true)) {
                    continue;
                }

                $field['key'] = $key;
                $field['type'] = $type;
                $field['label'] = trim((string) ($field['label'] ?? $key));
                $fields[] = $field;
            }

            if ($fields === []) {
                continue;
            }

            $out[$id] = [
                'title' => (string) $section['title'],
                'description' => (string) ($section['description'] ?? ''),
                'icon' => (string) ($section['icon'] ?? 'icon-cog'),
                'owner' => (string) ($section['owner'] ?? ''),
                'fields' => $fields,
            ];
        }

        return $out;
    }
}
