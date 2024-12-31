<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Model;

class CoreModel extends Model
{

    public function createTable()
    {
        // TODO: Implement createTable() method.
    }

    public static function getCoreModels($models = [])
    {
        return array_merge([            //list of models to run migrations
            Member::class,
            Program::class,
            Affiliate::class,
            Customer::class,
            Order::class,
            Product::class,
            OrderProduct::class,
            CommissionTier::class,
            CommissionEarning::class,
            Transaction::class,
            Payout::class,
            CustomerDiscount::class,
            AffiliateCoupon::class,
            Visit::class,
            CouponPayout::class,
        ], $models);
    }
}
