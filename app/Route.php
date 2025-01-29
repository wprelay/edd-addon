<?php

namespace EDDA\Affiliate\App;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use EDDA\Affiliate\App\Hooks\AdminHooks;
use EDDA\Affiliate\App\Hooks\CustomHooks;
use EDDA\Affiliate\App\Hooks\EddHooks;
use EDDA\Affiliate\App\Hooks\WPHooks;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\CustomRules;

class Route
{
    //declare the below constants with unique reference for your plugin
    const AJAX_NAME = 'relay_affiliate';
    const AJAX_NO_PRIV_NAME = 'guest_apis';

    public static function register()
    {
        AdminHooks::register();
        EddHooks::register();
        CustomHooks::register();
        WPHooks::register();
    }

    public static function getRequestObject()
    {
        return Request::make()->setCustomRuleInstance(new CustomRules());
    }
}
