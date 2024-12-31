<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

class WooCommerceHooks extends RegisterHooks
{
    public static function register()
    {
        static::registerCoreHooks('woocommerce-hooks.php');

        if (rwpa_app()->get('is_pro_plugin')) {
            static::registerProHooks('woocommerce-hooks.php');
        }
    }
}
