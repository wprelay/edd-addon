<?php

namespace EDDA\Affiliate\Core\Controllers\Hooks;

use RelayWp\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\EDD;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Customer;
use RelayWp\Affiliate\Core\Models\Order;

class CommissionTierController
{
    public static function getTierBasedAmountDetail($data,$wooOrder, $relayWpOrder, $commissionTier)
    {
        $matchedRange = static::getMatchedRange($wooOrder, $relayWpOrder, $commissionTier);

        if (empty($matchedRange)) {

            return [
                'commission_amount' => 0,
                'commission_display' => false
            ];
        }

        if ($matchedRange['type'] == 'percentage' && (int)($matchedRange['value']) > 0) {
            $totalOrderAmount = EDD::getTotalPrice($wooOrder);

            $calculatedCommissionAmount = ($totalOrderAmount * ($matchedRange['value'] / 100));
        } else {
            $calculatedCommissionAmount = $matchedRange['value'];
        }

        $data = [
            'commission_amount' => $calculatedCommissionAmount,
        ];

        return apply_filters('rwpa_get_tier_based_commission_data', $data);
    }

    public static function getMatchedRange($wooOrder, $relayWpOrder, $commissionTier)
    {
        $options = Functions::jsonDecode($commissionTier->rate_json);

        $ranges = $options['tier_based_options']['ranges'];

        if (empty($options)) {
            return false;
        }


        $based_on = $options['tier_based_options']['based_on'];

        $affiliateId = edd_get_payment_meta($wooOrder->id,Affiliate::ORDER_AFFILIATE_FOR);

        $currency = $wooOrder->currency;

        if ($based_on == 'number_of_referrals') {
            $count = Customer::query()
                ->select('count(*) as customer_count')
                ->where("affiliate_id = $affiliateId")
                ->where("created_at <= %s", [$relayWpOrder->created_at])
                ->first();

            $value = $count->customer_count;
        } else if ($based_on == 'number_of_sales_count') {
            $count = Order::query()
                ->select('count(*) as sales_count')
                ->where("affiliate_id = $affiliateId")
                ->where("created_at <= %s", [$relayWpOrder->created_at])
                ->first();
            $value = $count->sales_count;
        } else if ($based_on == 'total_sales_amount') {
            $count = Order::query()
                ->select('sum(total_amount) as sales_amount')
                ->where("affiliate_id = $affiliateId")
                ->where("currency = %s", [$currency])
                ->where("created_at <= %s", [$relayWpOrder->created_at])
                ->first();

            $value = $count->sales_amount;
        }

        if ($value < 0) return false;

        if ($based_on == 'total_sales_amount') {
            $ranges = \RelayWp\Affiliate\Pro\Controllers\Admin\Hooks\CommissionTierController::getSelectedCurrencyRanges($ranges, $currency);
        }

        //TODO: Handle currency for total sales amount type

        $range =  \RelayWp\Affiliate\Pro\Controllers\Admin\Hooks\CommissionTierController::getMatchedRow($ranges, $value);

        return $range;
    }
}