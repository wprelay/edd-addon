<?php

namespace EDDA\Affiliate\Core\Models;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;

class Customer extends Model
{
    protected static $table = 'customers';

    public function createTable()
    {
        //relaywpcode
    }

    public static function createCustomer( $order, $affiliate, $memberId)
    {
        $insertedRows = Customer::query()->create([
            'member_id' => $memberId,
            'woo_customer_id' => $order->customer_id,
            'affiliate_id' => $affiliate->id,
            'program_id' => $affiliate->program_id,
            'billing_email' => $order->email,
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime(),
        ]);

        if (is_int($insertedRows)) {
            return self::query()->lastInsertedId();
        }

        return false;
    }
}