<?php

defined("ABSPATH") or exit;

//All routes actions will be performed in Route::handleAuthRequest method.
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\CouponController;
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\EmailController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\AccountController;
use EDDA\Affiliate\Core\Controllers\StoreFront\CartController;
use EDDA\Affiliate\Core\Controllers\StoreFront\OrderPlacedController;

$store_front_hooks = [
    'actions' => [

        'edd_insert_payment' => ['callable' => [OrderPlacedController::class, 'orderCreatedFromBlockCheckout'], 'priority' => 10, 'accepted_args' => 1],
        'edd_update_payment_status' => ['callable' => [OrderPlacedController::class, 'orderStatusUpdated'], 'priority' => 10, 'accepted_args' => 3],
        'edd_removed_coupon' => ['callable' => [\EDDA\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'couponRemovedManually'], 'priority' => 10, 'accepted_args' => 1],

        'edd_update_cart_item' => ['callable' => [\EDDA\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],
        'edd_add_to_cart' => ['callable' => [\EDDA\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],
        'edd_cart_item_removed' => ['callable' => [\EDDA\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],

        'edd_before_checkout_cart' => function () {
            return [
                ['callable' => [CartController::class, 'applyAffiliateCouponIfNotApplied'], 'priority' => 10, 'accepted_args' => 1],
                ['callable' => [CartController::class, 'removeInvalidCoupons'], 'priority' => 11, 'accepted_args' => 1],
            ];
        },
    ],


    'filters' => [
        'edd_get_account_menu_items' => ['callable' => [AccountController::class, 'addAffiliateMenu'], 'priority' => 10, 'accepted_args' => 1],
        'edd_currency_symbol' => ['callable' => [EDD::class, 'getCurrencySymbol'], 'priority' => 10, 'accepted_args' => 2]
    ],
];

$admin_hooks = [
    'actions' => [],

    'filters' => [
        'edd_email_templates' => ['callable' => [EmailController::class, 'addEmails'], 'priority' => 10, 'accepted_args' => 1],
    ],
];


return [
    'store_front_hooks' => $store_front_hooks,
    'admin_hooks' => $admin_hooks
];
