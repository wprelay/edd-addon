<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;
use RelayWp\Affiliate\Core\Models\CustomerDiscount;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\Core\Models\AffiliateCoupon;

class Affiliate extends Model
{
    public  function createTable()
    {

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

}
