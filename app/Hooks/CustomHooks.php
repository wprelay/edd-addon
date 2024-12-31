<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

class CustomHooks extends RegisterHooks
{
    public static function register()
    {
        static::registerCoreHooks('custom-hooks.php');

        if (rwpa_app()->get('is_pro_plugin')) {
            static::registerProHooks('custom-hooks.php');
        }
    }
}

