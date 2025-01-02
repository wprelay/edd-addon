<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\EDD;
use EDDA\Affiliate\App\Model;

class Order extends Model
{

    protected static $table = 'orders';

    public function createTable()
    {
        $charset = static::getCharSetCollate();
        $table = static::getTableName();

        $affiliateTable = Affiliate::getTableName();
        $customerTable = Customer::getTableName();

        return "CREATE TABLE {$table} (
                id  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                customer_id  BIGINT UNSIGNED          DEFAULT NULL,
                affiliate_id BIGINT UNSIGNED,
                program_id BIGINT UNSIGNED,
                woo_order_id BIGINT UNSIGNED,
                currency     VARCHAR(255)        NOT NULL,
                order_status     VARCHAR(255)        NOT NULL,
                total_amount DECIMAL(15, 2),
                calculated_total_amount DECIMAL(15, 2),
                recurring_parent_id BIGINT UNSIGNED NULL,
                medium VARCHAR(255) default 'link',
                ordered_at   TIMESTAMP,
                created_at   TIMESTAMP           NOT NULL DEFAULT current_timestamp(),
                updated_at   TIMESTAMP           NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                deleted_at timestamp NULL,
                PRIMARY KEY (id)
            ) {$charset};";
    }

    public static function havingAffiliateCoupon( $order)
    {
        $applied_coupons = $order->get_coupon_codes();

        $affiliate_coupons = AffiliateCoupon::query()->where("coupon in ('" . implode("','", $applied_coupons) . "')")->get();

        foreach ($affiliate_coupons ?? [] as $affiliate_coupon) {
            return $affiliate_coupon->woo_coupon_id;
        }

        return false;
    }

    public static function createOrder(\WC_Order $order, $relayWpCustomerId, $affiliate)
    {
        $medium = $order->get_meta(Affiliate::ORDER_FROM_META_KEY);
        $recurring_order_id = $order->get_meta(Affiliate::ORDER_RECURRING_ID, true);
        $totalPrice = EDD::getTotalPrice($order);
        //create order if already not created
        $insertedRows = Order::query()->create([
            'woo_order_id' => $order->get_id(),
            'customer_id' => $relayWpCustomerId,
            'affiliate_id' => $affiliate->id,
            'program_id' => $affiliate->program_id,
            'currency' => $order->get_currency(),
            'total_amount' => $order->get_total(),
            'calculated_total_amount' => $totalPrice,
            'medium' => $medium ?? 'link',
            'recurring_parent_id' => $recurring_order_id ?: null,
            'ordered_at' => Functions::getWcTime($order->get_date_created()->format('Y-m-d h:i:s')),
            'order_status' => $order->get_status(),
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime(),
        ]);

        if (is_int($insertedRows)) {
            return self::query()->lastInsertedId();
        }

        return false;
    }

    public static function updateOrder(\WC_Order $order, $relayWpOrder)
    {
        $totalPrice = EDD::getTotalPrice($order);
        //create order if already not created
        $rowsUpdated = Order::query()->update([
            'woo_order_id' => $order->get_id(),
            'currency' => $order->get_currency(),
            'total_amount' => $totalPrice,
            'order_status' => $order->get_status(),
            'updated_at' => Functions::currentUTCTime(),
        ], ['id' => $relayWpOrder->id]);

        if (is_int($rowsUpdated)) {
            return $relayWpOrder->id;
        }

        return false;
    }

    public static function isNeedToTrackTheOrder($data, $order)
    {
        if (!empty($data['affiliate']) && !empty($data['medium'])) {
            return $data;
        }

        $affiliate = null;
        $medium = null;

        if ($referralCode = Functions::isAffiliateCookieSet()) {
            $affiliate = Affiliate::query()->findBy('referral_code', $referralCode);
            $medium = 'link';

        } elseif ($coupon_id = Order::havingAffiliateCoupon($order)) {
            $referralCode = wc_get_coupon_code_by_id($coupon_id);
            if (empty($referralCode)) {
                return [];
            }

            $affiliate = Affiliate::query()->findBy('referral_code', $referralCode);
            $medium = 'coupon';
        }

        if (empty($affiliate) || empty($medium)) {
            return [];
        }

        $data['affiliate'] = $affiliate;
        $data['medium'] = $medium;

        //If the $affiliate present it should not have Previous order to track here. if it has previous order that means this order is may recursive,
        // it will handled separately.

        $email = $order->get_billing_email();
        $member = Member::query()
            ->where("email = %s", [$email])
            ->where("type = %s", ['customer'])
            ->first();

        if (empty($member)) {
            return $data;
        }

        $customers = Customer::query()
            ->where("member_id = %d", [$member->id])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->get();

        if (!empty($customers)) {
            return [];
        }

        $customer_ids = array_map(function ($customer) {
            return $customer->id;
        }, $customers);

        if (!empty($customer_ids)) {
            return [];
        }

        $customer_ids_str = implode("','", $customer_ids);

        $previous_order = Order::query()
            ->where("customer_id IN ('" . $customer_ids_str . "')")
            ->where("affiliate_id = %d", [$affiliate->id])
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!empty($previous_order)) {
            return [];
        }

        return $data;
    }
}
