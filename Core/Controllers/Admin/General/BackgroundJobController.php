<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\General;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use EDDA\Affiliate\Core\Models\CustomerDiscount;
use EDDA\Affiliate\Core\Models\Member;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\Models\Transaction;

class BackgroundJobController
{
    public static function updateAffiliateCoupons()
    {
        $customerDiscountTable = CustomerDiscount::getTableName();

        $customerDiscounts = CustomerDiscount::query()
            ->where("{$customerDiscountTable}.update_coupons = %d", [1])
            ->get();

        foreach ($customerDiscounts as $customerDiscount) {
            $program = Program::query()->find($customerDiscount->program_id);

            if (empty($program)) {
                return;
            }

            $affiliates = Affiliate::query()->where("program_id = %s", [$program->id])->get();

            foreach ($affiliates as $affiliate) {
                try {
                    Affiliate::updateCoupon($affiliate, $program, $customerDiscount);
                } catch (\Error $error) {
                }
            }

            CustomerDiscount::query()->update([
                'update_coupons' => 0
            ], ['id' => $customerDiscount->id]);
        }
    }

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

            if (!in_array($relayWpOrder->order_status, $successfulOrderStatus) || !in_array($wooOrder->get_status(), $successfulOrderStatus)) {
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

    /**
     * action: rwpa_enqueue_payments
     * @param $payout_ids
     * @param $source
     * @return void
     */
    public static function enqueuePayments($payout_ids, $source)
    {
        if (!is_array($payout_ids)) {
            $payout_ids = [$payout_ids];
        }
        $transaction_data = [];

        $created_at = Functions::currentUTCTime();
        $ids = implode("','", $payout_ids);

        $payouts = Payout::query()
            ->where("id IN ('" . $ids . "')")
            ->get();

        if (empty($payouts)) return;

        $affiliate_ids = [];
        foreach ($payouts as $payout) {
            $affiliate_ids[] = $payout->affiliate_id;
        }

        $affiliate_ids = implode("','", $affiliate_ids);

        $rwpCurrency = $payouts[0]->currency;

        $affiliateTable = Affiliate::getTableName();
        $memberTable = Member::getTableName();
        $transactionTable = Transaction::getTableName();

        $balances = Affiliate::query()
            ->select("{$affiliateTable}.id as affiliate_id, {$affiliateTable}.payment_email as paypal_billing_email, $transactionTable.currency as currency,
                (COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'credit' THEN {$transactionTable}.amount END), 0) - COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'debit' THEN {$transactionTable}.amount END), 0)) as balance,
                {$memberTable}.first_name as affiliate_first_name, {$memberTable}.last_name as affiliate_last_name, {$memberTable}.email as affiliate_email")
            ->leftJoin("{$memberTable}", "{$memberTable}.id = {$affiliateTable}.member_id")
            ->leftJoin("{$transactionTable}", "{$affiliateTable}.id = {$transactionTable}.affiliate_id")
            ->where("$transactionTable.currency = %s", [$rwpCurrency])
            ->where("$affiliateTable.id IN ('" . $affiliate_ids . "')")
            ->groupBy("{$affiliateTable}.id")
            ->get();


        $balance_amounts = [];

        foreach ($balances as $balance) {
            $balance_amounts[$balance->affiliate_id] = $balance->balance;
        }

        $available_payout_ids = [];
        foreach ($payouts as $payout) {
            if (isset($balance_amounts[$payout->affiliate_id]) && $payout->amount <= $balance_amounts[$payout->affiliate_id]) {
                $transaction_data[] = [
                    'affiliate_id' => $payout->affiliate_id,
                    'type' => Transaction::DEBIT,
                    'currency' => $payout->currency,
                    'amount' => $payout->amount,
                    'transactionable_id' => $payout->id,
                    'transactionable_type' => 'payout',
                    'created_at' => $created_at,
                ];

                $available_payout_ids[] = $payout->id;
            } else {
                Payout::query()->update([
                    'status' => 'failed'
                ], ['id' => $payout->id]);
            }
        }

        if (!empty($transaction_data)) {
            Transaction::query()->createMany($transaction_data);
            do_action('rwpa_record_rwt_payment', $available_payout_ids, $source);
        }
    }
}
