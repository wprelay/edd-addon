<?php

namespace EDDA\Affiliate\App\Services;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;

class Settings
{
    private static $settings = [];

    public static function get($key, $default = null)
    {
        if (empty(static::$settings)) {
            static::$settings = static::fetchSettings();
        }

        return Functions::dataGet(static::$settings, $key, $default);
    }

    public static function getDefaultSettingsData($key = null)
    {
        $data = require RWPA_PLUGIN_PATH . "app/config/settings.php";

        if (isset($data[$key])) {
            return $data[$key];
        }

        return $data;
    }

    public static function fetchSettings()
    {

        $settings = [];

        $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));

        $rwpa_settings = Functions::jsonDecode($rwpa_settings);

        $default_settings = static::getDefaultSettingsData();

        $settings['general_settings'] = isset($rwpa_settings['general_settings']) ? $rwpa_settings['general_settings'] : $default_settings['general_settings'];
        $settings['affiliate_settings'] = isset($rwpa_settings['affiliate_settings']) ? $rwpa_settings['affiliate_settings'] : $default_settings['affiliate_settings'];
        $settings['email_settings'] = isset($rwpa_settings['email_settings']) ? $rwpa_settings['email_settings'] : $default_settings['email_settings'];
        $settings['payment_settings'] = isset($rwpa_settings['payment_settings']) ? $rwpa_settings['payment_settings'] : $default_settings['payment_settings'];

        $settings = apply_filters('rwpa_get_settings', $settings);

        return $settings;
    }

    public static function getAffiliateReferralURLVariable()
    {
        return static::get('affiliate_settings.url_options.url_variable');
    }
}
