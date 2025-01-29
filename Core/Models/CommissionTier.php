<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WC;
use Cartrabbit\Request\Request;
use RelayWp\Affiliate\App\Model;

class CommissionTier extends Model
{
    protected static $table = 'commission_tiers';

    public const RULE_BASED_TYPE = 'rule_based';
    const ADVANCED = 'advanced';
    const SIMPLE = 'simple';
    const RULE_BASED = 'rule_based';

    public function createTable()
    {
        //relaywp code
    }


    public static function getCommissionAmountDetails($wooOrder, $relayWpOrder, $commissionTier)
    {

        $rateType = $commissionTier->rate_type;

        if ($rateType == 'no_commission') {
            $data = [
                'commission_amount' => 0,
                'commission_display' => false,
                'keep_entry' => false
            ];
        } else {
            $data = apply_filters("rwpa_get_commission_details_for_edd_{$rateType}_type", [], $wooOrder, $relayWpOrder, $commissionTier);
        }

        return apply_filters('rwpa_commission_details', $data, $wooOrder);
    }
}
