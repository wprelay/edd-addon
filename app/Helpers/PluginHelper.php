<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use DateTime;
use Exception;
use RelayWp\Affiliate\App\Services\Settings;

class PluginHelper
{
    public static function isPRO()
    {
        $is_pro = eddApp()->get('is_pro_plugin');

        if (empty($is_pro)) return false;

        return true;
    }

    public static function getAuthRoutes()
    {
        $is_pro = static::isPRO();
        $routes = [];
        if ($is_pro) {
            $routes = require(EDDA_PLUGIN_PATH . 'Pro/routes/auth-api.php');
        }

        $core_routes = require(EDDA_PLUGIN_PATH . 'Core/routes/auth-api.php');

        $routes = array_merge($routes, $core_routes);

        return $routes;
    }

    public static function pluginRoutePath($pro = false)
    {
        if ($pro) {
            return EDDA_PLUGIN_PATH . 'Pro/routes';
        }

        return EDDA_PLUGIN_PATH . 'Core/routes';
    }

    public static function logError($message, $location = [], $exception = null)
    {
        if (empty($location)) {
            $log_message = $message;
        } else {
            $log_message = "Error At: {$location[0]}@{$location[1]} => `{$message}` ";
        }
        // Create a log message

        // If an exception object is provided, append its details to the log message
        if (($exception instanceof Exception) || ($exception instanceof \Error)) {
            $log_message .= "\nAcutal Error Message: " . $exception->getMessage();
            $log_message .= "\nTrace Details: " . $exception->getTraceAsString();
        }

        error_log($log_message);
    }

    public static function isActive(string $plugin_path): bool
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array($plugin_path, $active_plugins) || array_key_exists($plugin_path, $active_plugins);
    }

    public static function getPluginName()
    {
        return apply_filters('rwpa_get_plugin_name', RWPA_PLUGIN_NAME);
    }

    public static function serverErrorMessage()
    {
        return ['message' => __('Server Error Occurred', 'relay-affiliate-marketing')];
    }

    public static function verifyGoogleRecaptcha($request)
    {
        $secret_key = Settings::get('affiliate_settings.recaptcha.secret_key');

        if (empty($secret_key)) {
            Response::success([
                'recaptcha' => ["Google Recaptcha key not configured"]
            ], 422);
        }

        $curlData = array(
            'secret' => $secret_key,
            'response' => $request->get('g-recaptcha-response')
        );

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => $curlData,
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            // Handle the error appropriately
            Response::success([
                'recaptcha' => [$error_message]
            ], 422);
        }

        $body = wp_remote_retrieve_body($response);
        // Process the response as needed
        $captchaResponse = json_decode($body, true);

        if (
            $captchaResponse['success'] == '1'
            && $captchaResponse['action'] == 'validate_captcha'
            && $captchaResponse['score'] >= 0.5
            && $captchaResponse['hostname'] == Request::server('SERVER_NAME')
        ) {
            return null;
        } else {
            Response::success([
                'recaptcha' => ["Recaptcha Validation Failed You are not a human"]
            ], 422);
        }
    }
}
