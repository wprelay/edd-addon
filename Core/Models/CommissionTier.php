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
        $charset = static::getCharSetCollate();

        $table = static::getTableName();

        $programTable = Program::getTableName();

        return "CREATE TABLE {$table} (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                program_id BIGINT UNSIGNED,
                base_type VARCHAR(255) default 'simple',
                rate_type VARCHAR(255),
                rate_json JSON NULL,
                recurring_commission_enabled BOOLEAN DEFAULT 0,
                recurring_commission_options JSON,
                created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
                updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                deleted_at timestamp NULL,
                PRIMARY KEY (id)
            ) {$charset};";
    }


    public static function rateJsonDataFromRequest($type, Request $request)
    {
        $rateJson = [];

        if ($type == 'fixed') {
            $rateJson['value'] = $request->get('commission_type_options.value');
        } else if ($type == 'percentage_per_sale') {
            $rateJson['value'] = $request->get('commission_type_options.value');
        } else if ($type == 'no_commission') {
            $rateJson['value'] = null;
        } else if ($type == 'tier_based') {
            $rateJson = apply_filters('rwpa_get_tier_based_commission_tier_options', $rateJson, $request);
        } else if ($type == 'rule_based') {
            $rateJson['value'] = null;
        }

        $rateJson = wp_json_encode($rateJson);

        return $rateJson;
    }

    public static function getCommissionOptions($commissionTier)
    {
        $options = Functions::jsonDecode($commissionTier->rate_json) ?? [];

        $type = $commissionTier->rate_type;

        $rateJson = [];

        if ($type == 'fixed') {
            $rateJson['value'] = $options['value'];
        } else if ($type == 'percentage_per_sale') {
            $rateJson['value'] = $options['value'];
        } else if ($type == 'no_commission') {
            $rateJson['value'] = null;
        } else if ($type == 'tier_based') {
            $rateJson['tier_based_options']['based_on'] = $options['tier_based_options']['based_on'];
            $ranges = [];
            $tiers = $options['tier_based_options']['ranges'];

            $based_on = $rateJson['tier_based_options']['based_on'];
            foreach ($tiers as $index => $tier) {
                if ($rateJson['tier_based_options']['based_on'] == 'total_sales_amount') {
                    $ranges[$index]['currency'] = $tier['currency'];
                }
                $ranges[$index]['condition'] = $tier['condition'];
                $ranges[$index]['value'] = $tier['value'];
            }
            $rateJson['tier_based_options']['ranges'] = $ranges;
            $rateJson['tier_based_options']['type'] = $options['tier_based_options']['type'] ?? $tiers[0]['type'];
        }

        return $rateJson;
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
            $data = apply_filters("rwpa_get_commission_details_for_{$rateType}_type", [], $wooOrder, $relayWpOrder, $commissionTier);
        }

        return apply_filters('rwpa_commission_details', $data, $wooOrder);
    }

    public static function getPercentagePerSaleAmountDetail($data, \WC_Order $wooOrder, $relayWpOrder, $commissionTier)
    {
        $options = Functions::jsonDecode($commissionTier->rate_json);

        $percentage = isset($options['value']) ? $options['value'] : 0;
        $calculatedCommissionAmount = 0;

        if ($percentage > 0) {
            $totalOrderAmount = WC::getTotalPrice($wooOrder);

            $calculatedCommissionAmount = ($totalOrderAmount * ($percentage / 100));
        }

        $data = [
            'commission_amount' => $calculatedCommissionAmount,
        ];

        return apply_filters('rwpa_get_percentage_commission_data', $data);
    }

    public static function getFixedAmountDetail($data, $wooOrder, $relayWpOrder, $commissionTier)
    {
        $options = Functions::jsonDecode($commissionTier->rate_json) ?? [];

        $fixedAmount = $options['value'] ?? 0;

        $data = [
            'commission_amount' => $fixedAmount,
        ];

        return apply_filters('rwpa_get_fixed_commission_data', $data);
    }

    public static function getCommissionTierInfo($commissionTier)
    {
        $rateType = $commissionTier->rate_type;
        $rateJson = Functions::jsonDecode($commissionTier->rate_json);

        $typeFormatted = static::getCommissionTierTypeLabel($rateType);

        switch ($rateType) {
            case 'percentage_per_sale':
                return [
                    'commission_value' => $rateJson['value'],
                    'commission_value_formatted' => $rateJson['value'] . '%',
                    'type' => $rateType,
                    'type_formatted' => $typeFormatted,
                ];
                //            case 'tier_based' :
                //                return static::getTierBasedAmountDetail($commissionTier);
            case 'fixed':
                return [
                    'commission_value' => $rateJson['value'],
                    'commission_value_formatted' => Functions::formatAmount($rateJson['value']),
                    'type' => $rateType,
                    'type_formatted' => $typeFormatted,
                ];
            case 'tier_based':
            case 'rule_based':
            case 'no_commission':
                return [
                    'commission_value' => 0,
                    'commission_value_formatted' => 0,
                    'type' => $rateType,
                    'type_formatted' => $typeFormatted,
                ];
        }
    }

    public static function getCommissionTierTypeLabel($type)
    {
        switch ($type) {
            case 'percentage_per_sale':
                return "Percentage Per Sale";
            case 'fixed':
                return 'Flat Rate Per Order';
            case 'no_commission':
                return 'No Commission';
            case 'tier_based':
                return 'Tier Based';
            case 'rule_based':
                return 'Rule Based';
        }
    }

    public static function getRecurringCommissionOptions($commissionTier)
    {
        $options = Functions::jsonDecode($commissionTier->recurring_commission_options);

        if (empty($options)) {
            return [
                'type' => 'lifetime',
                'value' => 0
            ];
        }
        return $options;
    }


    public static function recurringCommissionOptionsDataFromRequest($request)
    {
        $options = $request->get('recurring_commission_options');

        if (empty($options)) return null;

        return wp_json_encode([
            'type' => $options['type'],
            'value' => $options['value']
        ]);
    }

    public static function getRecursiveDataToStore($commissionTierData, Request $request)
    {
        if (rwpa_app()->get('is_pro_plugin')) {
            return apply_filters('rwpa_get_pro_recursive_data_to_store', $commissionTierData, $request);
        }

        return apply_filters('rwpa_get_core_recursive_data_to_store', $commissionTierData, $request);
    }

    /**
     * @group core
     *
     */
    public static function getCoreRecursiveData($commissionTierData, Request $request)
    {
        $commissionTierData['recurring_commission_enabled'] = 0;
        $commissionTierData['recurring_commission_options'] = null;
        return $commissionTierData;
    }

    public static function isProType($type)
    {
        return $type == 'advanced';
    }
}
