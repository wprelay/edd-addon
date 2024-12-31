<?php

defined("ABSPATH") or exit;

use RelayWp\Affiliate\Core\Controllers\Admin\General\BackgroundJobController;
use RelayWp\Affiliate\Core\Controllers\Admin\HooksController\WooCommerce\EmailController;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Payments\Coupon;
use RelayWp\Affiliate\Core\Payments\Offline;
use RelayWp\Affiliate\Core\Payments\RWPPayment;
use RelayWp\Affiliate\Core\ShortCodes\ShortCodes;

$store_front_hooks = [
    'actions' => [
        'rwpa_auto_approve_commission' => ['callable' => [BackgroundJobController::class, 'autoApproveCommission'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_payment_mark_as_succeeded' => ['callable' => [Payout::class, 'markAsSucceeded'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_payment_mark_as_failed' => ['callable' => [Payout::class, 'markAsFailed'], 'priority' => 10, 'accepted_args' => 2],
    ],
    'filters' => [
        'rwpa_get_commission_details_for_fixed_type' => ['callable' => [CommissionTier::class, 'getFixedAmountDetail'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_commission_details_for_percentage_per_sale_type' => ['callable' => [CommissionTier::class, 'getPercentagePerSaleAmountDetail'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_recursive_data_to_store' => ['callable' => [CommissionTier::class, 'getRecursiveDataToStore'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_core_recursive_data_to_store' => ['callable' => [CommissionTier::class, 'getCoreRecursiveData'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_track_affiliate_order' => ['callable' => [Order::class, 'isNeedToTrackTheOrder'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_get_shortcodes_classes' => ['callable' => [ShortCodes::class, 'getShortCodes'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_coupon_payment_available_for_currency' => ['callable' => [Coupon::class, 'isCouponPaymentAvailable'], 'priority' => 10, 'accepted_args' => 2],

    ]
];

$admin_hooks = [
    'actions' => [
        'rwpa_send_affiliate_approved_email' => ['callable' => [EmailController::class, 'sendAffiliateApprovedEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_send_affiliate_rejected_email' => ['callable' => [EmailController::class, 'sendAffiliateRejectedEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_send_new_sale_made_email' => ['callable' => [EmailController::class, 'newSaleMadeEmail'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_payment_processed_email' => ['callable' => [EmailController::class, 'paymentProcessedEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_send_affiliate_registered_email' => ['callable' => [EmailController::class, 'affiliateRegisteredEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_affiliate_commission_approved_email' => ['callable' => [EmailController::class, 'commissionApprovedEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_affiliate_commission_rejected_email' => ['callable' => [EmailController::class, 'commissionRejectedEmail'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_update_affiliate_coupons' => ['callable' => [BackgroundJobController::class, 'updateAffiliateCoupons'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_enqueue_payments' => ['callable' => [BackgroundJobController::class, 'enqueuePayments'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_record_rwt_payment' => ['callable' => [RWPPayment::class, 'processPayment'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_process_coupon_payouts' => ['callable' => [Coupon::class, 'sendPayments'], 'priority' => 11, 'accepted_args' => 1],
    ],
    'filters' => [
        'rwpa_get_payment_methods' => ['callable' => [RWPPayment::class, 'getPaymentSources'], 'priority' => 11, 'accepted_args' => 2],
        'rwpa_payment_process_sources' => function () {
            return [
                ['callable' => [Offline::class, 'addOfflinePayment'], 'priority' => 10, 'accepted_args' => 2],
                ['callable' => [Coupon::class, 'addCouponPayment'], 'priority' => 10, 'accepted_args' => 2],
                //                ['callable' => [PayPal::class, 'addPaypalPayment'], 'priority' => 11, 'accepted_args' => 2]
            ];
        },
    ]
];

return [
    'store_front_hooks' => $store_front_hooks,
    'admin_hooks' => $admin_hooks
];
