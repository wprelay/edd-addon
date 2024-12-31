<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Services\View;
use RelayWp\Affiliate\Core\Models\Affiliate;
use Cartrabbit\Request\Request;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Route;

class OrderHook
{

    public static function render_custom_orders_filters()
    {
        $request = Route::getRequestObject();

        $post_type = $request->get('post_type');

        if (WC::isHPOSEnabled()) {
            $page = $request->get('page');
            if ('wc-orders' !== $page) {
                return;
            }
        } else {
            $post_type = $request->get('post_type');
            if ('shop_order' !== $post_type) {
                return;
            }
        }

        $selected = $request->get('rwp_order');

        if (empty($selected)) {
            $selected = 'all';
        }

        $content = View::render('admin/order_filter', [
            'selected' => $selected
        ]);

        echo wp_kses($content, [
            'select' => [
                'id' => [],
                'class' => [],
                'name' => [],
            ],
            'option' => [
                'value' => [],
                'selected' => [],
            ],
        ]);
    }

    public static function filter_woocommerce_orders_in_the_table($query)
    {
        global $pagenow;

        $request = Route::getRequestObject();

        if ('edit.php' === $pagenow && 'shop_order' === $query->query['post_type']) {

            $selected = $request->get('rwp_order');

            if (empty($selected)) {
                return $query;
            }

            if ($selected == 'all') return $query;

            $meta_key = Affiliate::ORDER_AFFILIATE_FOR;

            if ($selected == 'recurring') {
                $meta_key = Affiliate::ORDER_RECURRING_ID;
            }

            $meta_query = array(
                array(
                    'key' => $meta_key,
                    'compare' => 'EXISTS'
                )
            );

            $query->set('meta_query', $meta_query);
        }

        return $query;
    }

    public static function filter_woocommerce_orders_for_hpos($args)
    {

        $request = Route::getRequestObject();

        if (!WC::isHPOSEnabled()) {
            return $args;
        }

        $page = $request->get('page');

        if ('wc-orders' !== $page) {
            return $args;
        }

        $selected = $request->get('rwp_order');

        if (empty($selected)) {
            return $args;
        }

        if ($selected == 'all') return $args;

        $meta_key = Affiliate::ORDER_AFFILIATE_FOR;

        if ($selected == 'recurring') {
            $meta_key = Affiliate::ORDER_RECURRING_ID;
        }
        $args['meta_query'][] = [
            'key'     => $meta_key,
            'compare' => 'EXISTS'
        ];


        return $args;
    }
}
