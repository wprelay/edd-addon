<?php

//All routes actions will be performed in Route::handleAuthRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Hooks\AdminHooks;
use RelayWp\Affiliate\Core\Models\CoreModel;

$admin_hooks = [
    'actions' => [
        'admin_init' => ['callable' => [AdminHooks::class, 'init'], 'priority' => 10, 'accepted_args' => 1],
        'admin_head' => ['callable' => [AdminHooks::class, 'head'], 'priority' => 10, 'accepted_args' => 1],
        'admin_menu' => ['callable' => [AdminHooks::class, 'addMenu'], 'priority' => 10, 'accepted_args' => 1],
    ],
    'filters' => [
        'rwpa_affiliate_get_models' => ['callable' => [CoreModel::class, 'getCoreModels'], 'priority' => 10, 'accepted_args' => 1],
    ],
];

$store_front_hooks = [
    'actions' => [],
    'filters' => [],
];

return [
    'admin_hooks' => $admin_hooks,
    'store_front_hooks' => $store_front_hooks
];
