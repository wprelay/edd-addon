<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Resources\Affiliate\CommissionBalanceResource;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Transaction;

class CommissionController
{
    public function updateStatus(Request $request)
    {
        Database::beginTransaction();

        try {
            $commissionStatus = $request->get('status');
            $commissionId = $request->get('commission_id');

            $commissionEarning = CommissionEarning::query()
                ->where("id = %d", [$commissionId])
                ->firstOrFail();

            $previousCommissionStatus = $commissionEarning->status;

            $isUpdated = CommissionEarning::query()->update(['status' => $commissionStatus], ['id' => $commissionId]);

            $affiliate = Affiliate::findOrFail($commissionEarning->affiliate_id);
            $member = Member::findOrFail($affiliate->member_id);
            if ($isUpdated) {
                //if approved add credit entry in transaction table

                if ($commissionStatus == CommissionEarning::APPROVED) {
                    Transaction::query()->create([
                        'affiliate_id' => $commissionEarning->affiliate_id,
                        'type' => Transaction::CREDIT,
                        'currency' => $commissionEarning->commission_currency,
                        'amount' => $commissionEarning->commission_amount,
                        'system_note' => "Commission Approved #{$commissionEarning->id}",
                        'transactionable_id' => $commissionEarning->id,
                        'transactionable_type' => 'commission',
                        'created_at' => Functions::currentUTCTime(),
                    ]);
                } else if ($commissionStatus == CommissionEarning::REJECTED && $previousCommissionStatus != 'pending') {
                    Transaction::query()->create([
                        'affiliate_id' => $commissionEarning->affiliate_id,
                        'type' => Transaction::DEBIT,
                        'currency' => $commissionEarning->commission_currency,
                        'amount' => $commissionEarning->commission_amount,
                        'system_note' => "Commission Rejected #{$commissionEarning->id}",
                        'transactionable_id' => $commissionEarning->id,
                        'transactionable_type' => 'commission',
                        'created_at' => Functions::currentUTCTime(),
                    ]);
                }

                CommissionEarning::sendCommissionStatusMail($commissionStatus, $commissionEarning, $affiliate, $member);
            }

            Database::commit();

            return Response::success([
                'message' => __('Commission Status Updated Successfully', 'relay-affiliate-marketing')
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getCommissionBalance(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');
            $rwpCurrency = Functions::getSelectedCurrency();

            $affiliate = Affiliate::findOrFail($affiliateId);

            $member = Member::query()->find($affiliate->member_id);
            $pendingPaymentCount = Payout::query()
                ->where("status = %s", ['pending'])
                ->where('affiliate_id = %d', [$affiliate->id])
                ->count();

            $commissionTransactionBalance = Transaction::query()
                ->select("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount END), 0) - COALESCE(SUM(CASE WHEN type = 'debit' THEN amount END), 0) as commission_transaction_balance, currency")
                ->where("affiliate_id = %d", [$affiliateId])
                ->where("currency = %s", [$rwpCurrency])
                ->first();

            $commissionBalance = $commissionTransactionBalance->commission_transaction_balance;

            return CommissionBalanceResource::resource([$affiliate, $member, $commissionBalance, $rwpCurrency, $pendingPaymentCount]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
