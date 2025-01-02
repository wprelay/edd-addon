<?php

namespace EDDA\Affiliate\Core\Controllers\Admin;

defined("ABSPATH") or exit;

use Error;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\Core\Resources\WC\CountryCollection;
use RelayWp\Affiliate\Core\Resources\WC\StateCollection;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use WC_Countries;
use WC_Product_Query;

class EDDController
{
    public static function getWcCountries(Request $request)
    {
        try {
            $search_term = $request->get('search');

            $countries = WC()->countries->get_countries();
            if (!is_array($countries) && empty($values) && !is_string($search_term) && empty($search_term)) {
                return [];
            }

            $countries = array_filter($countries, function ($value) use ($search_term) {
                return strpos(strtolower($value), strtolower($search_term)) !== false;
            });

            $countries = array_values($countries);

            // Output the country names
            return CountryCollection::collection([$countries]);
        } catch (\Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getWcStates(Request $request)
    {
        try {
            $search_term = $request->get('search', '');

            $store_front = $request->get('store_front', false);

            $states = WC::getStates($request->get('country_code'));
            if (!is_array($states) && empty($values)) {
                return [];
            }

            if (!$store_front && !is_string($search_term) && empty($search_term)) {
                return [];
            }

            if (!empty($search_term)) {
                $states = array_filter($states, function ($value) use ($search_term) {
                    return strpos(strtolower($value), strtolower($search_term)) !== false;
                });
            }

            $states = array_values($states);

            // Output the country names
            return StateCollection::collection([$states]);
        } catch (\Exception | Error $exception) {
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
}

