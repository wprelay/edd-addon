<?php

namespace EDDA\Affiliate\App;

defined('ABSPATH') or exit;

class App extends Container
{

    public static $app;

    public static function make()
    {
        if (!isset(self::$app)) {
            self::$app = new static();
        }

        return self::$app;
    }

    /* Bootstrap plugin
     */
    public function bootstrap()
    {
        Setup::init();
        add_action('plugins_loaded', function () {
            do_action('rwpa_before_init');
            Route::register();

            static::registerShortCodes();
            do_action('rwpa_after_init');
        }, 1);
    }

    public static function registerShortCodes()
    {
        //register the shortcode classes
        $classes =  apply_filters('rwpa_get_shortcodes_classes', []);

        foreach ($classes as $class) {
            $class::register();
        }
    }
}
