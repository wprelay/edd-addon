<?php

defined("ABSPATH") or exit;

//All routes actions will be performed in Route::handleAuthRequest method.
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\CouponController;
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\EmailController;
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\OrderHook;
use RelayWp\Affiliate\Core\Controllers\StoreFront\AccountController;
use EDDA\Affiliate\Core\Controllers\StoreFront\CartController;
use EDDA\Affiliate\Core\Controllers\StoreFront\OrderPlacedController;

$store_front_hooks = [
    'actions' => [
        //define your woocommerce actions and filters here
        'init' => ['callable' => [AccountController::class, 'registerAffiliateEndPoint'], 'priority' => 10, 'accepted_args' => 1],

        'woocommerce_checkout_update_order_meta' => ['callable' => [OrderPlacedController::class, 'orderCreated'], 'priority' => 10, 'accepted_args' => 2],
        'edd_insert_payment_custom' => ['callable' => [OrderPlacedController::class, 'orderCreatedFromBlockCheckout'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_order_status_changed' => ['callable' => [OrderPlacedController::class, 'orderStatusUpdated'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_checkout_update_order_review' => ['callable' => [\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'getBillingEmail'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_removed_coupon' => ['callable' => [\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'couponRemovedManually'], 'priority' => 10, 'accepted_args' => 1],

        'woocommerce_after_cart_item_quantity_update' => ['callable' => [\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_add_to_cart' => ['callable' => [\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_remove_cart_item' => ['callable' => [\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::class, 'clearCustomerRemovedCoupon'], 'priority' => 10, 'accepted_args' => 1],

        'edd_before_checkout_cart' => function () {
            return [
                ['callable' => [CartController::class, 'applyAffiliateCouponIfNotApplied'], 'priority' => 10, 'accepted_args' => 1],
                ['callable' => [CartController::class, 'removeInvalidCoupons'], 'priority' => 11, 'accepted_args' => 1],
            ];
        },
        //Click Save Changes in General Permalink Settings to Reflect
        'woocommerce_account_relay-affiliate-marketing_endpoint' => ['callable' => [AccountController::class, 'registerAffiliateEndpointContent'], 'priority' => 10, 'accepted_args' => 1],
    ],


    'filters' => [
        'woocommerce_account_menu_items' => ['callable' => [AccountController::class, 'addAffiliateMenu'], 'priority' => 10, 'accepted_args' => 1],
    ],
];

$admin_hooks = [
    'actions' => [
        'save_post' => ['callable' => [CouponController::class, 'detectCouponUpdate'], 'priority' => 10, 'accepted_args' => 3],
        'trashed_post' => ['callable' => [CouponController::class, 'couponTrashed'], 'priority' => 10, 'accepted_args' => 1],
        'restrict_manage_posts' => ['callable' => [OrderHook::class, 'render_custom_orders_filters'], 'priority' => 100, 'accepted_args' => 2],
        'woocommerce_order_list_table_restrict_manage_orders' => ['callable' => [OrderHook::class, 'render_custom_orders_filters'], 'priority' => 100, 'accepted_args' => 2],
        'pre_get_posts' => function () {
            return
                [
                    ['callable' => [CouponController::class, 'filterCustomCoupons'], 'priority' => 10, 'accepted_args' => 1],
                    ['callable' => [OrderHook::class, 'filter_woocommerce_orders_in_the_table'], 'priority' => 99, 'accepted_args' => 1]
                ];
        },
    ],

    'filters' => [
        'edd_email_templates' => ['callable' => [EmailController::class, 'addEmails'], 'priority' => 10, 'accepted_args' => 1],
        //        'woocommerce_coupon_error' => ['callable' => [CouponController::class, 'couponError'], 'priority' => 10, 'accepted_args' => 3],
        'views_edit-shop_coupon' => ['callable' => [CouponController::class, 'addCouponFilter'], 'priority' => 10, 'accepted_args' => 1],
        'woocommerce_order_query_args' => ['callable' => [OrderHook::class, 'filter_woocommerce_orders_for_hpos'], 'priority' => 99, 'accepted_args' => 1],

    ],
];


return [
    'store_front_hooks' => $store_front_hooks,
    'admin_hooks' => $admin_hooks
];
