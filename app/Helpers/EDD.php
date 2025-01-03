<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

use EDDA\Affiliate\App\Services\Database;
use EDDA\Affiliate\App\Services\Settings;

class EDD
{
    public static function saveDiscount($discountData, $discountId = null)
    {

        // Check if Easy Digital Downloads is active
        if (!function_exists('edd_add_discount')) {
            return false;
        }

        // Prepare the discount data
        $data = [
            'name'              => $discountData['name'], // Discount name
            'code'              => $discountData['code'], // Discount code
            'status'            => $discountData['status'] ?? 'active', // Default to active if not provided
            'type'              => $discountData['amount_type'], // Discount type (e.g., 'percent' or 'flat')
            'amount'            => $discountData['amount'], // Discount amount
            'max_uses'          => $discountData['max_uses'] ?? null, // Max usage limit
            'start'             => !empty($discountData['start_date']) ? $discountData['start_date'] : null,
            'expiration'        => !empty($discountData['end_date']) ? $discountData['expiry_date'] : null,
            'min_price'         => $discountData['min_charge_amount'] ?? 0, // Minimum cart amount
            'product_reqs'      => $discountData['product_reqs'] ?? [], // Required products
            'once_per_customer' => $discountData['once_per_customer'] ?? 0,
            'product_condition' => 'all', // Use 'all' or 'any' based on your requirement
            'excluded_products' => $discountData['excluded_products'] ?? [],
            'scope'             => $discountData['scope'] // Excluded products
        ];
        // Update existing discount or create a new one
        $discount_id = edd_add_discount($data);

        // Check if the discount was saved successfully
        if (!$discount_id) {
            return false;
        }

        // Add metadata to the discount
        if (!empty($discountData['meta_data']) && is_array($discountData['meta_data'])) {
            foreach ($discountData['meta_data'] as $key => $value) {
                edd_add_adjustment_meta($discount_id, $key, $value);
            }
        }

        return $discount_id;
    }


    public static function getProductNameWithID($product_id, $prefix = '#')
    {
        return $prefix . $product_id . ' ' . html_entity_decode(get_the_title($product_id));
    }

    public static function getTerms(array $args = []): array
    {
        $args = array_merge([
            'search' => '',
            'taxonomy' => '',
            'hide_empty' => false,
        ], $args);

        $terms = get_terms($args);

        return is_array($terms) ? $terms : [];
    }

    public static function getCategoryParent($category_id): string
    {
        if (empty($category_id) || $category_id < 0) return '';

        $category = WC::getCategory($category_id);
        if (is_object($category)) return '';

        $label = !empty($category->parent) ? self::getCategoryParent($category->parent) . ' -> ' : '';
        $label .= !empty($category->term_id) ? $category->name : '';
        return $label;
    }


    public static function getCategory($category_id)
    {
        return self::getTerm($category_id, 'product_cat');
    }


    public static function getTerm($term_id, string $taxonomy = '')
    {
        if (!is_numeric($term_id) || $term_id <= 0) return false;
        $term = get_term($term_id, $taxonomy);

        return is_object($term) ? $term : false;
    }

    public static function getIdsFromLabelsArray($items)
    {
        if (!is_array($items)) return [];

        $ids = [];
        foreach ($items as $item) {
            if (isset($item['value'])) {
                $ids[] = $item['value'];
            }
        }
        return $ids;
    }

    public static function getCountryWithLabel($countryCode)
    {
        if(!defined( 'WC_VERSION' )) return $countryCode;
        if (!$countryCode) return null;

        $wc_countries = new WC_Countries();

        // Define the country code you want to retrieve details for
        $country_code = $countryCode; // Replace with the desired country code

        $countries = $wc_countries->get_countries();

        // Check if the country code exists in the list
        if (isset($countries[$country_code])) {

            return [
                'value' => $country_code,
                'label' => $countries[$country_code]
            ];
            // Add more details as needed
        } else {
            return [];
        }
    }

    public static function getStateWithLabel($countryCode, $stateCode)
    {
        $states = WC::getStates($countryCode);

        if (isset($states[$stateCode])) {

            return [
                'value' => $stateCode,
                'label' => $states[$stateCode]
            ];
            // Add more details as needed
        } else {
            return [];
        }
    }

    public static function getEDDCurrencySymbol($currencyCode = '')
    {
        if (empty($currencyCode) && function_exists('edd_get_currency')) {
            $currencyCode = edd_get_currency();
        }
        $currency = edd_currency_symbol($currencyCode);
        return html_entity_decode($currency);
    }

    static function getSession($key, $default = NULL)
    {
        if (static::isWCSessionLoaded() && Util::isMethodExists(WC()->session, 'get')) {
            return WC()->session->get($key, $default);
        }
        return $default;
    }

    /**
     * set the session value by key
     * @param $key
     * @param $value mixed
     */
    static function setSession($key, $value)
    {
        if (static::isWCSessionLoaded() && Util::isMethodExists(WC()->session, 'set')) {
            WC()->session->set($key, $value);
        }
    }

    static function isWCSessionLoaded()
    {
        return function_exists('WC') && isset(WC()->session) && WC()->session != null;
    }

    public static function getAppliedCouponsforOrder($order_id)
    {
        if (!is_object($order_id) && empty($order_id)) {
            $order = wc_get_order($order_id);
        } else {
            $order = $order_id;
        }

        // Get applied coupons from the order
        $applied_coupons = $order->get_coupon_codes();

        return $applied_coupons;
    }

    public static function getTotalPrice(\WC_Order $order)
    {
        $excludeShipping = (bool)Settings::get('general_settings.commission_settings.exclude_shipping', false);
        $excludeTaxes = (bool)Settings::get('general_settings.commission_settings.exclude_taxes', false);

        //with tax and with shipping
        $orderTotal = $order->get_total();

        if ($excludeTaxes) {
            $orderTotal -= $order->get_total_tax();
        }

        if ($excludeShipping) {
            $orderTotal -= $order->get_shipping_total();
            $orderTotal -= $order->get_shipping_tax();
        }

        return apply_filters('rwpa_get_total_price_of_the_order', $orderTotal);
    }

    public static function removeCoupon(string $code): bool
    {
        if(!WC::isWoocommerceIntsalled()){
            return $code;
        }
        return function_exists('WC') && isset(WC()->cart)
            && Util::isMethodExists(WC()->cart, 'remove_coupon') && WC()->cart->remove_coupon($code);
    }

    public static function isCouponExists(string $coupon_code)
    {
        global $wpdb;
        $coupon = Database::table($wpdb->posts)
            ->select("ID, post_status")
            ->where("post_type = %s AND post_title = %s", ['shop_coupon', $coupon_code])
            ->first();

        // If coupon is found, check if it's in trash or not
        if ($coupon) {
            return $coupon;
        }

        return false; // Coupon does not exist    }    }
    }

    public static function getAffilateEndPoint()
    {
        if(function_exists('wc_get_account_endpoint_url') && !WC::isWoocommerceIntsalled())
        {
            $endpoint_url=wc_get_account_endpoint_url('relay-affiliate-marketing');
            return $endpoint_url;
        }
        return '';
    }

    public static function getStoreName()
    {
        return get_bloginfo('name');
    }

    public static function getAdminDashboard()
    {
        $pluginSlug = RWPA_PLUGIN_SLUG;

        return admin_url("admin.php?page={$pluginSlug}#");
    }

    public static function getStates($countryCode)
    {
        if(!WC::isWoocommerceIntsalled()){
            return $countryCode;
        }
        if (empty($countryCode)) return [];

        $woo_countries = new WC_Countries();

        $states = $woo_countries->get_states($countryCode);

        if (empty($states)) return [];

        return $states;
    }

    public static function getOrderEditUrl($order_id)
    {
        return null;
        get_edit_post_link($order_id, '');
    }

    public static function getEDDCurrencySymbolCode($code = 'USD')
    {
        if (function_exists('edd_currency_symbol')) {
            return html_entity_decode(edd_currency_symbol($code));
        }

        return '$'; // Default to USD if EDD is not active or the function does not exist
    }

    public static function getCurrencyList()
    {
        if (function_exists('edd_get_currencies')) {
            return edd_get_currencies();
        }
        return [];
    }

    public static function isHPOSEnabled()
    {
        if (! class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return false;
        }

        if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            return true;
        }

        return false;
    }

    public static function getDefaultCurrency()
    {
        if (function_exists('edd_get_currency')) {
            return edd_get_currency();
        }
        return '';
    }

    public static function isWoocommerceIntsalled() : bool{
        if (class_exists('EDD') && function_exists('WC')) {
            return true;
        }
        return false;
    }
}
