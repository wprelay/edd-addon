<?php

//All routes actions will be performed in Route::handleAuthRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\Core\Controllers\Api\AffiliateController;
use RelayWp\Affiliate\Core\Controllers\Api\PageController;

$store_front_hooks = [
    'actions' => [],
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
