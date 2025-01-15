<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Services\Database;
use RelayWp\Affiliate\App\Services\Settings;

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
            'type'              => 'discount',
            'description'       => $discountData['description'],
            'status'            => $discountData['status'] ?? 'active', // Default to active if not provided
            'amount_type'       => $discountData['amount_type'], // Discount type (e.g., 'percent' or 'flat')
            'amount'            => $discountData['amount'], // Discount amount
            'max_uses'          => $discountData['max_uses'] ?? 0, // Max usage limit
            'start'             => !empty($discountData['start_date']) ? $discountData['start_date'] : null,
            'expiration'        => !empty($discountData['end_date']) ? $discountData['expiry_date'] : null,
            'min_charge_amount' => $discountData['min_charge_amount'] ?? 0, // Minimum cart amount
            'product_reqs'      => $discountData['product_reqs'] ?? [], // Required products
            'once_per_customer' => $discountData['once_per_customer'] ?? 0,
            'product_condition' => 'all', // Use 'all' or 'any' based on your requirement
            'excluded_products' => $discountData['excluded_products'] ?? [],
            'scope'             => $discountData['scope'] // Excluded products
        ];

        $existing_discount = edd_get_discount_by_code($discountData['code']);
        if ($existing_discount) {
            $discountId = $existing_discount->id;
            $existing_discount_id = edd_update_discount($discountId, $data);
            if (!$existing_discount_id) {
                return false;
            }
            return $existing_discount_id;
        }
        $discount_id = edd_add_discount($data);

        if (!$discount_id) {
            return false;
        }

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

    public static function getEDDCurrencySymbol($currencyCode = 'USA')
    {
        if (empty($currencyCode) && function_exists('edd_get_currency')) {
            $currency = edd_currency_symbol($currencyCode);
        }
        return html_entity_decode($currency);
    }

    static function getSession($key, $default = null)
    {
        if (static::isEDDSessionsEnabled() && isset(EDD()->session)) {
            return EDD()->session->get($key, $default);
        }
        return $default;
    }

    static function setSession($key, $value)
    {
        if (static::isEDDSessionsEnabled() && isset(EDD()->session)) {
            EDD()->session->set($key, $value);
        }
    }

    static function isEDDSessionsEnabled()
    {
        return class_exists('EDD') && isset(EDD()->session) && EDD()->session != null;
    }

    /*public static function getAppliedCouponsforOrder($order_id)
    {
        if (!is_object($order_id) && empty($order_id)) {
            $order = wc_get_order($order_id);
        } else {
            $order = $order_id;
        }

        // Get applied coupons from the order
        $applied_coupons = $order->get_coupon_codes();

        return $applied_coupons;
    }*/

    public static function getTotalPrice($order)
    {
        $excludeShipping = (bool)Settings::get('general_settings.commission_settings.exclude_shipping', false);
        $excludeTaxes = (bool)Settings::get('general_settings.commission_settings.exclude_taxes', false);

        //with tax and with shipping
        $orderTotal = $order->total;

        if ($excludeTaxes) {
            $orderTotal -= $order->tax;
        }

        if ($excludeShipping) {
            return $orderTotal;
        }

        return apply_filters('rwpa_get_total_price_of_the_order', $orderTotal);
    }

    /*public static function removeCoupon(string $code): bool
    {
        if(!WC::isWoocommerceIntsalled()){
            return $code;
        }
        return function_exists('WC') && isset(WC()->cart)
            && Util::isMethodExists(WC()->cart, 'remove_coupon') && WC()->cart->remove_coupon($code);
    }*/

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
        return 'WpLoyalty';
    }

    public static function getAdminDashboard()
    {
        $pluginSlug = EDDA_PLUGIN_SLUG;

        return admin_url("admin.php?page={$pluginSlug}#");
    }

    public static function getStates($countryCode)
    {
        if (!$countryCode) {
            return [];
        }
        if (function_exists('edd_get_shop_states')) {
            $states = edd_get_shop_states($countryCode);
            return isset($states) ? $states : [];
        }
        return [];
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
        return '$';
    }

    public static function getCurrencyList()
    {
        if (function_exists('edd_get_currencies')) {
            return edd_get_currencies();
        }
        return [];
    }

   /* public static function isHPOSEnabled()
    {
        if (! class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return false;
        }

        if (\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            return true;
        }

        return false;
    }*/

    public static function getDefaultCurrency()
    {
        if (function_exists('edd_get_currency')) {
            return edd_get_currency();
        }
        return '';
    }
    public static function getCurrencySymbol($symbol,$code){
        switch ( $code ) {
            case "GBP" :
                $symbol = '&pound;';
                break;
            case "BRL" :
                $symbol = 'R&#36;';
                break;
            case "EUR" :
                $symbol = '&euro;';
                break;
            case 'USD': // US Dollar
                $symbol = '&#36;'; // $
                break;
            case 'AUD': // Australian Dollar
                $symbol = 'A&#36;'; // A$
                break;
            case 'NZD': // New Zealand Dollar
                $symbol = 'NZ&#36;'; // NZ$
                break;
            case 'CAD': // Canadian Dollar
                $symbol = 'C&#36;'; // C$
                break;
            case 'HKD': // Hong Kong Dollar
                $symbol = 'HK&#36;'; // HK$
                break;
            case 'MXN': // Mexican Peso
                $symbol = 'MX&#36;'; // MX$
                break;
            case 'SGD': // Singapore Dollar
                $symbol = 'S&#36;'; // S$
                break;
            case 'CZK': // Czech Koruna
                $symbol = 'Kč';
                break;
            case 'DKK': // Danish Krone
                $symbol = 'kr';
                break;
            case 'HUF': // Hungarian Forint
                $symbol = 'Ft';
                break;
            case 'ILS': // Israeli Shekel
                $symbol = '&#8362;'; // ₪
                break;
            case 'MYR': // Malaysian Ringgit
                $symbol = 'RM';
                break;
            case 'NOK': // Norwegian Krone
                $symbol = 'kr';
                break;
            case 'PHP': // Philippine Peso
                $symbol = '₱';
                break;
            case 'PLN': // Polish Zloty
                $symbol = 'zł';
                break;
            case 'SEK': // Swedish Krona
                $symbol = 'kr';
                break;
            case 'CHF': // Swiss Franc
                $symbol = 'CHF';
                break;
            case 'TWD': // Taiwan New Dollar
                $symbol = 'NT$';
                break;
            case 'THB': // Thai Baht
                $symbol = '&#3647;'; // ฿
                break;
            case 'INR': // Indian Rupee
                $symbol = '&#8377;'; // ₹
                break;
            case 'TRY': // Turkish Lira
                $symbol = '&#8378;'; // ₺
                break;
            case 'RUB': // Russian Ruble
                $symbol = '&#8381;'; // ₽
                break;
            case "JPY" :
                $symbol = '&yen;';
                break;
            case "AOA" :
                $symbol = 'Kz';
                break;
            default:
                $symbol = $code; // Fallback to currency code if no match
                break;
        }
        return $symbol;
    }
    public static function getOrderStatus($order_status){
        if($order_status=='complete'){
            return 'completed';
        }
        return $order_status;
    }
    public static function getEDDCountries()
    {
        if(function_exists('edd_get_country_list')){
            return edd_get_country_list();
        }
        return [];
    }
    public static function getOrderStatusSettings($order_status){
        $result = [];
        foreach ($order_status as $status) {
            switch ($status) {
                case 'completed':
                    $result[] = 'complete';
                    break;
                default:
                    $result[] = $status;
                    break;
            }
        }
        return $result;
    }
}
