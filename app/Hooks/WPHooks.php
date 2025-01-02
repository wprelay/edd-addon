<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

class WPHooks extends RegisterHooks
{
    public static function register()
    {
        static::registerCoreHooks('wp-hooks.php');

        if (eddApp()->get('is_pro_plugin')) {
            static::registerProHooks('wp-hooks.php');
        }
    }
}

