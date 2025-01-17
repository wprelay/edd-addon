<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Model;

class CommissionEarning extends Model
{
    protected static $table = 'commission_earnings';

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public function createTable()
    {

    }
    public static function createCommissionEarning($commissionDetails, $affiliate, $order, $relayWpOrder, $program)
    {
        $isCreated = CommissionEarning::query()->create([
            'affiliate_id' => $affiliate->id,
            'order_id' => $relayWpOrder->id,
            'program_id' => $program->id,
            'commission_amount' => $commissionDetails['commission_amount'],
            'commission_currency' => $relayWpOrder->currency,
            'show_commission' => $commissionDetails['commission_display'] ?? true,
            'status' => CommissionEarning::PENDING,
            'reason' => "From SuccessFul Order {$order->status}",
            'type' => $commissionDetails['type'] ?? 'commission',
            'date_created' => Functions::currentUTCTime(),
            'created_at' => Functions::currentUTCTime(),
            'updated_at' => Functions::currentUTCTime(),
        ]);

        if ($isCreated && ($commissionDetails['commission_display'] ?? true)) {
            return CommissionEarning::query()->lastInsertedId();
        }

        return false;
    }
}
