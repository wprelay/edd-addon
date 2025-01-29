<?php

//All routes actions will be performed in Route::handleAuthRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Hooks\AdminHooks;
use RelayWp\Affiliate\Core\Models\CoreModel;

$admin_hooks = [
    'actions' => [],
    'filters' => [],
];

$store_front_hooks = [
    'actions' => [],
    'filters' => [],
];

return [
    'admin_hooks' => $admin_hooks,
    'store_front_hooks' => $store_front_hooks
];
