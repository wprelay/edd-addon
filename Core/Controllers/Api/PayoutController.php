<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Resources\Payouts\PendingPaymentCollection;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use EDDA\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Transaction;
use RelayWp\Affiliate\Core\ValidationRequest\Payout\BulkPayoutRequest;
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
            if (!wp_next_scheduled('rwpa_enqueue_payments', [$payoutId, $source])) {
                wp_schedule_single_event(strtotime("+1 minute"), 'rwpa_enqueue_payments', [$payoutId, $source]);
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
        } catch (Exception|Error $exception) {
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
            if (!wp_next_scheduled('rwpa_enqueue_payments', [$payout_ids, $source])) {
                wp_schedule_single_event(strtotime("+1 minute"), 'rwpa_enqueue_payments', [$payout_ids, $source]);
            }else {
            }

            Database::commit();

            return Response::success([
                'message' => __('Payout Recorded Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception|Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
