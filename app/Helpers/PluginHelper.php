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

    public static function serverErrorMessage()
    {
        return ['message' => __('Server Error Occurred', 'relay-affiliate-marketing')];
    }
}
