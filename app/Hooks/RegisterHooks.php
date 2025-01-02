<?php

namespace EDDA\Affiliate\App\Hooks;

defined('ABSPATH') or exit;

use EDDA\Affiliate\App\Helpers\PluginHelper;

class RegisterHooks
{
    public static function registerCoreHooks($hookFile, $context = '')
    {

        $path = PluginHelper::pluginRoutePath();
        $hooks = require("{$path}/{$hookFile}");

        static::bindActions($hooks['store_front_hooks']['actions']);
        static::bindActions($hooks['admin_hooks']['actions']);

        static::bindFilters($hooks['store_front_hooks']['filters']);
        static::bindFilters($hooks['admin_hooks']['filters']);
    }

    public static function registerProHooks($hookFile)
    {
        $path = PluginHelper::pluginRoutePath(true);
        $hooks = require("{$path}/{$hookFile}");

        static::bindActions($hooks['store_front_hooks']['actions']);
        static::bindActions($hooks['admin_hooks']['actions']);

        static::bindFilters($hooks['store_front_hooks']['filters']);
        static::bindFilters($hooks['admin_hooks']['filters']);
    }

    public static function bindActions($actions = []): void
    {
        static::bindHooks($actions, 'action');
    }

    public static function bindFilters($filters = []): void
    {
        static::bindHooks($filters, 'filter');
    }

    private static function bindHooks($hooks = [], $type = 'action')
    {
        if (!is_array($hooks)) {
            wp_die('Expected array value');
        }

        foreach ($hooks as $name => $handler) {
            if (is_callable($handler)) {
                $nestedHandlers = $handler();
                foreach ($nestedHandlers as $nestedHandler) {
                    static::bind($name, $nestedHandler, $type);
                }
            } else {
                static::bind($name, $handler, $type);
            }
        }
    }

    public static function bind($name, $handler, $type = 'action'): void
    {
        if (is_callable($handler['callable'])) {
            if ($type == 'action') {
                add_action($name, $handler['callable'], $handler['priority'], $handler['accepted_args']);
            } else if ($type == 'filter') {
                add_filter($name, $handler['callable'], $handler['priority'], $handler['accepted_args']);
            } else {
                wp_die("The handler either should be action or filter when registering");
            }
        } else {
            wp_die(esc_attr("Error While Registering {$name}, It's Not Callable"));
        }
    }
}
