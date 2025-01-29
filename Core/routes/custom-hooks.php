<?php

defined("ABSPATH") or exit;

use EDDA\Affiliate\Core\Controllers\Admin\General\BackgroundJobController;
use EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD\EmailController;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Payments\Coupon;
use EDDA\Affiliate\Core\Models\CouponPayout;
use RelayWp\Affiliate\Core\Payments\Offline;
use RelayWp\Affiliate\Core\Payments\RWPPayment;
use EDDA\Affiliate\Core\ShortCodes\ShortCodes;
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\Core\Models\Affiliate;
use EDDA\Affiliate\Core\Controllers\Admin\EDDController;
use EDDA\Affiliate\Core\Models\AffiliateCoupon;
use EDDA\Affiliate\App\Helpers\RulesHelper;
use EDDA\Affiliate\Core\Models\CommissionEarning;
use EDDA\Affiliate\Core\Controllers\StoreFront\OrderController;
use EDDA\Affiliate\Core\Controllers\Hooks\CommissionTierController;

$store_front_hooks = [
    'actions' => [
        'rwpa_auto_approve_commission' => ['callable' => [BackgroundJobController::class, 'autoApproveCommission'], 'priority' => 9, 'accepted_args' => 1],
    ],
    'filters' => [
        'rwpa_get_commission_details_for_edd_fixed_type' => ['callable' => [CommissionTier::class, 'getFixedAmountDetail'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_commission_details_for_edd_percentage_per_sale_type' => ['callable' => [CommissionTier::class, 'getPercentagePerSaleAmountDetail'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_recursive_data_to_store' => ['callable' => [CommissionTier::class, 'getRecursiveDataToStore'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_core_recursive_data_to_store' => ['callable' => [CommissionTier::class, 'getCoreRecursiveData'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_edd_track_affiliate_order' => function(){
            return [
                ['callable' => [Order::class, 'isNeedToTrackTheOrder'], 'priority' => 10, 'accepted_args' => 2],
                ['callable' => [OrderController::class, 'isRecurringOrder'], 'priority' => 11, 'accepted_args' => 2],
            ];
        },
        'rwpa_get_commission_details_for_edd_tier_based_type' => ['callable' => [CommissionTierController::class, 'getTierBasedAmountDetail'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_get_shortcodes_classes' => ['callable' => [ShortCodes::class, 'getShortCodes'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_coupon_payment_available_for_currency' => ['callable' => [Coupon::class, 'isCouponPaymentAvailable'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_edd_wprelay_calculate_bonus_from_rules' => ['callable' => [RulesHelper::class, 'calculateBonusCommission'], 'priority' => 10, 'accepted_args' => 4],

        'rwpa_get_default_currency' => ['callable' => [EDD::class, 'getDefaultCurrency'], 'priority' => 11, 'accepted_args' => 0],
        'rwpa_get_currency_list' => ['callable' => [EDD::class, 'getCurrencyList'], 'priority' => 11, 'accepted_args' => 0],
        'rwpa_get_currency_symbol' => ['callable' => [EDD::class, 'getEDDCurrencySymbol'], 'priority' => 11, 'accepted_args' => 0],
        'rwpa_get_currency_symbol_with_code' => ['callable' => [EDD::class, 'getEDDCurrencySymbolCode'], 'priority' => 11, 'accepted_args' => 1],
        'rwpa_format_amount' => ['callable' => [Functions::class, 'formatAmount'], 'priority' => 20, 'accepted_args' => 2],
        'rwpa_create_coupon' => ['callable' => [Affiliate::class, 'createCoupon'], 'priority' => 5, 'accepted_args' => 2],
        'edda_search_product_list' => ['callable' => [EDDController::class, 'getProductsList'], 'priority' => 10, 'accepted_args' => 1],
        'edda_search_categories_list' => ['callable' => [EDDController::class, 'getCategoriesList'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_set_session' => ['callable' => [EDD::class, 'setSession'], 'priority' => 8, 'accepted_args' => 2],
        'rwpa_get_session' => ['callable' => [EDD::class, 'getSession'], 'priority' => 8, 'accepted_args' => 2],
        'rwpa_get_edd_countries' => ['callable' => [EDD::class, 'getEDDCountries'], 'priority' => 10, 'accepted_args' => 0],
        'rwpa_get_search_countries' => ['callable' => [EDDController::class, 'getSearchEDDCountries'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_get_search_states' => ['callable' => [EDDController::class, 'getSearchEDDStates'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_edd_get_order_status' => ['callable' => [EDD::class, 'getOrderStatusSettings'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_update_affiliate_coupons_in_db' => ['callable' => [Affiliate::class, 'updateCoupon'], 'priority' => 9, 'accepted_args' => 3],
        'rwpa_get_commission_details_for_edd_rule_based_type' => ['callable' => [RulesHelper::class, 'calculateCommissions'], 'priority' => 10, 'accepted_args' => 4],
        'rwpa_edd_create_commission_earning' => ['callable' => [CommissionEarning::class, 'createCommissionEarning'], 'priority' => 10, 'accepted_args' => 5],
        'rwpa_create_coupon_for_payout' => ['callable' => [CouponPayout::class, 'createCouponForPayout'], 'priority' => 11, 'accepted_args' => 1],
        'rwpa_is_coupon_available' => ['callable' => [EDD::class, 'isCouponAvailable'], 'priority' => 11, 'accepted_args' => 1],
        'rwpa_get_states_with_label' => ['callable' => [EDD::class, 'getStateWithLabel'], 'priority' => 11, 'accepted_args' => 3],
        'rwpa_get_country_with_label' => ['callable' => [EDD::class, 'getCountryWithLabel'], 'priority' => 10, 'accepted_args' => 1],
        'rwpa_affiliate_create_account' => ['callable' => [Affiliate::class, 'createWPAccount'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_update_coupon_code' => ['callable' => [AffiliateCoupon::class, 'updateDiscountCode'], 'priority' => 10, 'accepted_args' => 2],
        'rwpa_is_coupon_exist' => ['callable' => [EDD::class, 'isCouponExists'], 'priority' => 10, 'accepted_args' => 1],
    ]
];

$admin_hooks = [
    'actions' => [
        'rwpa_send_affiliate_approved_email' => ['callable' => [EmailController::class, 'sendAffiliateApprovedEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_send_affiliate_rejected_email' => ['callable' => [EmailController::class, 'sendAffiliateRejectedEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_send_new_sale_made_email' => ['callable' => [EmailController::class, 'newSaleMadeEmail'], 'priority' => 5, 'accepted_args' => 2],
        'rwpa_payment_processed_email' => ['callable' => [EmailController::class, 'paymentProcessedEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_send_affiliate_registered_email' => ['callable' => [EmailController::class, 'affiliateRegisteredEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_affiliate_commission_approved_email' => ['callable' => [EmailController::class, 'commissionApprovedEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_affiliate_commission_rejected_email' => ['callable' => [EmailController::class, 'commissionRejectedEmail'], 'priority' => 5, 'accepted_args' => 1],
        'rwpa_record_rwt_payment' => ['callable' => [RWPPayment::class, 'processPayment'], 'priority' => 10, 'accepted_args' => 2],
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
