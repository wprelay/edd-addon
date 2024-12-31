<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Model;

class Product extends Model
{
    protected static $table = 'products';

    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();
        $orderTable = Order::getTableName();
        return "CREATE TABLE {$table} (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              woo_product_id BIGINT UNSIGNED NULL,
              order_id BIGINT UNSIGNED NULL,
              name varchar(255) NOT NULL,
              price varchar(255) NOT NULL,
              quantity INTEGER NOT NULL,
              date_created timestamp NOT NULL,
              created_at timestamp NOT NULL DEFAULT current_timestamp(),
              updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              deleted_at timestamp NULL,
              PRIMARY KEY (id)
            ) {$charset};";
    }

    public static function getProductWithLabels($product_ids)
    {
        if (!is_array($product_ids)) return [];

        $data = array_map(function ($product_id) {
            return [
                'value' => (string)$product_id,
                'label' => WC::getProductNameWithID($product_id),
                'url' => get_permalink($product_id),
            ];
        }, $product_ids);

        return $data;
    }


    public static function getCategoryWithLabels($category_ids)
    {

        if (!is_array($category_ids)) {
            return [];
        }

        $categories = array_map(function ($category_id) {
            return WC::getTerm($category_id, 'product_cat');
        }, $category_ids);

        $data = [];

        foreach ($categories as $category) {
            $category_label = !empty(WC::getCategoryParent($category->parent)) ? WC::getCategoryParent($category->parent) . ' -> ' : '';

            $data[] = [
                'label' => $category_label . $category->name,
                'value' => $category->term_id,
                'url' => get_category_link($category->term_id)
            ];
        }

        return $data;
    }
}

