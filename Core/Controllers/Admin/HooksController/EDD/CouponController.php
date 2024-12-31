<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Route;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\AffiliateCoupon;
use WC_Coupon;

class CouponController
{
    public static function detectCouponUpdate($post_id, $post, $update)
    {
        if ($post->post_type != 'shop_coupon' || !$update) {
            return;
        }

        $affiliateCoupon = AffiliateCoupon::query()->where("woo_coupon_id = %d", [$post_id])->first();

        if (!$affiliateCoupon) {
            return;
        }

        $coupon = new \WC_Coupon($post_id);

        Affiliate::query()->update([
            'referral_code' => $coupon->get_code()
        ], ['id' => $affiliateCoupon->affiliate_id]);

        AffiliateCoupon::query()->update(AffiliateCoupon::getCouponData($coupon), ['id' => $affiliateCoupon->id]);
    }

    public static function couponTrashed($post_id)
    {
        if (get_post_type($post_id) === 'shop_coupon') {
            // Perform your tracking or logging here

            $coupon = new \WC_Coupon($post_id);

            $couponCode = $coupon->get_code();

            $affiliateCoupon = AffiliateCoupon::query()->where("coupon = %s", [$couponCode])->first();

            if (empty($affiliateCoupon)) return;


            AffiliateCoupon::query()->update([
                'deleted_at' => Functions::currentUTCTime()
            ], [
                'id' => $affiliateCoupon->id
            ]);
        }
    }

    public static function addCouponFilter($types)
    {
        if (current_user_can('manage_woocommerce')) {
            $request = Route::getRequestObject();

            $class = $request->get('filter-by') === 'is_rwp_coupon' ? 'current' : '';
            $admin_url = admin_url('edit.php?post_type=shop_coupon');
            $query_string = add_query_arg(array('filter-by' => rawurlencode('is_rwp_coupon')), $admin_url);

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $query = new \WP_Query(array('post_type' => 'shop_coupon', 'meta_key' => 'is_rwp_coupon', 'meta_value' => true));
            $pluginName = RWPA_PLUGIN_NAME;

            $types['rwp_coupon'] = wp_kses_post('<a href="' . esc_url($query_string) . '" class="' . esc_attr($class) . '">' . esc_attr("{$pluginName} Coupons") . ' (' . $query->found_posts . ')</a>');
        }

        return $types;
    }

    public static function filterCustomCoupons($query)
    {
        global $pagenow;

        $request = Route::getRequestObject();

        $filterBy = $request->get('filter-by');
        $post_type = $request->get('post_type');

        // Ensure it is an edit.php admin page, the filter exists and has a value, and that it's the products page
        if ($query->is_admin && $pagenow == 'edit.php' && $filterBy == 'is_rwp_coupon' && $post_type == 'shop_coupon') {

            // Create meta query array and add to WP_Query
            $meta_key_query = array(
                array(
                    'key' => 'is_rwp_coupon',
                    'value' => true,
                )
            );
            $query->set('meta_query', $meta_key_query);
        }
    }
}
