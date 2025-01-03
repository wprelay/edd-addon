<?php

namespace EDDA\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\Core\Models\Affiliate;
use EDDA\Affiliate\Core\Models\Member;

class CouponController
{
    public static $billing_email;
    public static $customer_removed_coupon = 'customer_removed_coupon';

    public static function getBillingEmail($post_data)
    {
        parse_str($post_data, $data);

        $billing_email = isset($data['billing_email']) ? $data['billing_email'] : null;

        if (empty($billing_email)) return;

        $referralCode = Functions::isAffiliateCookieSet();

        if (empty($referralCode)) {
            return;
            //check whether the request has affiliate_id cookie and it exists in db
        }

        //        $affiliate = Affiliate::query()->findBy('referral_code', $referralCode);
        //
        //        if (empty($affiliate)) return;
        //
        //        $member = Member::query()->find($affiliate->member_id);
        //
        //        if (empty($member)) return;

        //        if ($member->email == $billing_email) {
        static::$billing_email = $billing_email;
        //        }
    }

    public static function couponRemovedManually($coupon_code)
    {
        $affiliate = Affiliate::query()->where('referral_code = %s', [$coupon_code])->first();

        $discount_id = edd_get_discount_id_by_code( $coupon_code );
        $coupon =edd_get_discount($discount_id);

        if (!$coupon->is_valid()) {
            return;
        }

        if (empty($affiliate)) {
            return;
        }
        EDD::setSession(static::$customer_removed_coupon, $coupon_code);
    }

    public static function clearCustomerRemovedCoupon($hook)
    {
        EDD::setSession(static::$customer_removed_coupon, null);
    }
}
