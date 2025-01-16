<?php

/**
 * Plugin Name:          Edd Relay
 * Description:          This is the add-on to support Easy Digital Downloads (EDD) in the WPRelay
 * Version:              1.0.0
 * Requires at least:    5.9
 * Requires PHP:         7.4
 * Author:               Relay Affiliate
 * Author URI:           https://www.wprelay.com
 * Text Domain:          edd-wprelay
 * Domain Path:          /i18n/languages
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins:     Easy Digital Downloads 3.1.0.1
 */

use EDDA\Affiliate\App\App;

defined('ABSPATH') or exit;

defined('EDDA_PLUGIN_PATH') or define('EDDA_PLUGIN_PATH', plugin_dir_path(__FILE__));
defined('EDDA_PLUGIN_URL') or define('EDDA_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('EDDA_PLUGIN_FILE') or define('EDDA_PLUGIN_FILE', __FILE__);
defined('EDDA_PLUGIN_NAME') or define('EDDA_PLUGIN_NAME', 'Edd-relay');
defined('EDDA_PLUGIN_SLUG') or define('EDDA_PLUGIN_SLUG', "relay-affiliate-marketing");
defined('EDDA_VERSION') or define('EDDA_VERSION', "1.0.0");
defined('EDDA_PREFIX') or define('EDDA_PREFIX', "prefix_");

/**
 * Required PHP Version
 */
if (!defined('EDDA_REQUIRED_PHP_VERSION')) {
    define('EDDA_REQUIRED_PHP_VERSION', 7.4);
}

$php_version = phpversion();

if (version_compare($php_version, EDDA_REQUIRED_PHP_VERSION, '<=')) {
    $message = EDDA_PLUGIN_NAME . ": Minimum PHP Version Required Is " . EDDA_REQUIRED_PHP_VERSION;
    $status = 'warning';

    add_action('admin_notices', function () use ($message, $status) {
        ?>
        <div class="notice notice-<?php echo esc_attr($status); ?>">
            <p><?php echo wp_kses_post($message); ?></p>
        </div>
        <?php
    }, 1);
    return;
}

/**
 * Required Easy Digital Downloads Version
 */
if (!defined('EDDA_REQUIRED_VERSION')) {
    define('EDDA_REQUIRED_VERSION', '3.1.0.1');
}
// To load PSR4 autoloader
if (!file_exists(EDDA_PLUGIN_PATH . '/vendor/autoload.php')) {
    return;
}
require EDDA_PLUGIN_PATH . '/vendor/autoload.php';

//check edd is installed or not
if (!function_exists('isEddInstalled')) {
    function isEddInstalled()
    {
        // Path to the EDD plugin
        $plugin_path = trailingslashit(WP_PLUGIN_DIR) . 'easy-digital-downloads/easy-digital-downloads.php';

        // Check if EDD is installed and activated
        if (
            in_array($plugin_path, wp_get_active_and_valid_plugins()) ||
            (is_multisite() && in_array($plugin_path, wp_get_active_network_plugins()))
        ) {
            return true;
            // EDD installed and activated
        } else {
            // EDD is not activated
            $message = EDDA_PLUGIN_NAME . ": Easy Digital Downloads Not Activated and it should be a minimum version of => " . EDDA_REQUIRED_VERSION;
            $status = 'warning';

            // Show admin notice if EDD is not installed or activated
            add_action('admin_notices', function () use ($message, $status) {
                ?>
                <div class="notice notice-<?php echo esc_attr($status); ?>">
                    <p><?php echo wp_kses_post($message); ?></p>
                </div>
                <?php
            }, 1);
            return false;
        }
    }
}
if (function_exists('isEddInstalled')) {
    if (isEddInstalled()) {
        // Check EDD version
        if (defined('EDD_VERSION') && version_compare(EDD_VERSION, EDDA_REQUIRED_VERSION, '<')) {
            $message = EDDA_PLUGIN_NAME . ": Easy Digital Downloads minimum version should be => " . EDDA_REQUIRED_VERSION;
            $status = 'warning';

            // Show admin notice if the EDD version is lower than required
            add_action('admin_notices', function () use ($message, $status) {
                ?>
                <div class="notice notice-<?php echo esc_attr($status); ?>">
                    <p><?php echo wp_kses_post($message); ?></p>
                </div>
                <?php
            }, 1);
            return false;
        }
    }
}

if (!function_exists('eddaApp')) {
    function eddApp(): App
    {
        return App::make();
    }
}

//here __FILE__ Will Return the Included File Path so it the base of the starting point.
// To bootstrap the plugin
if (class_exists('EDDA\Affiliate\App\App')) {
    $app = eddApp();
    $app->bootstrap(); // to load the plugin
} else {
    //    wp_die('Plugin is unable to find the App class.');
    return;
}
