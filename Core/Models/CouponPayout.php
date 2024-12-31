<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;

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
        if (!function_exists('WC')) {
            return false;
        }

        // Check if WooCommerce is activated
        if (!class_exists('WC_Coupon')) {
            return false;
        }

        $coupon =  new \WC_Coupon();

        // Set coupon code
        $coupon_code = $couponData['code'];

        $coupon->set_code($coupon_code);

        $coupon->set_status($couponData['status']);

        // Set coupon discount type and amount
        $coupon->set_discount_type($couponData['discount_type']);
        $coupon->set_amount($couponData['discount_value']);

        // Set other coupon details based on $couponData
        $coupon->set_individual_use($couponData['individual_use']);

        $usage_limit_per_user = $couponData['usage_limit_per_user'] ?? null;

        $coupon->set_usage_limit_per_user($couponData['usage_limit_per_user']);

        $coupon->set_usage_limit($couponData['usage_limit_per_coupon']);
        $coupon->set_minimum_amount($couponData['minimum_amount']);

        $coupon_id = $coupon->save();

        return $coupon_id;
    }
}
