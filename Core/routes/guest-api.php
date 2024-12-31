<?php

//All routes actions will be performed in Route::handleGuestRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\Core\Controllers\Admin\WcController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\CustomerController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\MenuController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\RegisterController;

return [
    'capture_customer_visit' => ['callable' => [CustomerController::class, 'captureVisit']],
    'new_affiliate_registration' => ['callable' => [RegisterController::class, 'newAffiliateRequest']],
    'update_affiliate' => ['callable' => [MenuController::class, 'updateAffiliate']],
    'get_wc_states_for_store_front' => ['callable' => [WcController::class, 'getWcStates']],
];
