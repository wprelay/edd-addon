<?php

namespace EDDA\Affiliate\App;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use RelayWp\Affiliate\App\Hooks\AdminHooks;
use RelayWp\Affiliate\App\Hooks\AssetsActions;
use RelayWp\Affiliate\App\Hooks\CustomHooks;
use RelayWp\Affiliate\App\Hooks\WooCommerceHooks;
use RelayWp\Affiliate\App\Hooks\WPHooks;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\CustomRules;

class Route
{
    //declare the below constants with unique reference for your plugin
    const AJAX_NAME = 'relay_affiliate';
    const AJAX_NO_PRIV_NAME = 'guest_apis';

    public static function register()
    {
        add_action('wp_ajax_nopriv_' . static::AJAX_NO_PRIV_NAME, [__CLASS__, 'handleGuestRequests']);
        add_action('wp_ajax_' . static::AJAX_NAME, [__CLASS__, 'handleAuthRequests']);

        AdminHooks::register();
        AssetsActions::register();
        WooCommerceHooks::register();
        CustomHooks::register();
        WPHooks::register();
    }

    public static function getRequestObject()
    {
        return Request::make()->setCustomRuleInstance(new CustomRules());
    }

    public static function handleAuthRequests()
    {
        $request = static::getRequestObject();

        $method = $request->get('method');

        $isAuthRoute = false;
        $handlers = require(PluginHelper::pluginRoutePath() . '/guest-api.php');

        if (rwpa_app()->get('is_pro_plugin')) {
            $handlers = array_merge($handlers, require(PluginHelper::pluginRoutePath(true) . '/guest-api.php'));
        }

        if (!isset($handlers[$method])) {
            //loading auth routes
            $handlers = PluginHelper::getAuthRoutes();

            if (rwpa_app()->get('is_pro_plugin')) {
                $handlers = array_merge($handlers, require(PluginHelper::pluginRoutePath(true) . '/auth-api.php'));
            }

            $isAuthRoute = true;
        }

        if ($isAuthRoute) {
            $nonce_key = $request->get('_wp_nonce_key');
            $nonce = $request->get('_wp_nonce');

            if ($method != 'get_local_data' && $method != 'playground') {
                static::verifyNonce($nonce_key, $nonce); // to verify nonce
            }
        }

        if (!isset($handlers[$method])) {
            Response::error(['message' => __('Method not exists', 'relay-affiliate-marketing')]);
        }

        $targetAction = $handlers[$method];

        return static::handleRequest($targetAction, $request);
    }

    public static function handleGuestRequests()
    {
        $request = static::getRequestObject();

        $method = $request->get('method');

        //loading guest routes
        $handlers = require(PluginHelper::pluginRoutePath() . '/guest-api.php');

        if (rwpa_app()->get('is_pro_plugin')) {
            $handlers = array_merge($handlers, require(PluginHelper::pluginRoutePath(true) . '/guest-api.php'));
        }

        if (!isset($handlers[$method])) {
            wp_send_json_error(['message' => 'Method not exists'], 404);
        }

        $targetAction = $handlers[$method];

        return static::handleRequest($targetAction, $request);
    }

    private static function verifyNonce($nonceKey, $nonce)
    {
        if (empty($nonce) || !WordpressHelper::verifyNonce($nonceKey, $nonce)) {
            Response::error(['message' => __('Security Check Failed', 'relay-affiliate-marketing')]);
        }
    }


    public static function handleRequest($targetAction, $request)
    {
        $target = $targetAction['callable'];

        $class = $target[0];

        $targetMethod = $target[1];

        $controller = new $class();

        $response = $controller->{$targetMethod}($request);

        return wp_send_json_success($response);
    }
}
