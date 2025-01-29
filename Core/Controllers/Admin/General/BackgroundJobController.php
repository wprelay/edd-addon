<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\General;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\CustomerDiscount;
use EDDA\Affiliate\Core\Models\Member;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\Models\Transaction;

class BackgroundJobController
{
    public static function autoApproveCommission($commissionEarningIds)
    {
        $ids = implode("','", $commissionEarningIds);

        //        ->where("$affiliateTable.id IN ('" . $affiliate_ids . "')")
        $commissionEarnings = CommissionEarning::query()->where("id IN ('" . $ids . "')")->get();

        foreach ($commissionEarnings as $commissionEarning) {

            if (empty($commissionEarning)) {
                return false;
            }

            $status = $commissionEarning->status;

            if ($status == CommissionEarning::APPROVED) {
                return false;
            }

            $relayWpOrder = Order::query()->find($commissionEarning->order_id);

            if (empty($relayWpOrder)) {
                return false;
            }

            $wooOrder = edd_get_order($relayWpOrder->woo_order_id);

            if (empty($wooOrder)) {
                return false;
            }

            $successfulOrderStatus = Settings::get('affiliate_settings.successful_order_status');

            if (!in_array($relayWpOrder->order_status, $successfulOrderStatus) || !in_array($wooOrder->status, $successfulOrderStatus)) {
                return false;
            }

            CommissionEarning::query()->update(['status' => 'approved'], ['id' => $commissionEarning->id]);

            Transaction::query()->create([
                'affiliate_id' => $commissionEarning->affiliate_id,
                'type' => Transaction::CREDIT,
                'currency' => $relayWpOrder->currency,
                'amount' => $commissionEarning->commission_amount,
                'transactionable_id' => $commissionEarning->id,
                'transactionable_type' => 'commission',
                'system_note' => "Commission Auto Approved #{$relayWpOrder->id}",
                'created_at' => Functions::currentUTCTime()
            ]);

            $affiliate = Affiliate::query()->find($commissionEarning->affiliate_id);

            $member = Member::query()->find($affiliate->member_id);

            //If the commission is amount is 0, we don't need to trigger the email
            if (!empty($commissionEarning->commission_amount)) {
                CommissionEarning::sendCommissionStatusMail('approved', $commissionEarning, $affiliate, $member);
            }
        }
    }
}
