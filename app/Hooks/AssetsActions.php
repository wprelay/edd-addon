<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\AssetHelper;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use RelayWp\Affiliate\App\Route;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;

defined('ABSPATH') or exit;

class AssetsActions
{
    public static function register()
    {
        static::enqueue();
    }

    /**
     * Enqueue scripts
     */
    public static function enqueue()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'addAdminPluginAssets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'addStoreFrontScripts']);
    }

    public static function addAdminPluginAssets($hook)
    {
        if (strpos($hook, RWPA_PLUGIN_SLUG) !== false) {
            $reactDistUrl = AssetHelper::getReactAssetURL();
            $resourceUrl = AssetHelper::getResourceURL();
            $pluginSlug = RWPA_PLUGIN_SLUG;
            wp_enqueue_style("{$pluginSlug}-plugin-styles", "{$reactDistUrl}/main.css", [], RWPA_VERSION);
            wp_enqueue_script("{$pluginSlug}-plugin-script", "{$reactDistUrl}/main.bundle.js", array('wp-element'), RWPA_VERSION, true);
            wp_enqueue_style("{$pluginSlug}-plugin-styles-font-awesome", "{$resourceUrl}/admin/css/rwp-fonts.css", [], RWPA_VERSION);
            remove_all_actions('admin_notices');
        }
    }

    public static function addStoreFrontScripts()
    {
        $pluginSlug = RWPA_PLUGIN_SLUG;
        $handle = "{$pluginSlug}-track-order-script";
        $variableName = Settings::get('affiliate_settings.url_options.url_variable');


        $request = Route::getRequestObject();

        $affiliate_variable_is_present = !empty($request->get($variableName));

        if ($affiliate_variable_is_present || defined('WC_VERSION') ? is_checkout() : true) {
            $resourceUrl = AssetHelper::getResourceURL();
            $storeConfig = AssetsActions::getStoreConfigValues();

            wp_enqueue_script($handle, "{$resourceUrl}/scripts/track_wprelay_order.js", array('jquery'), RWPA_VERSION, true);
            wp_localize_script($handle, 'rwpa_relay_store', $storeConfig);
        }
    }

    public static function getStoreConfigValues()
    {
        return [
            'home_url' => get_home_url(),
            'admin_url' => admin_url(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'recaptcha_site_key' => Settings::get('affiliate_settings.recaptcha.site_key'),
            'affiliate_url_variable' => Settings::get('affiliate_settings.url_options.url_variable'),
            'cookie_duration' => Settings::get('general_settings.cookie_duration'),
            'cookie_host_name' => apply_filters('rwpa_get_host_name_for_cookie', null),
            'nonces' => [
                'wprelay_state_list_nonce' => WordpressHelper::createNonce('wprelay_state_list_nonce'),
            ]
        ];
    }
}
