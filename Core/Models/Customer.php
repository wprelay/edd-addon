<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;

class Customer extends Model
{
    protected static $table = 'customers';

    public function createTable()
    {
        $charset = static::getCharSetCollate();

        $table = static::getTableName();
        $memberTable = Member::getTableName();
        $affiliateTable = Affiliate::getTableName();

        return "CREATE TABLE {$table} (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id    BIGINT UNSIGNED          DEFAULT NULL,
            woo_customer_id   BIGINT UNSIGNED          DEFAULT NULL,
            billing_email   VARCHAR(255),
            affiliate_id    BIGINT UNSIGNED          DEFAULT NULL,
            program_id    BIGINT UNSIGNED          DEFAULT NULL,
            is_recurring    BOOLEAN      DEFAULT false,
            date_created timestamp    DEFAULT current_timestamp(),
            created_at   timestamp   DEFAULT current_timestamp(),
            updated_at   timestamp   DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            deleted_at timestamp NULL,
            PRIMARY KEY (id)
        ) {$charset};";
    }

    public static function createCustomer(\WC_Order $order, $affiliate, $memberId)
    {
        $insertedRows = Customer::query()->create([
            'member_id' => $memberId,
            'woo_customer_id' => $order->get_customer_id(),
            'affiliate_id' => $affiliate->id,
            'program_id' => $affiliate->program_id,
            'billing_email' => $order->get_billing_email(),
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime(),
        ]);

        if (is_int($insertedRows)) {
            return self::query()->lastInsertedId();
        }

        return false;
    }

    public static function hasPreviousOrders($affiliate, $email)
    {
        if (empty($email)) {
            return false;
        }

        $member = Member::query()
            ->where("email = %s", [$email])
            ->where("type = %s", ['customer'])
            ->first();

        if (empty($member)) {
            return false;
        }

        $customers = Customer::query()
            ->where("member_id = %d", [$member->id])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->get();

        if (empty($customers)) {
            return false;
        }

        $customer_ids = array_map(function ($customer) {
            return $customer->id;
        }, $customers);

        if (empty($customer_ids)) {
            return false;
        }

        $customer_ids_str = implode("','", $customer_ids);

        $previous_order = Order::query()
            ->where("customer_id IN ('" . $customer_ids_str . "')")
            ->where("affiliate_id = %d", [$affiliate->id])
            ->orderBy('created_at', 'DESC')
            ->first();

        if (empty($previous_order)) {
            return false;
        }

        return true;
    }
}
