<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

use EDDA\Affiliate\App\Helpers\PluginHelper;
use EDDA\Affiliate\Core\Controllers\Api\PageController;

class AdminHooks extends RegisterHooks
{
    public static function register()
    {
        static::registerCoreHooks('admin-hooks.php');

        if (PluginHelper::isPRO()) {
            static::registerProHooks('admin-hooks.php');
        }
    }

    public static function init() {}

    public static function head() {}

    public static function addMenu()
    {
        $pluginName = RWPA_PLUGIN_NAME;

        add_menu_page(
            $pluginName,
            $pluginName,
            'manage_options',
            RWPA_PLUGIN_SLUG,
            [PageController::class, 'show'],
            'dashicons-money',
            56
        );
    }
}
