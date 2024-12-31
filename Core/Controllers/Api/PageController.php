<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Services\View;

class PageController
{
    /*
     *
     * instead of return just use echo when returning page in word-press plugin
     */

    public static function show()
    {
        echo wp_kses_post(View::render('admin'));
    }

    public static function localData() {}

    public static function addAffiliateRole()
    {
        $capabilities = array(
            'read' => true,  // Allow reading
            'edit_posts' => true,  // Allow editing posts
            'upload_files' => true,  // Allow uploading files
            // Add more capabilities as needed
        );

        // Add role
        add_role(
            'affiliate',  // Role slug
            'Affiliate',  // Role display name
            $capabilities  // Capabilities
        );
    }
}
