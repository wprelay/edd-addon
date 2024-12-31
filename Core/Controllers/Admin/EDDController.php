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

            $args = [
                'search' => $request->get('search'),
                'limit' => 20,
            ];

            //remove_all_filters('woocommerce_data_stores');
            $data_store = \WC_Data_Store::load('product');

            $product_ids = !empty($args['search'])
                ? $data_store->search_products($args['search'], '', true, false, $args['limit'])
                : $data_store->get_products($args);


            if (empty($product_ids)) {
                Response::success(['products' => []]);
            }

            $data = array_map(function ($product_id) {
                return [
                    'value' => (string)$product_id,
                    'label' => WC::getProductNameWithID($product_id),
                ];
            }, $product_ids);

            //removing index sometimes the index is not in order causing application break
            $data = array_values($data);


            Response::success(['products' => $data]);
        } catch (\Exception | Error $exception) {
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
            ];

            $args['taxonomy'] = 'product_cat';

            $categories = apply_filters('rwpa_product_category_list', WC::getTerms($args));

            if (empty($categories)) {
                Response::success(['categories' => []]);
            }

            $data = [];

            foreach ($categories as $category) {
                $category_label = !empty(WC::getCategoryParent($category->parent)) ? WC::getCategoryParent($category->parent) . ' -> ' : '';
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

