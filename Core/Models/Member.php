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
        //relaywp code
    }


    public static function createMemberFromOrder($order)
    {
        $payment_meta = edd_get_payment_meta($order->id);

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
