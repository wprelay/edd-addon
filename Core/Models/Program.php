<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use Cartrabbit\Request\Request;
use DateTime;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Model;
use EDDA\Affiliate\Core\Models\CommissionTier;
class Program extends Model
{

    protected static $table = 'programs';


    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();

        return "CREATE TABLE {$table} (
                    id BIGINT UNSIGNED AUTO_INCREMENT,
                    title TEXT,
                    description TEXT NULL,
                    start_date timestamp null,
                    end_date timestamp null,
                    auto_approve boolean default 0,
                    status varchar(255) default 'draft',
                    is_default integer(2) default 0,
                    is_archived integer(2) default 0,
                    custom_field_shortcode varchar(255) NULL,
                    custom_affiliate_fields JSON NULL,
                    created_at timestamp NOT NULL DEFAULT current_timestamp(),
                    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    deleted_at timestamp NULL,
                    PRIMARY KEY (id)
                ) {$charset};";
    }

    public static function getProgramsForIndexPage()
    {
        $programTable = Program::getTableName();
        $commissionTierTable = CommissionTier::getTableName();

        $offset = 0;
        $limit = 10;

        $query = "select * from {$programTable} inner join $commissionTierTable on {$programTable}.id = {$commissionTierTable}.program_id LIMIT {$limit} OFFSET {$offset}";

        return static::getResults($query);
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

    public static function isScheduled($start_date, $end_date): bool
    {
        try {
            $start_date = empty($start_date) ? null : Functions::utcToWPTime($start_date);
            $end_date = empty($end_date) ? null : Functions::utcToWPTime($end_date);

            $currentDateTime = current_datetime();
            $timezone = wp_timezone();

            if (empty($start_date) && empty($end_date)) {
                return false;
            } else if (!empty($start_date) && empty($end_date)) {
                $startDate = new DateTime($start_date, $timezone);

                if ($currentDateTime < $startDate) {
                    return true;
                }
            } else if (!empty($start_date) && !empty($end_date)) {
                $startDate = new DateTime($start_date, $timezone);
                $endDate = new DateTime($end_date, $timezone);

                if ($currentDateTime <= $startDate && $currentDateTime <= $endDate) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }


    public static function isTierAvailable($isAvailable, $program, $commissionTier)
    {
        return !static::isPro($commissionTier->base_type);
    }

    public static function isPro($type)
    {
        return $type == 'advanced';
    }
}
