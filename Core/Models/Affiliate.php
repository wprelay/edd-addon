<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Models\CustomerDiscount;
use EDDA\Affiliate\App\Helpers\EDD;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use Cartrabbit\Request\Request;
use EDDA\Affiliate\App\Model;
use RelayWp\Affiliate\App\Services\Settings;
use WP_Error;
class Affiliate extends Model
{
    protected static $table = 'affiliates';

    public const AFFILIATE_META_KEY_FOR_ORDER = '_relay_wp_affiliate_code';
    public const ORDER_FROM_META_KEY = '_relay_wp_order_from';
    public const  ORDER_AFFILIATE_FOR = '_relay_wp_affiliate_id';
    public const  ORDER_AFFILIATE_SESSION_EMAIL = '_relay_wp_order_session_email';
    public const  ORDER_RECURRING_ID = '_relay_wp_recurring_order_id';


    public function createTable()
    {
        //relaywp code
    }


    public static function getReferralCodeURL($affiliate, $key = 'referral_code')
    {
        $code = $affiliate->{$key};

        $shopURL = self::getHomeURL();
        $query = wp_parse_url($shopURL, PHP_URL_QUERY);

        $urlVariable = Settings::getAffiliateReferralURLVariable();

        if (empty($query)) {
            return $shopURL . "?{$urlVariable}={$code}";
        } else {
            return $shopURL . "&{$urlVariable}={$code}";
        }
    }

    public static function getHomeURL()
    {
        return get_home_url();
    }

    public static function createCoupon($affiliate, $program)
    {
        add_filter('is_create_coupon', function($value) {
            return false;
        });
        $customerDiscount = CustomerDiscount::query()->where("program_id = {$program->id}")->first();

        $couponData = static::getEddDiscountData($affiliate, $program, $customerDiscount);
        $couponId = EDD::saveDiscount($couponData);
        if (empty($couponId)) return false;
        $data = AffiliateCoupon::getDiscountData($couponId);

        AffiliateCoupon::query()->create(array_merge($data, [
            'affiliate_id' => $affiliate->id,
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime()
        ]));
    }
    public static function getEddDiscountData($affiliate, $program, $customerDiscount)
    {

        return [
            'description'       => $program->description,
            'status'            => 'active', // Discount status
            'name'              => $program->title, // Use the program title as the discount name
            'code'              => $affiliate->referral_code, // Use the affiliate's referral code
            'amount'            => $customerDiscount->coupon_amount, // Discount amount
            'amount_type'       => self::getEddCouponDiscountType($customerDiscount->discount_type), // Discount type (percent or flat)
            'product_reqs'      => $customerDiscount->product_ids ? Functions::jsonDecode($customerDiscount->product_ids) : [], // Required products
            'product_condition' => 'all', // Apply to all required products
            'scope'             => 'not_global', // Not global by default
            'excluded_products' => $customerDiscount->exclude_product_ids ? Functions::jsonDecode($customerDiscount->exclude_product_ids) : [], // Excluded products
            'min_charge_amount' => $customerDiscount->min_requirements_enabled ? $customerDiscount->minimum_amount : null, // Minimum charge
            'max_uses'          => $customerDiscount->usage_limit_enabled ? $customerDiscount->usage_limit_per_user : null, // Usage limit
            'once_per_customer' => $customerDiscount->usage_limit_per_user ? 1 : 0, // Restrict to one use per customer
            'type'              => 'discount', // Discount type
            'start_date'        => $program->start_date, // Use program start date
            'end_date'          => $program->end_date, // Use program end date
            'meta_data'         => [
                'affiliate_id' => $affiliate->id, // Store affiliate ID as metadata
                'program_id'   => $program->id, // Store program ID as metadata
                'free_shipping' => $customerDiscount->free_shipping, // Include free shipping info
            ],
        ];
    }

    public static function getEddCouponDiscountType($type)
    {
        if($type=='percent'){
            return 'percent';
        }
        return 'flat';
    }
    public static function updateCoupon($affiliate, $program, $customerDiscount)
    {
        $affiliateCoupon = AffiliateCoupon::query()
            ->where('affiliate_id = %s', [$affiliate->id])
            ->where('is_primary = %d', [1])
            ->where('deleted_at IS NULL')
            ->first();

        if (empty($affiliateCoupon)) return false;

        $couponData = static::getEddDiscountData($affiliate, $program, $customerDiscount);
        $coupon_code = edd_get_discount_code($affiliateCoupon->woo_coupon_id);

        $coupon = edd_get_discount_by_code($coupon_code);

        $couponId = EDD::saveDiscount($couponData, $coupon);


        $data = AffiliateCoupon::getDiscountData($coupon);

        AffiliateCoupon::query()->update(array_merge($data, [
            'affiliate_id' => $affiliate->id,
            'updated_at' => Functions::currentUTCTime()
        ]), ['id' => $affiliateCoupon->id]);
    }

    public static function createWPAccount($member, $affiliate)
    {
        try {
            $parts = explode("@", $member->email);
            $username= $parts[0];
            $password = WordpressHelper::generateRandomPassword(10);
            if ( username_exists( $username ) || email_exists( $member->email ) ) {
                return new WP_Error( 'user_exists', 'User with that username or email already exists.' );
            }

            $userdata = [
                'user_login' => $username,
                'user_pass'  => $password,
                'user_email' => $member->email,
                'first_name' => $member->first_name,
                'last_name'  => $member->last_name,
                'role'       => 'shop_worker', // Set default role
            ];
            $user_id = wp_insert_user($userdata);
            if ( is_wp_error( $user_id ) ) {
                return $user_id;
            }
            return $user_id;
        } catch (\Error $error) {
            return false;
        }
    }
    public static function isAffiliateApproved($status): bool
    {
        return $status == 'approved';
    }
}
