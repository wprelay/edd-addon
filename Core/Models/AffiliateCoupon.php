<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;
use EDD_Discount;
class AffiliateCoupon extends Model
{
    protected static $table = 'affiliate_coupons';

    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();
        return "
                CREATE TABLE {$table} (
                    id                          BIGINT AUTO_INCREMENT,
                    woo_coupon_id                      BIGINT NOT NULL,
                    affiliate_id                      BIGINT NOT NULL,
                    coupon                      VARCHAR(255)   NOT NULL,
                    is_primary                  INT  default 0,
                    discount_type               VARCHAR(255)   NOT NULL,
                    discount_value                      DECIMAL(30, 2) NOT NULL,
                    status                      VARCHAR(255)   NOT NULL,
                    date_expires                BIGINT       NULL,
                    individual_use              bool  default 0,
                    product_ids                 JSON           NULL,
                    excluded_product_ids        JSON           NULL,
                    usage_limit_per_user        INT            NULL,
                    usage_limit_per_coupon        INT            NULL,
                    usage_limit_to_x_items      INT            NULL,
                    free_shipping               bool default false,
                    product_categories          JSON           NULL,
                    excluded_product_categories JSON           NULL,
                    excluded_sale_items         bool default false,
                    minimum_amount         decimal(20, 2) NULL ,
                    maximum_amount         decimal(20, 2) NULL,
                    email_restrictions         JSON NULL,
                    used_by         JSON NULL,
                    custom_field         VARCHAR(255) NULL,
                    custom_fields         JSON NULL, 
                    additional_data         JSON NULL, 
                    discount_amount         decimal(20, 2),
                    created_at timestamp NOT NULL DEFAULT current_timestamp(),
                    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    deleted_at timestamp NULL,
                    PRIMARY KEY (id)
            ) {$charset};";
    }

    public static function getDiscountData($discount_id)
    {
        // Get the discount object
        $discount = new EDD_Discount($discount_id);

        // Prepare the data array
        $data = [
            'discount_code'          => $discount->get_code(),
            'edd_discount_id'        => $discount->get_id(),
            'is_primary'             => 1, // Assuming this is always 1, modify if needed
            'discount_type'          => $discount->get_discount_type(),
            'discount_value'         => $discount->get_amount(),
            'status'                 => $discount->get_status() ?? 'publish',
            'date_expires'           => $discount->get_date_expires() ? strtotime($discount->get_date_expires()) : null,
            'individual_use'         => $discount->get_individual_use(),
            'product_ids'            => Functions::arrayToJson($discount->get_product_ids()),
            'excluded_product_ids'   => Functions::arrayToJson($discount->get_excluded_product_ids()),
            'usage_limit_per_user'   => $discount->get_usage_limit_per_user(),
            'usage_limit_per_discount' => $discount->get_usage_limit(),
            'usage_limit_to_x_items' => $discount->get_limit_usage_to_x_items(),
            'free_shipping'          => $discount->get_free_shipping(),
            'product_categories'     => Functions::arrayToJson($discount->get_product_categories()),
            'excluded_product_categories' => Functions::arrayToJson($discount->get_excluded_product_ids()),
            'excluded_sale_items'    => $discount->get_exclude_sale_items(),
            'minimum_amount'         => $discount->get_minimum_amount(),
            'maximum_amount'         => $discount->get_maximum_amount(),
            'email_restrictions'     => Functions::arrayToJson($discount->get_email_restrictions()),
            'used_by'                => Functions::arrayToJson($discount->get_used_by()),
            'discount_amount'        => $discount->get_discount_amount($discount->get_amount()),
        ];

        return apply_filters('rwpa_get_discount_data', $data, $discount);
    }

    public static function updateCouponCode($affiliate, $newCode)
    {
        $affiliateCoupon = self::query()->where('affiliate_id = %d', [$affiliate->id])
            ->where('is_primary = %d', [1])
            ->first();

        if (!$affiliateCoupon) return false;

        $coupon = new \WC_Coupon($affiliateCoupon->coupon);

        $coupon->set_code($newCode);

        $coupon->save();

        AffiliateCoupon::query()->update([
            'coupon' => $newCode,
            'updated_at' => Functions::currentUTCTime()
        ], ['id' => $affiliateCoupon->id]);

        return true;
    }
}
