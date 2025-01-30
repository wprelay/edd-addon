<?php

namespace EDDA\Affiliate\App;

defined("ABSPATH") or exit;

class Setup
{
    /**
     * Init setup
     */
    public static function init()
    {
        register_activation_hook(EDDA_PLUGIN_FILE, [__CLASS__, 'activate']);
        register_deactivation_hook(EDDA_PLUGIN_FILE, [__CLASS__, 'deactivate']);
        register_uninstall_hook(EDDA_PLUGIN_FILE, [__CLASS__, 'uninstall']);

        // add_action('plugins_loaded', [__CLASS__, 'maybeRunMigration']);
        //        add_action('upgrader_process_complete', [__CLASS__, 'maybeRunMigration']);
    }

    /**
     * Run plugin activation scripts
     */
    public static function activate() {}

    /**
     * Run plugin activation scripts
     */
    public static function deactivate()
    {
        //        wp_clear_scheduled_hook('rwp_update_affiliate_coupons');
    }

    /**
     * Run plugin activation scripts
     */
    public static function uninstall()
    {
        //        $models = static::getModels();
        //
        //        global $wpdb;
        //        foreach ($models as $model) {
        //            $object = (new $model);
        //
        //            if ($object instanceof Model) {
        //                $query = $object->deleteTable();
        //                $wpdb->query("set foreign_key_checks = 0;");
        //                $wpdb->query($query);
        //                $wpdb->query("set foreign_key_checks = 1;");
        //            }
        //        }
        //        delete_option('rwpa_current_version');
        //        delete_option('rwpa_plugin_settings');

    }
}
