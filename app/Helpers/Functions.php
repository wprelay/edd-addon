<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

use DateTime;
use DateTimeZone;
use Cartrabbit\Request\Request;
use Exception;
use EDDA\Affiliate\App\Route;
use RelayWp\Affiliate\App\Services\Settings;

defined('ABSPATH') or exit;

class Functions
{

    public static function arrayToJson($array)
    {
        if (empty($array)) return null;

        return wp_json_encode($array);
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

    public static function getEDDTime($datetime, $format = 'Y-m-d H:i:s')
    {
        return Functions::utcToWPTime($datetime, $format);
    }

    public static function currentUTCTime($format = 'Y-m-d H:i:s')
    {
        return current_datetime()->setTimezone(new DateTimeZone('UTC'))->format($format);
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
            if(function_exists('edd_get_currency')) {
                $currency = edd_get_currency();
            }
        }
        return $currency;
    }

    public static function jsonDecode($data)
    {
        if (is_null($data)) {
            return json_decode('{}', true);
        }

        return json_decode($data, true);
    }
}
