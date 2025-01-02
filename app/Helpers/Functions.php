<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

use DateTime;
use DateTimeZone;
use Cartrabbit\Request\Request;
use Exception;
use EDDA\Affiliate\App\Route;
use EDDA\Affiliate\App\Services\Settings;

defined('ABSPATH') or exit;

class Functions
{

    public static function snakeCaseToCamelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    public static function arrayToJson($array)
    {
        if (empty($array)) return null;

        return wp_json_encode($array);
    }

    public static function currentTimestamp()
    {
        return gmdate('Y-m-d H:i:s');
    }

    public static function formatAmount($amount, $currencyCode = 'USD')
    {
        if ($amount == 0 || empty($amount)) $amount = 0;

        if (function_exists('edd_currency_symbol')) {
            return html_entity_decode(edd_currency_symbol($currencyCode)) . number_format($amount, 2); // Format amount as needed
        }

        return $currencyCode . ' ' . number_format($amount, 2);
    }

    public static function utcToWPTime($datetime, $format = 'Y-m-d H:i:s')
    {
        if (empty($datetime)) return null;

        $date = new DateTime($datetime, new DateTimeZone('UTC'));

        $timestamp = $date->format('U');

        return wp_date($format, $timestamp);
    }

    public static function wpToUTCTime($datetime, $format = 'Y-m-d H:i:s')
    {
        if (empty($datetime)) return null;

        $date = new DateTime($datetime, wp_timezone());

        // Convert the DateTime object to UTC
        $date->setTimezone(new DateTimeZone('UTC'));

        // Format the date and time in UTC using wp_date
        return wp_date($format, $date->getTimestamp(), new DateTimeZone('UTC'));
    }

    public static function getWcTime($datetime, $format = 'Y-m-d H:i:s')
    {
        return Functions::utcToWPTime($datetime, $format);
    }

    public static function currentUTCTime($format = 'Y-m-d H:i:s')
    {
        return current_datetime()->setTimezone(new DateTimeZone('UTC'))->format($format);
    }

    public static function dataGet(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    public static function isAffiliateCookieSet()
    {
        $urlVariable = Settings::getAffiliateReferralURLVariable();

        if (empty($value = Request::cookie($urlVariable))) {
            return false;
        }

        return $value;
    }

    public static function getSelectedCurrency()
    {
        $request = Route::getRequestObject();

        $currency = $request->get('rwp_currency');

        if (empty($currency)) {
            if(function_exists('get_woocommerce_currency')) {
                $currency = get_woocommerce_currency();
            }
        }
        return $currency;
    }

    public static function isContentTypeJson()
    {
        // Check if the Content-Type header is set
        if (!empty($contentType = Request::server('CONTENT_TYPE'))) {
            // Get the Content-Type header value

            // Return true if the content type is application/json
            return strtolower($contentType) === 'application/json';
        }

        // Alternatively, you can check HTTP_CONTENT_TYPE for some server configurations
        if (!empty($contentType = Request::server('HTTP_CONTENT_TYPE'))) {

            // Return true if the content type is application/json
            return strtolower($contentType) === 'application/json';
        }

        // Default to false if Content-Type header is not set
        return false;
    }

    public static function getBoolValue($value)
    {
        if ($value === 'false') return false;

        if ($value === 'true') return true;

        if ($value === '1') return true;

        if ($value === '0') return false;

        if ($value === 1) return true;

        if ($value === 0) return false;

        return (bool)$value;
    }

    public static function getUniqueKey($id = null)
    {
        if (empty($id)) {
            $id = random_int(1, 1000);
        }
        return substr(md5(uniqid(wp_rand(), true) . $id), 0, 12);
    }

    public static function toSnakeCase($text)
    {
        // Step 1: Convert CamelCase or PascalCase to snake_case
        $pattern = '/([a-z])([A-Z])/';
        $replacement = '$1_$2';
        $snake = preg_replace($pattern, $replacement, $text);

        // Step 2: Replace non-alphanumeric characters with underscores
        $snake = preg_replace('/[^a-zA-Z0-9]+/', '_', $snake);

        // Step 3: Convert the string to lowercase
        $snake = strtolower($snake);

        // Step 4: Trim any leading or trailing underscores
        return trim($snake, '_');
    }

    /**
     * @param $date
     * @param string $format
     */
    public static function formatDate(?string $date, string $format = 'Y-m-d H:i')
    {
        if (empty($date)) return null;

        try {
            $dateTime = new DateTime($date);
            $formatted_date = $dateTime->format($format);
        } catch (Exception $exception) {
            $formatted_date = null;
        }

        return $formatted_date;
    }

    public static function jsonDecode($data)
    {
        if (is_null($data)) {
            return json_decode('{}', true);
        }

        return json_decode($data, true);
    }

    public static function removeScripts($html_text)
    {
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html_text);
    }
}
