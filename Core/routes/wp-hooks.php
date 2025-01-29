<?php

//All routes actions will be performed in Route::handleAuthRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\Core\Controllers\Api\AffiliateController;
use RelayWp\Affiliate\Core\Controllers\Api\PageController;

$store_front_hooks = [
    'actions' => [
        // 'wp_loaded' => ['callable' => [License::class,  'init'], 'priority' => 10, 'accepted_args' => 1],
        'user_register' => ['callable' => [AffiliateController::class,  'checkAffiliateExist'], 'priority' => 10, 'accepted_args' => 1],
        'deleted_user' => ['callable' => [AffiliateController::class,  'userDeleted'], 'priority' => 10, 'accepted_args' => 3],
        'profile_update' => ['callable' => [AffiliateController::class,  'userUpdated'], 'priority' => 10, 'accepted_args' => 3],
    ],
    'filters' => [],
];

$admin_hooks = [
    'actions' => [],
    'filters' => [],
];

return [
    'store_front_hooks' => $store_front_hooks,
    'admin_hooks' => $admin_hooks
];
