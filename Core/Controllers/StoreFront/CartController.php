<?php

namespace EDDA\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use Cartrabbit\Request\Request;
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use EDDA\Affiliate\Core\Models\AffiliateCoupon;
//use EDDA\Affiliate\Core\Models\Customer;
use EDDA\Affiliate\Core\Models\CustomerDiscount;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Program;

class CartController
{
    public static function applyAffiliateCouponIfNotApplied($cart)
    {
        error_log('enetereddddddddddddddddddd this function');
        remove_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
        $urlVariable = Settings::getAffiliateReferralURLVariable();
        if (empty($referral_id = Request::cookie($urlVariable))) {
            error_log('enetereddddddddddddddddddd');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }
        $affiliateReferralId = $referral_id;

        $affiliate = Affiliate::query()->findBy('referral_code', $affiliateReferralId);

        if (!$affiliate) {
            error_log('enetereddddddddddddddddddd $affiliate');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $member = Member::query()->find($affiliate->member_id);
        if (!$member) {
            error_log('$member enetereddddd');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $program = Program::query()->find($affiliate->program_id);
        $customerDiscount = CustomerDiscount::query()->where("program_id = %d", [$program->id])->first();

        if (!Program::isValid($program) || empty($customerDiscount) || $customerDiscount->discount_type == 'no_discount') {
            error_log('Program::isValid($program) || empty($customerDiscount)');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $affiliateCoupon = AffiliateCoupon::query()
            ->where("is_primary = %d", [1])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("deleted_at IS NULL")
            ->first();
        if (empty($affiliateCoupon)) {
            error_log('$affiliateCoupon');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $coupon_id = $affiliateCoupon->woo_coupon_id;

        $coupon_code = edd_get_discount_code( $coupon_id );

        if (!$coupon_code) {
            error_log('$coupon_code');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }
        $discount_id = edd_get_discount_id_by_code( $coupon_code );
        $coupon =edd_get_discount($discount_id);
        if (!apply_filters('rwpa_coupon_is_valid', $coupon->is_valid())) {
            error_log('apply_filters');
            add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
            return;
        }

        $customer_removed_coupon = EDD::getSession(\EDDA\Affiliate\Core\Controllers\StoreFront\CouponController::$customer_removed_coupon);


        $applied_coupons = edd_get_cart_discounts();
        $applied_coupons = array_map(function ($item) {
            return strtolower($item);
        }, $applied_coupons);


        if (!in_array(strtolower($coupon_code), $applied_coupons) && $customer_removed_coupon != $coupon_code ) {
            error_log('edd_set_cart_discount');
            edd_set_cart_discount($coupon_code);
        }
        add_action('edd_before_checkout_cart', [__CLASS__, 'applyAffiliateCouponIfNotApplied'], 10, 1);
    }

    public static function removeInvalidCoupons($cart)
    {
        remove_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
        $urlVariable = Settings::getAffiliateReferralURLVariable();

        if (empty($referral_id = Request::cookie($urlVariable))) {
            add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $affiliateReferralId = $referral_id;
        $affiliate = Affiliate::query()->findBy('referral_code', $affiliateReferralId);

        if (!$affiliate) {
            add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $member = Member::query()->find($affiliate->member_id);

        if (!$member) return;

        $affiliateCoupon = AffiliateCoupon::query()
            ->where("is_primary = %d", [1])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->first();

        if (empty($affiliateCoupon)) {
            add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        $program = Program::query()->find($affiliate->program_id);

        if (empty($program)) return;

        $coupon_code = $affiliateCoupon->coupon;

        if (!Program::isValid($program)) {
            error_log('edd_unset_cart_discount 1');
            edd_unset_cart_discount( $coupon_code );
            add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }
        $discount = edd_get_discount_by_code( $coupon_code );
        if (in_array($coupon_code, edd_get_cart_discounts()) && !empty( $discount->id ) && $discount->is_valid()) {
            error_log("unset cart discount...called");
            edd_unset_cart_discount($coupon_code);
            add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
            return;
        }

        add_action('edd_before_checkout_cart', [__CLASS__, 'removeInvalidCoupons'], 11, 1);
    }

    public static function isOwnAffiliateCoupon($member, $email)
    {
        return !empty($email) && $email == $member->email;
    }

}
