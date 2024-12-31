<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Models\CouponPayout;
use RelayWp\Affiliate\Core\Resources\Payouts\PaymentHistoryCollection;
use RelayWp\Affiliate\Core\Resources\Payouts\PendingPaymentCollection;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Transaction;
use RelayWp\Affiliate\Core\ValidationRequest\Payout\BulkPayoutRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Payout\DeletePayoutRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Payout\PayoutRequest;

class PayoutController
{
    public function recordPayout(Request $request)
    {
        $request->validate(new PayoutRequest());
        Database::beginTransaction();
        try {
            $affiliateId = $request->get('affiliate_id');
            $amount = $request->get('amount_to_pay');
            $source = $request->get('payment_source');
            $affiliateNote = $request->get('affiliate_notes');
            $adminNote = $request->get('admin_notes');
            $rwpCurrency = Functions::getSelectedCurrency();

            if (!Payout::isPaymentModeEnabled($source)) {
                Response::error([
                    'message' => __('Payment Mode is not enabled', 'relay-affiliate-marketing')
                ], 403);
            }

            [$isPassed, $message] = Payout::isPaymentValidationsPassed($source, $rwpCurrency, $amount);

            if (!$isPassed) {
                Response::error([
                    'message' => $message
                ], 403);
            }

            $affiliate = Affiliate::query()->findOrFail($affiliateId);

            $pendingPayoutCount = Payout::query()
                ->where("status = %s", ['pending'])
                ->where('affiliate_id = %d', [$affiliate->id])
                ->count();

            if ((int)$pendingPayoutCount > 0) {
                return Response::error([
                    'message' => __('Previous Payment is not yet processed, Please Try Again Later', 'relay-affiliate-marketing')
                ], 403);
            }

            $data = [
                'amount' => $amount,
                'affiliate_note' => $affiliateNote,
                'admin_note' => $adminNote,
                'source' => $source,
                'currency' => $rwpCurrency,
                'paid_at' => Functions::currentUTCTime(),
                'affiliate_id' => $affiliateId,
            ];

            //Bulk Payout Insert
            Payout::query()->create([
                'amount' => ($source == 'paypal' || $source == 'lpoints') ? (int)$data['amount'] : $data['amount'],
                'payment_source' => $data['source'],
                'affiliate_note' => $data['affiliate_note'],
                'admin_note' => $data['admin_note'],
                'currency' => $data['currency'],
                'paid_at' => Functions::currentUTCTime(),
                'affiliate_id' => $data['affiliate_id'],
                'status' => 'pending',
                'paid_by' => get_current_user_id(),
                'created_at' => Functions::currentUTCTime(),
                'updated_at' => Functions::currentUTCTime(),
            ]);

            $payoutId = Payout::query()->lastInsertedId();

            if (\ActionScheduler::is_initialized()) {
                as_schedule_single_action(strtotime("+1 minute"), 'rwpa_enqueue_payments', [[$payoutId], $source]);
            } else {
                //action schedlular not initialized
                Payout::query()->update([
                    'status' => 'failed',
                    'payout_details' => wp_json_encode(['message' => 'Action Scheduler not initialized. So unable to process the apyout'])
                ], ['id' => $payoutId]);
            }

            Database::commit();

            return Response::success([
                'message' => __('Payout Recorded Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function recordBulkPayout(Request $request)
    {
        $request->validate(new BulkPayoutRequest());
        Database::beginTransaction();
        try {
            $selected_payments = $request->get('selected_payments');
            $source = $request->get('payment_source');
            $affiliateNote = $request->get('affiliate_notes');
            $adminNote = $request->get('admin_notes');
            $rwpCurrency = Functions::getSelectedCurrency();

            if (!Payout::isPaymentModeEnabled($source)) {
                Response::error([
                    'message' => __('Payment Mode is not enabled', 'relay-affiliate-marketing')
                ], 403);
            }


            $data = [];
            $paid_at = Functions::currentUTCTime();
            foreach ($selected_payments as $payment) {
                $amount = ($source == 'paypal' || $source == 'lpoints') ? (int)$payment['commission_amount'] : $payment['commission_amount'];
                [$isPassed, $message] = Payout::isPaymentValidationsPassed($source, $rwpCurrency, $amount);

                if (!$isPassed) {
                    Response::error([
                        'message' => $message
                    ], 403);
                }

                $data[] = [
                    'amount' => $amount,
                    'affiliate_note' => $affiliateNote,
                    'admin_note' => $adminNote,
                    'payment_source' => $source,
                    'currency' => $rwpCurrency,
                    'paid_at' => $paid_at,
                    'status' => 'pending',
                    'paid_by' => get_current_user_id(),
                    'affiliate_id' => $payment['affiliate_id'],
                ];
            }

            $latestPayout = Payout::query()->select()->orderBy('id', 'DESC')
                ->first();

            Payout::query()->createMany($data);

            $payouts = Payout::query()
                ->when(!empty($latestPayout), function ($query) use ($latestPayout) {
                    return $query->where("id > %d", [$latestPayout->id]);
                })
                ->get();

            $payout_ids = [];

            foreach ($payouts as $payout) {
                $payout_ids[] = $payout->id;
            }

            if (\ActionScheduler::is_initialized()) {
                as_schedule_single_action(strtotime("+1 minute"), 'rwpa_enqueue_payments', [$payout_ids, $source]);
            } else {
            }

            Database::commit();

            return Response::success([
                'message' => __('Payout Recorded Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getPaymentMethods(Request $request)
    {
        try {
            $paymentMethods = [];
            $paymentMethods = apply_filters('rwpa_get_payment_methods', $paymentMethods);

            $paymentSettings = Settings::get('payment_settings');

            foreach ($paymentMethods as $index => $method) {
                if ($method['value'] == 'manual') continue;
                if (!isset($paymentSettings[$method['value']]['enabled']) || !$paymentSettings[$method['value']]['enabled']) {
                    unset($paymentMethods[$index]);
                }
            }

            $removeIndexes = [];

            foreach ($paymentMethods as $index => $method) {
                $removeIndexes[] = $method;
            }

            return Response::success($removeIndexes);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function pendingPaymentList(Request $request)
    {
        $search = $request->get('search');
        $currentPage = $request->get('current_page');
        $perPage = $request->get('per_page');

        try {

            $affiliateTable = Affiliate::getTableName();
            $memberTable = Member::getTableName();
            $payoutTable = Payout::getTableName();
            $transactionTable = Transaction::getTableName();
            $search = $request->get('search');
            $rwpCurrency = Functions::getSelectedCurrency();

            $query = Affiliate::query()
                ->select("{$affiliateTable}.id as affiliate_id, {$affiliateTable}.payment_email as paypal_billing_email, $transactionTable.currency as currency, (SELECT SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) FROM {$payoutTable} WHERE {$payoutTable}.affiliate_id = {$affiliateTable}.id)  as pending_payout_count,
                (COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'credit' THEN {$transactionTable}.amount END), 0) - COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'debit' THEN {$transactionTable}.amount END), 0)) as balance,
                {$memberTable}.first_name as affiliate_first_name, {$memberTable}.last_name as affiliate_last_name, {$memberTable}.email as affiliate_email")
                ->leftJoin("{$memberTable}", "{$memberTable}.id = {$affiliateTable}.member_id")
                ->leftJoin("{$transactionTable}", "{$affiliateTable}.id = {$transactionTable}.affiliate_id")
                ->where("$transactionTable.currency = %s", [$rwpCurrency])
                ->when($search, function ($query) use ($memberTable, $search) {
                    return $query->nameLike("$memberTable.first_name", "$memberTable.last_name", $search)
                        ->orWhere("$memberTable.email like %s", ["%{$search}%"]);
                })->groupBy("{$affiliateTable}.id")
                ->having("(COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'credit' THEN {$transactionTable}.amount END), 0) - COALESCE(SUM(CASE WHEN {$transactionTable}.type = 'debit' THEN {$transactionTable}.amount END), 0)) != 0")
                ->orderBy("{$affiliateTable}.id", "DESC");

            $totalCount = $query->count();

            $data = $query->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return PendingPaymentCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function paymentHistories(Request $request)
    {
        try {
            $search = $request->get('search');
            $currentPage = $request->get('current_page');
            $perPage = $request->get('per_page');
            $rwpCurrency = Functions::getSelectedCurrency();
            $status = $request->get('payout_status', []);

            $affiliateTable = Affiliate::getTableName();
            $memberTable = Member::getTableName();
            $payoutTable = Payout::getTableName();
            $couponPayoutTable = CouponPayout::getTableName();

            $query = Payout::query()
                ->select("{$payoutTable}.id as payout_id, {$payoutTable}.currency as currency, {$payoutTable}.status as status, {$payoutTable}.deleted_at as deleted_at, {$payoutTable}.payment_source as payment_source, {$payoutTable}.created_at as paid_at, {$affiliateTable}.id as affiliate_id, {$memberTable}.first_name as affiliate_first_name, {$memberTable}.last_name as affiliate_last_name, {$memberTable}.email as affiliate_email,
                    {$payoutTable}.amount as paid_amount,
                    {$payoutTable}.payout_details as additional_details,
                    {$couponPayoutTable}.coupon_code
                    ")
                ->join("{$affiliateTable}", "{$affiliateTable}.id = {$payoutTable}.affiliate_id")
                ->join("{$memberTable}", "{$memberTable}.id = {$affiliateTable}.member_id")
                ->leftJoin("{$couponPayoutTable}", "{$couponPayoutTable}.payout_id = {$payoutTable}.id")
                ->where("{$payoutTable}.currency = %s", [$rwpCurrency])
                ->when(!empty($status), function (Database $query) use ($payoutTable, $status) {
                    return $query->where("{$payoutTable}.status in ('" . implode("','", $status) . "')");
                })
                ->when($search, function ($query) use ($memberTable, $search) {
                    return $query->nameLike("$memberTable.first_name", "$memberTable.last_name", $search)
                        ->orWhere("$memberTable.email like %s", ["%{$search}%"]);
                })->orderBy("{$payoutTable}.id", "DESC");

            $totalCount = $query->count();

            $data = $query->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return PaymentHistoryCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function revertPayout(Request $request)
    {
        $request->validate(new DeletePayoutRequest());

        Database::beginTransaction();
        try {
            $payoutId = $request->get('payout_id');
            $revert_reason = $request->get('revert_reason');
            $payout = Payout::query()->findOrFail($payoutId);

            Transaction::query()->create([
                'affiliate_id' => $payout->affiliate_id,
                'type' => Transaction::CREDIT,
                'currency' => $payout->currency,
                'amount' => $payout->amount,
                'transactionable_id' => $payout->id,
                'transactionable_type' => 'payout',
                'system_note' => "Payout Reverted #{$payoutId}",
            ]);

            Payout::query()->update([
                'revert_reason' => $revert_reason,
                'deleted_at' => Functions::currentUTCTime(),
            ], ['id' => $payout->id]);

            Database::commit();

            return Response::success([
                'message' => __('Payment History Reverted', 'relay-affiliate-marketing')
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
