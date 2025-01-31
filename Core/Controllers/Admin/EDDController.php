<?php

namespace EDDA\Affiliate\Core\Controllers\Admin;

defined("ABSPATH") or exit;

use Error;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use EDDA\Affiliate\App\Helpers\EDD;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Resources\WC\CountryCollection;
use RelayWp\Affiliate\Core\Resources\WC\StateCollection;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;

class EDDController
{
    public static function getSearchEDDCountries(Request $request)
    {
        try {
            $search_term = $request->get('search');

            $countries = edd_get_country_list();

            if (!is_array($countries) || empty($countries) || !is_string($search_term) || empty($search_term)) {
                return [];
            }

            $countries = array_filter($countries, function ($value, $key) use ($search_term) {
                return strpos(strtolower($value), strtolower($search_term)) !== false;
            }, ARRAY_FILTER_USE_BOTH);

            // Wrap the country names in a collection for response
            return CountryCollection::collection([$countries]);
        } catch (\Exception | \Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getSearchEddStates(Request $request)
    {
        try {
            $search_term = $request->get('search', '');
            $store_front = $request->get('store_front', false);
            $country_code = $request->get('country_code');

            // Get states for the country
            $states = EDD::getStates($country_code);

            // Validate the states result
            if (!is_array($states) || empty($states)) {
                return [];
            }

            // If not on storefront and no search term, return an empty array
            if (!$store_front && (!is_string($search_term) || empty($search_term))) {
                return [];
            }

            // Filter states based on the search term
            if (!empty($search_term)) {
                $states = array_filter($states, function ($value, $key) use ($search_term) {
                    return strpos(strtolower($value), strtolower($search_term)) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }
            // Output the states
            return StateCollection::collection([$states]);
        } catch (\Exception | \Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }


    public static function getProductsList(Request $request)
    {
        try {
            // Extract search query and limit from the request
            $searchQuery = $request->get('search', '');
            $limit = 20;

            // Define query arguments for EDD
            $args = [
                'post_type'      => 'download',
                'posts_per_page' => $limit,
                's'              => $searchQuery,
                'post_status'    => 'publish',
            ];

            $query = new \WP_Query($args);

            if (!$query->have_posts()) {
                Response::success(['products' => []]);
            }

            $data = array_map(function ($post) {
                return [
                    'value' => (string)$post->ID,
                    'label' => $post->post_title,
                ];
            }, $query->posts);

            wp_reset_postdata();

            $data = array_values($data);


            Response::success(['products' => $data]);
        } catch (\Exception | \Error $exception) {

            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getCategoriesList(Request $request)
    {
        try {

            $args = [
                'search' => $request->get('search'),
                'limit' => 20,
                'taxonomy' => 'download_category',
                'hide_empty' => false,
            ];

            // Get categories using WordPress's get_terms function
            $categories = get_terms($args);

            if (empty($categories)) {
                Response::success(['categories' => []]);
            }

            $data = [];
            foreach ($categories as $category) {
                $parent_category = get_term($category->parent, 'download_category');
                $category_label = !empty($parent_category) ? $parent_category->name . ' -> ' : '';
                $data[] = [
                    'label' => $category_label . $category->name,
                    'value' => $category->term_id,
                ];
            }
            $data = array_values($data);
            $data = (array)apply_filters('rwpa_categories_search_in_customer_discount_coupon', $data);

            Response::success(['categories' => $data]);
        } catch (\Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
    public static function scheduleActions($value,$hook,$payoutId,$source){
        switch($hook){
            case 'rwpa_enqueue_payments':
                if (!wp_next_scheduled($hook, [$payoutId, $source])) {
                    $is_scheduled= wp_schedule_single_event(strtotime("+1 minute"), $hook, [$payoutId, $source]);
                    return $is_scheduled;
                }

            case 'rwpa_auto_approve_commission':
                $delay = apply_filters('rwpa_auto_approval_delay_in_days', Settings::get('affiliate_settings.general.auto_approve_delay_in_days'));
                $delay = $delay ?: 0;
                if (!wp_next_scheduled($hook, [$source])){
                    $is_scheduled= wp_schedule_single_event(strtotime("+{$delay} days"), 'rwpa_auto_approve_commission', [$source]);
                    return $is_scheduled;
                }

            case 'rwpa_update_affiliate_coupons':
                if(!wp_next_scheduled($hook)) {
                    $is_scheduled= wp_schedule_single_event(strtotime('now'), $hook);
                    return $is_scheduled;
                }
            case 'rwpa_process_coupon_payouts':
                if (!wp_next_scheduled($hook, [$payoutId])) {
                    $is_scheduled= wp_schedule_single_event(time(), $hook, [$payoutId]);
                    return $is_scheduled;
                }
            default:
                return false;
        }
    }
}
