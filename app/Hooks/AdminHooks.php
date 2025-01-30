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

}
