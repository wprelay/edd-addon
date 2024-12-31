<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Helpers\PluginHelper;

class AddOn
{
    const REMOTE_JSON_FILE_URL = 'https://cdn.jsdelivr.net/gh/wprelay/wprelay-addons@add_on_list/list_v1.json';

    public static function list()
    {

        $default_data = [
            'name' => '',
            'description' => '',
            'icon_url' => '',
            'product_url' => '',
            'download_url' => '',
            'plugin_file' => '',
            'page_url' => '',
            'settings_url' => '',
            'requires' => [
                'wp' => '',
                'wc' => '',
                'wdr_core' => '',
                'wdr_pro' => '',
            ],
            'is_pro' => false,
            'is_external' => true,
        ];

        $addons = apply_filters('wprelay_addon_list', self::getRemoteAddonsList());

        $available_plugins = array_keys(get_plugins());

        $items = [];
        foreach ($addons as $slug => $addon) {
            $addon = array_merge($default_data, $addon);
            $addon['name'] = $addon['name'] ?? '';
            $addon['description'] = $addon['description'] ?? '';
            $addon['icon_url'] = $addon['icon_url'] ?? '';
            $addon['product_url'] = $addon['product_url'] ?? '';
            $addon['page_url'] = self::parseAddonUrl($addon['page_url'] ?? '', $slug);
            $addon['settings_url'] = self::parseAddonUrl($addon['settings_url'] ?? '', $slug);
            $addon['is_active'] = PluginHelper::isActive($addon['plugin_file']);
            $addon['is_installed'] = !empty($addon['plugin_file']) && in_array($addon['plugin_file'], $available_plugins);
            $addon['download_url'] = $addon['download_url'] ?? '';
            $addon['slug'] = $slug;

            $items[] = $addon;
        }

        $items = apply_filters('rwp_plugin_apps', $items);

        Response::success([
            'items' => $items
        ]);
    }

    public static function togglePluginActivation(Request $request)
    {
        $plugin_action = $request->get('plugin_action');
        $plugin = $request->get('plugin');

        if (empty($plugin_action) || empty($plugin)) {
            Response::error([
                'message' => __('Plugin Data is missing', 'relaywp')
            ]);
        }

        if ($plugin_action == 'activate') {
            if (!current_user_can('activate_plugin', $plugin)) {
                Response::error([
                    'message' => __('Sorry, you are not allowed to activate this plugin.', 'relaywp')
                ]);
            }

            if (is_multisite() && !is_network_admin() && is_network_only_plugin($plugin)) {
                Response::error([
                    'message' => __('Sorry, you are not allowed to activate this plugin.', 'relaywp')
                ]);
            }

            $result = activate_plugin($plugin, '', is_network_admin());
            if (is_wp_error($result)) {
                Response::error([
                    'message' => __('App activation failed', 'relaywp')
                ]);
            }

            if (!is_network_admin()) {
                $recent = (array)get_option('recently_activated');
                unset($recent[$plugin]);
                update_option('recently_activated', $recent);
            } else {
                $recent = (array)get_site_option('recently_activated');
                unset($recent[$plugin]);
                update_site_option('recently_activated', $recent);
            }

            Response::success([
                'message' => __('Plugin activated successfully', 'relaywp')
            ]);
        } else if ($plugin_action == 'deactivate') {
            if (!current_user_can('deactivate_plugin', $plugin)) {
                Response::error([
                    'message' => __('Sorry, you are not allowed to deactivate this plugin.', 'relaywp')
                ]);
            }

            if (!is_network_admin() && is_plugin_active_for_network($plugin)) {
                Response::error([
                    'message' => __('Sorry, you are not allowed to deactivate this plugin.', 'relaywp')
                ]);
            }

            deactivate_plugins($plugin, false, is_network_admin());

            if (!is_network_admin()) {
                update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
            } else {
                update_site_option('recently_activated', array($plugin => time()) + (array)get_site_option('recently_activated'));
            }
            Response::success([
                'message' => __('Plugin deactivated successfully', 'wp-loyalty-rules')
            ]);
        }
    }

    public static function isPluginIsActive($plugin_path)
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        return in_array($plugin_path, $active_plugins) || array_key_exists($plugin_path, $active_plugins);
    }

    private static function getRemoteAddonsList(): array
    {
        $addons = get_transient('wprelay_remote_addons_list');
        if (empty($addons)) {
            $addons = [];
            $response = wp_remote_get(self::REMOTE_JSON_FILE_URL);
            if (!is_wp_error($response)) {
                $addons = (array)json_decode(wp_remote_retrieve_body($response), true);
                set_transient('wprelay_remote_addons_list', $addons, 24 * 60 * 60);
            }
        }
        return $addons;
    }

    private static function parseAddonUrl(string $url, string $slug): string
    {
        $rules_page = admin_url('admin.php?page=' . RWP_PLUGIN_SLUG);
        $addon_page = admin_url("admin.php?page=$slug#/");
        return str_replace(['{admin_page}', '{rules_page}', '{addon_page}'], [admin_url(), $rules_page, $addon_page], $url);
    }
}

