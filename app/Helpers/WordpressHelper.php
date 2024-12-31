<?php

namespace EDDA\Affiliate\App\Helpers;

defined('ABSPATH') or exit;

class WordpressHelper
{

    /**
     * Verify nonce
     *
     * @param string $nonce
     * @param string $action
     * @return false
     */
    public static function verifyNonce($key, $nonce)
    {
        return (bool)wp_verify_nonce($nonce, $key);
    }

    /**
     * Verify nonce
     *
     * @param string $nonce
     * @param string $action
     * @return false
     */
    public static function createNonce($action)
    {
        return wp_create_nonce($action);
    }

    public static function getCurrentURL()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
        } else {
            $host = wp_parse_url(home_url(), PHP_URL_HOST);
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        } else {
            $path = '/';
        }
        return esc_url_raw((is_ssl() ? 'https' : 'http') . '://' . $host . $path);
    }

    public static function generateRandomPassword($length = 10)
    {
        // Define characters to use in the password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';

        // Get the total length of available characters
        $char_length = strlen($chars);

        // Initialize the password variable
        $password = '';

        // Generate random password
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[wp_rand(0, $char_length - 1)];
        }

        return $password;
    }

    public static function generateRandomString($length = 10)
    {
        return substr(md5(time()), 0, $length);
    }
}
