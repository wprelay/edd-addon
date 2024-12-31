<?php

namespace RelayWp\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use Cartrabbit\Request\Request;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\AffiliateCoupon;
use RelayWp\Affiliate\Core\Models\Customer;
use RelayWp\Affiliate\Core\Models\CustomerDiscount;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Program;

class CartController
{
    public static function applyAffiliateCouponIfNotApplied($cart)
    {
        remove_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
        $urlVariable = Settings::getAffiliateReferralURLVariable();

        if (empty($referral_id = Request::cookie($urlVariable))) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $affiliateReferralId = $referral_id;

        $affiliate = Affiliate::query()->findBy('referral_code', $affiliateReferralId);

        if (!$affiliate) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $member = Member::query()->find($affiliate->member_id);

        if (!$member) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $program = Program::query()->find($affiliate->program_id);
        $customerDiscount = CustomerDiscount::query()->where("program_id = %d", [$program->id])->first();

        if (!Program::isValid($program) || empty($customerDiscount) || $customerDiscount->discount_type == 'no_discount') {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $affiliateCoupon = AffiliateCoupon::query()
            ->where("is_primary = %d", [1])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("deleted_at IS NULL")
            ->first();

        if (empty($affiliateCoupon)) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $coupon_id = $affiliateCoupon->woo_coupon_id;

        $coupon_code = wc_get_coupon_code_by_id($coupon_id);

        if (!$coupon_code) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $coupon = new \WC_Coupon($coupon_code);

        if (!apply_filters('rwpa_coupon_is_valid', $coupon->is_valid())) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $customer_removed_coupon = WC::getSession(\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::$customer_removed_coupon);

        $customer = function_exists('WC') ? WC()->customer : null;

        $billingEmail = null;

        //Customer has session get the billing email and check whether the affiliate email and billing email are same
        if ($customer) {
            $billingEmail = $customer->get_billing_email();
            $userEmail = $customer->get_email();
            $isOwnAffiliate = static::isOwnAffiliateCoupon($member, $billingEmail) || static::isOwnAffiliateCoupon($member, $userEmail);
        }

        $applied_coupons = WC()->cart->get_applied_coupons() ?? [];

        $applied_coupons = array_map(function ($item) {
            return strtolower($item);
        }, $applied_coupons);


        if (!in_array(strtolower($coupon_code), $applied_coupons) && $customer_removed_coupon != $coupon_code && !$isOwnAffiliate) {
            WC()->cart->apply_coupon($coupon_code);
        }

        add_action('woocommerce_after_calculate_totals', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
    }

    public static function removeInvalidCoupons($cart)
    {
        remove_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
        $urlVariable = Settings::getAffiliateReferralURLVariable();

        if (empty($referral_id = Request::cookie($urlVariable))) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $affiliateReferralId = $referral_id;
        $affiliate = Affiliate::query()->findBy('referral_code', $affiliateReferralId);

        if (!$affiliate) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $member = Member::query()->find($affiliate->member_id);

        if (!$member) return;

        $affiliateCoupon = AffiliateCoupon::query()
            ->where("is_primary = %d", [1])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->first();

        if (empty($affiliateCoupon)) {
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $program = Program::query()->find($affiliate->program_id);

        if (empty($program)) return;

        $coupon_code = $affiliateCoupon->coupon;

        if (!Program::isValid($program)) {
            WC()->cart->remove_coupon($coupon_code);
            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $customer = function_exists('WC') ? WC()->customer : null;

        $billingEmail = null;

        $isOwnAffiliateCoupon = false;

        if (!empty($customer)) {
            $billingEmail = $customer->get_billing_email();
            $userEmail = $customer->get_email();
            $isOwnAffiliateCoupon = static::isOwnAffiliateCoupon($member, $billingEmail) || static::isOwnAffiliateCoupon($member, $userEmail);
        } else if (!empty(\RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::$billing_email)) {
            $billingEmail = \RelayWp\Affiliate\Core\Controllers\StoreFront\CouponController::$billing_email;
            $isOwnAffiliateCoupon = static::isOwnAffiliateCoupon($member, $billingEmail);
        }

        if (in_array($coupon_code, WC()->cart->get_applied_coupons())) {
            if ($isOwnAffiliateCoupon) {
                WC()->cart->remove_coupon($coupon_code);
            }

            WC()->cart->calculate_totals();

            add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        add_action('woocommerce_after_calculate_totals', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
    }

    public static function isOwnAffiliateCoupon($member, $email)
    {
        return !empty($email) && $email == $member->email;
    }
}
