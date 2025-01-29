<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;
use EDD_Discount;
class AffiliateCoupon extends Model
{
    protected static $table = 'affiliate_coupons';

    public function createTable()
    {
        //relaywp code
    }

    public static function getDiscountData($discount_id)
    {
        // Get the discount object
        $discount = new EDD_Discount($discount_id);
        // Prepare the data array
        $data =  [

            'coupon' => $discount->code ?? '', // Discount code
            'woo_coupon_id' => $discount->id ?? 0, // WooCommerce coupon ID
            'is_primary' => 1, // Mark as primary
            'discount_type' => $discount->amount_type ?? 'flat', // Type of discount
            'discount_value' => $discount->amount ?? 0, // Discount value
            'status' => $discount->status ?? 'inactive', // Status
            'date_expires' => $discount->end_date ? strtotime($discount->end_date) : null, // Expiration date
            'individual_use' => $discount->once_per_customer ?? 0, // Individual use flag
            'product_ids' => Functions::arrayToJson($discount->product_reqs ?? []), // Product IDs (JSON)
            'excluded_product_ids' => Functions::arrayToJson($discount->excluded_products ?? []), // Excluded product IDs (JSON)
            'usage_limit_per_user' => $discount->once_per_customer ?? 0, // Usage limit per user
            'usage_limit_per_coupon' => $discount->max_uses ?? 0, // Usage limit per coupon
            'usage_limit_to_x_items' => 0, // Limit to specific items (not implemented)
            'free_shipping' => 0, // Free shipping flag
            'product_categories' => Functions::arrayToJson([]), // Product categories (JSON)
            'excluded_product_categories' => Functions::arrayToJson([]), // Excluded categories (JSON)
            'excluded_sale_items' => 0, // Excluded sale items flag
            'minimum_amount' => $discount->min_charge_amount ?? 0, // Minimum amount
            'maximum_amount' => 0, // Maximum amount (not implemented)
            'email_restrictions' => null, // Email restrictions (not implemented)
            'used_by' => Functions::arrayToJson([]), // List of users who used the coupon (placeholder logic)
            'custom_field' => null, // Custom field (not implemented)
            'custom_fields' => Functions::arrayToJson([]), // Custom fields (JSON)
            'additional_data' => Functions::arrayToJson([]), // Additional data (JSON)
            'discount_amount' => $discount->amount ?? 0, // Discount amount
        ];

        return apply_filters('rwpa_get_discount_data', $data, $discount);
    }
    public static function updateDiscountCode($affiliate, $newCode)
    {
        $affiliateDiscount = self::query()->where('affiliate_id = %d', [$affiliate->id])
            ->where('is_primary = %d', [1])
            ->first();

        if (!$affiliateDiscount) return false;

        $discount = edd_get_discount($affiliateDiscount->coupon);

        if (!$discount) return false;

        edd_update_discount($discount->id, [
            'code'       => $newCode,
            'modified'   => Functions::currentUTCTime(), // Updating timestamp
        ]);

        AffiliateCoupon::query()->update([
            'coupon' => $newCode,
            'updated_at' => Functions::currentUTCTime()
        ], ['id' => $affiliateDiscount->id]);

        return true;
    }
}
