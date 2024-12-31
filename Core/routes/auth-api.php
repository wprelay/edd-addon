<?php

//All routes actions will be performed in Route::handleAuthRequest method.

defined("ABSPATH") or exit;

use RelayWp\Affiliate\Core\Controllers\Api\CommissionController;
use RelayWp\Affiliate\Core\Controllers\Api\PayoutController;
use RelayWp\Affiliate\Core\Controllers\Api\ProgramController;
use RelayWp\Affiliate\Core\Controllers\Api\AffiliateController;
use RelayWp\Affiliate\Core\Controllers\Api\DashboardController;
use RelayWp\Affiliate\Core\Controllers\Api\SettingsController;
use RelayWp\Affiliate\Core\Controllers\Admin\WcController;
use RelayWp\Affiliate\Core\Controllers\LocalDataController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\MenuController;
use RelayWp\Affiliate\Core\Controllers\StoreFront\RegisterController;


//this actions will be prefixed by Route::constants

return [
    'playground' => ['callable' => [DashboardController::class, 'playground']],
    'get_local_data' => ['callable' => [LocalDataController::class, 'getLocalData']],

    //Dashboard
    'dashboard_get_orders' => ['callable' => [DashboardController::class, 'getOrders']],
    'dashboard_get_commissions' => ['callable' => [DashboardController::class, 'getCommissions']],
    'dashboard_get_visits' => ['callable' => [DashboardController::class, 'getVisits']],
    'dashboard_benchmark_details' => ['callable' => [DashboardController::class, 'getBenchMarkDetails']],

    //Affiliate
    'create_affiliate' => ['callable' => [AffiliateController::class, 'create']],
    'update_affiliate_profile' => ['callable' => [AffiliateController::class, 'update']],
    'update_referral_code' => ['callable' => [AffiliateController::class, 'updateAffiliateCode']],
    'update_affiliate_status' => ['callable' => [AffiliateController::class, 'updateStatus']],
    'affiliate_list' => ['callable' => [AffiliateController::class, 'index']],
    'fetch_affiliate' => ['callable' => [AffiliateController::class, 'show']],
    'affiliate_commission_balance' => ['callable' => [CommissionController::class, 'getCommissionBalance']],
    'fetch_commission_details' => ['callable' => [CommissionController::class, 'getCommissionBalance']],
    'affiliate_sales' => ['callable' => [AffiliateController::class, 'sales']],
    'affiliate_sales_overview' => ['callable' => [AffiliateController::class, 'getAffiliateSalesOverview']],
    'affiliate_commissions' => ['callable' => [AffiliateController::class, 'commissions']],
    'affiliate_commission_overview' => ['callable' => [AffiliateController::class, 'getAffiliateCommissionOverview']],
    'affiliate_payouts' => ['callable' => [AffiliateController::class, 'payouts']],
    'affiliate_transactions' => ['callable' => [AffiliateController::class, 'transactions']],
    'affiliate_payout_overview' => ['callable' => [AffiliateController::class, 'getAffiliatePayoutOverview']],

    'affiliate_coupons' => ['callable' => [AffiliateController::class, 'coupons']],
    'affiliate_regenerate_coupon' => ['callable' => [AffiliateController::class, 'regenerateCoupon']],
    'check_coupon_is_already_exists' => ['callable' => [AffiliateController::class, 'isValidCoupon']],
    'change_affiliate_program' => ['callable' => [AffiliateController::class, 'changeProgram']],

    //programs
    'program_overview' => ['callable' => [ProgramController::class, 'overview']],
    'program_list' => ['callable' => [ProgramController::class, 'index']],
    'program_with_commission_tiers' => ['callable' => [ProgramController::class, 'commissionTiersDetails']],
    'program_create' => ['callable' => [ProgramController::class, 'create']],
    'program_update' => ['callable' => [ProgramController::class, 'update']],
    'program_update_status' => ['callable' => [ProgramController::class, 'updateStatus']],
    'fetch_program' => ['callable' => [ProgramController::class, 'fetchProgram']],


    'update_commission_status' => ['callable' => [CommissionController::class, 'updateStatus']],

    //select2
    'get_programs_for_select2' => ['callable' => [ProgramController::class, 'getProgramListForSelect']],
    'get_products_for_select2' => ['callable' => [WcController::class, 'getProductsList']],
    'get_categories_for_select2' => ['callable' => [WcController::class, 'getCategoriesList']],
    'get_affiliates_for_select2' => ['callable' => [AffiliateController::class, 'getAffiliatesList']],

    //payouts
    'record_payout' => ['callable' => [PayoutController::class, 'recordPayout']],
    'record_bulk_payout' => ['callable' => [PayoutController::class, 'recordBulkPayout']],
    'fetch_payment_methods' => ['callable' => [PayoutController::class, 'getPaymentMethods']],


    'pending_payment_list' => ['callable' => [PayoutController::class, 'pendingPaymentList']],
    'payment_histories_list' => ['callable' => [PayoutController::class, 'paymentHistories']],
    'delete_payment_history' => ['callable' => [PayoutController::class, 'revertPayout']],

    //General
    'get_wc_countries' => ['callable' => [WcController::class, 'getWcCountries']],
    'get_wc_states' => ['callable' => [WcController::class, 'getWcStates']],


    //Settings
    'get_general_settings' => ['callable' => [SettingsController::class, 'getGeneralSettings']],
    'save_general_settings' => ['callable' => [SettingsController::class, 'saveGeneralSettings']],
    'get_affiliate_settings' => ['callable' => [SettingsController::class, 'getAffiliateSettings']],
    'save_affiliate_settings' => ['callable' => [SettingsController::class, 'saveAffiliateSettings']],
    'get_email_settings' => ['callable' => [SettingsController::class, 'getEmailSettings']],
    'save_email_settings' => ['callable' => [SettingsController::class, 'saveEmailSettings']],
    'get_payment_settings' => ['callable' => [SettingsController::class, 'getPaymentSettings']],
    'save_payment_settings' => ['callable' => [SettingsController::class, 'savePaymentSettings']],
    'update_payment_coupon_settings' => ['callable' => [SettingsController::class, 'saveCouponPaymentSettings']],
    'get_payment_coupon_settings' => ['callable' => [SettingsController::class, 'getCouponPaymentSettings']],

    'get_licence_details' => ['callable' => [SettingsController::class, 'getLicenceDetails']],
    'validate_license' => ['callable' => [SettingsController::class, 'validateLicense']],

    //Menu Api's
    'store_front_update_affiliate' => ['callable' => [MenuController::class, 'updateAffiliate']],
];
