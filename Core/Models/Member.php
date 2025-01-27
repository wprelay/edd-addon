<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Model;
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
        $payment_meta = edd_get_payment_meta($order->id);
        error_log(print_r($payment_meta,true));
        $firstName = $payment_meta['user_info']['first_name'];
        $lastName = $payment_meta['user_info']['last_name'];
        $billingEmail = $order->email;


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
