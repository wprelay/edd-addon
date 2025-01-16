<?php

namespace EDDA\Affiliate\Core\ShortCodes;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\Core\Controllers\StoreFront\AccountController;

class GlobalRegistrationFormShortCode
{

    public static function register()
    {
        add_shortcode('edd_affiliate_page', [AccountController::class, 'registerAffiliateEndpointContent']);
    }
}
