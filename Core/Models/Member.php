<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;

class Member extends Model
{

    protected static $table = 'members';

    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();

        return "CREATE TABLE {$table} (
                    id BIGINT UNSIGNED AUTO_INCREMENT,
                    description TEXT,
                    email varchar(255),
                    type varchar(30) default 'customer',
                    first_name varchar(255),
                    last_name varchar(255),
                    created_at timestamp NOT NULL DEFAULT current_timestamp(),
                    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    deleted_at timestamp NULL,
                    PRIMARY KEY (id)
                ) {$charset};";
    }


    public static function createMemberFromOrder($order)
    {

        $firstName = $order->get_billing_first_name();
        $lastName = $order->get_billing_last_name();
        $billingEmail = $order->get_billing_email();


        $insertedRows = self::query()->create([
            'email' => $billingEmail,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'type' => 'customer',
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime(),
        ]);

        if (is_int($insertedRows)) {
            return self::query()->lastInsertedId();
        }

        return false;
    }
}
