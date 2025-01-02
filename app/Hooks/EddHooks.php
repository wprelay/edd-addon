<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

class EddHooks extends RegisterHooks
{
    public static function register()
    {
        static::registerCoreHooks('edd-hooks.php');

        if (eddApp()->get('is_pro_plugin')) {
            static::registerProHooks('edd-hooks.php');
        }
    }
}
