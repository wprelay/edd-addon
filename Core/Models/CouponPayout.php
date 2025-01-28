<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Model;

class CouponPayout extends Model
{
    protected static $table = 'coupon_payouts';

    public const OPTION_KEY = 'rwpa_payout_coupon_settings';

    public function createTable()
    {
        $charset = static::getCharSetCollate();

        $table = static::getTableName();

        return "CREATE TABLE {$table} (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                payout_id BIGINT UNSIGNED,
                coupon_id BIGINT UNSIGNED NULL,
                coupon_code VARCHAR(255) NULL,
                extra_data JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
                updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                deleted_at TIMESTAMP NULL,
                PRIMARY KEY (id)
                ) {$charset};";
    }

    public static function getCouponDefaults()
    {
        return [
            'status' => 'publish',
            'apply_before_tax' => true,
            'individual_use' => false,
            'usage_limit_per_user' => 1,
            'usage_limit_per_coupon' => 1,
        ];
    }

    public static function createCouponForPayout($couponData)
    {
        if (!function_exists('edd_store_discount')) {
            return false;
        }
        $discountData = [
            'name'              => $couponData['code'], // Coupon name
            'code'              => $couponData['code'], // Coupon code
            'status'            => $couponData['status'] === 'publish' ? 'active' : 'inactive', // Status (e.g., 'active')
            'type'              => $couponData['discount_type'] === 'percent' ? 'percent' : 'flat', // Discount type: 'flat' or 'percent'
            'amount'            => $couponData['discount_value'], // Discount amount
            'uses'              => $couponData['usage_limit_per_coupon'], // Usage limit for the coupon
            'min_price'         => $couponData['minimum_amount'], // Minimum cart total to apply the coupon
            'start'             => '', // Start date (optional)
            'expiration'        => $couponData['date_expires'] ?? '', // Expiration date
            'once_per_customer' => $couponData['usage_limit_per_user'] ? 1 : 0, // Limit to one use per customer
            'product_reqs'      => [], // Required products (empty for no restrictions)
            'excluded_products' => [], // Excluded products
            'apply_before_tax'  => $couponData['apply_before_tax'], // Apply before tax
            'global'            => !$couponData['individual_use'], // Global discount
        ];
        $discount_id = edd_store_discount($discountData);

        if (empty($discount_id)) {
            return false;
        }
        return $discount_id;
    }
}
