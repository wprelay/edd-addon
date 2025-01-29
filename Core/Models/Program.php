<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use DateTime;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Model;
class Program extends Model
{

    protected static $table = 'programs';


    public function createTable()
    {
        //relaywp code
    }
    public static function calculateCommission($wooOrder, $relayWpOrder, $program, $bonus = false)
    {
        $commissionTier = CommissionTier::findBy('program_id', $program->id);

        $amountDetails = CommissionTier::getCommissionAmountDetails($wooOrder, $relayWpOrder, $commissionTier, $bonus);

        if (isset($amountDetails['keep_entry']) && !$amountDetails['keep_entry']) {
            return false;
        }

        if (!isset($amountDetails['commission_amount'])) {
            return false;
        }

        $data = [
            'commission_tier_id' => $commissionTier->id,
            'commission_amount' => $amountDetails['commission_amount'],
            'commission_display' => $amountDetails['commission_display'] ?? true,
            'status' => 'pending'
        ];
        return apply_filters('rwpa_get_commission_earning_details', $data);
    }

    /**
     * @param $start_date
     * @param $end_date
     * @return bool
     */
    public static function hasHasValidDateRange($start_date, $end_date): bool
    {
        try {
            $start_date = empty($start_date) ? null : Functions::utcToWPTime($start_date);
            $end_date = empty($end_date) ? null : Functions::utcToWPTime($end_date);

            $currentDateTime = current_datetime();
            $timezone = wp_timezone();

            if (empty($start_date) && empty($end_date)) {
                return true;
            } else if (!empty($start_date) && empty($end_date)) {
                $startDate = new DateTime($start_date, $timezone);
                if ($currentDateTime >= $startDate) {
                    return true;
                }
            } else if (empty($start_date) && !empty($end_date)) {
                $endDate = new DateTime($end_date, $timezone);
                if ($currentDateTime <= $endDate) {
                    return true;
                }
            } else if (!empty($start_date) && !empty($end_date)) {
                $startDate = new DateTime($start_date, $timezone);
                $endDate = new DateTime($end_date, $timezone);

                if ($currentDateTime >= $startDate && $currentDateTime <= $endDate) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public static function isValid($program)
    {
        $isValid = !empty($program) && $program->status == 'active' && self::hasHasValidDateRange($program->start_date, $program->end_date);

        return $isValid;
    }
}
