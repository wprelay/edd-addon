<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;

class OrderProduct extends Model
{
    protected static $table = 'order_products';

    public function createTable()
    {
        $charset = static::getCharSetCollate();
        $table = static::getTableName();
        $orderTable = Order::getTableName();
        $productTable = Product::getTableName();

        return "CREATE TABLE {$table}
            (
                id             BIGINT UNSIGNED NOT NULL,
                order_id       BIGINT UNSIGNED,
                product_id     BIGINT UNSIGNED,
                date_created   timestamp,
                name           varchar(255) ,
                quantity       varchar(255),
                price_per_unit varchar(255),
                created_at     timestamp  NOT NULL DEFAULT current_timestamp(),
                updated_at     timestamp  NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                deleted_at timestamp NULL,
                PRIMARY KEY (id)
            ) {$charset};";
    }
}

