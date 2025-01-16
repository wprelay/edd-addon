<?php

namespace EDDA\Affiliate\App\Helpers;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Pro\Models\Rules;
use RelayWp\Affiliate\Pro\Models\RulesCommissionDetail;
use RelayWp\Affiliate\Pro\Models\RulesMeta;

class RulesHelper
{
    public static function calculateCommissions($data, $order, $relayWpOrder, $commissionTier)
    {
        try {
            $bonus_commissions = [];

            $program_id = $relayWpOrder->program_id;
            $affiliate_id = $relayWpOrder->affiliate_id;

            $available_rules = static::getAvailableRules($program_id, $affiliate_id);

            if (empty($available_rules)) {
                return [
                    'commission_amount' => 0,
                    'commission_display' => true,
                    'keep_entry' => true
                ];
            }

            foreach ($available_rules as $rule) {
                [$available, $amount, $additional_data] = static::checkCommissionAvailable($rule, $order);
                if (!$available || !apply_filters('rwpa_wprelay_rule_commission_calculate_for_order', true, $order, $rule)) continue;

                $bonus_commissions[] = [
                    'rule_id' => $rule->rule_id,
                    'commission_amount' => $amount,
                    'woo_order_id' => $order->id,
                    'order_id' => $relayWpOrder->id,
                    'additional_data' => $additional_data
                ];
            }

            if (empty($bonus_commissions)) {
                return [
                    'commission_amount' => 0,
                    'commission_display' => true,
                    'keep_entry' => true
                ];
            }

            $total_bonus_amount = 0;

            $filter_type = apply_filters('rwpa_wprelay_rule_commission_get_setting', 'all_commission_amount');

            if (count($bonus_commissions) > 1 && $filter_type != 'all_commission_amount') {
                switch ($filter_type) {
                    case "highest_commission_amount":
                        $bonus_commissions = static::getSingleCommissionBasedOnSettings($bonus_commissions, 'highest_bonus_amount');
                        break;
                    case "least_commission_amount":
                        $bonus_commissions = static::getSingleCommissionBasedOnSettings($bonus_commissions, 'least_bonus_amount');
                        break;
                }
            }

            if (empty($bonus_commissions)) {
                return [
                    'commission_amount' => 0,
                    'commission_display' => true,
                    'keep_entry' => true
                ];
            }

            foreach ($bonus_commissions as $bonus_commission) {
                RulesCommissionDetail::query()
                    ->create([
                        'rule_id' => $bonus_commission['rule_id'],
                        'order_id' => $relayWpOrder->id,
                        'woo_order_id' => $order->id,
                        'commission_amount' => $bonus_commission['commission_amount'],
                        'additional_data' => wp_json_encode($bonus_commission['additional_data'] ?? [])
                    ]);

                $total_bonus_amount += $bonus_commission['commission_amount'];
            }

            $updated_total_bonus_amount = apply_filters('rwpa_wprelay_get_bonus_amount', $total_bonus_amount, $order);
            if (!is_numeric($updated_total_bonus_amount)) {
                $updated_total_bonus_amount = 0;
            }
            return [
                'commission_amount' => $updated_total_bonus_amount,
            ];
        } catch (\Exception $exception) {
            PluginHelper::logError('Bonus Calculation Error', [__CLASS__, __FUNCTION__], $exception);
        }
    }

    public static function getSingleCommissionBasedOnSettings($bonus_commissions, $type)
    {
        $filterKey = 'bonus_amount';

        // Initialize the current commission as null
        $current = null;

        if ($type == 'least_commission_amount') {
            foreach ($bonus_commissions as $commission) {
                if ($current === null || $commission[$filterKey] <= $current[$filterKey]) {
                    $current = $commission;
                }
            }
        } else if ($type == 'highest_commission_amount') {
            foreach ($bonus_commissions as $commission) {
                if ($current === null || $commission[$filterKey] >= $current[$filterKey]) {
                    $current = $commission;
                }
            }
        }

        return empty($current) ? [] : [$current];
    }


    public static function getBonusCommission($amount, $type, $value)
    {
        if ($type == 'fixed') {
            return $value > 0 ? $value : 0;
        } else if ($type == 'percentage') {
            return ($amount * ($value / 100));
        }

        return 0;
    }

    /**
     * STEPS are explained in @param $relayWpOrder
     * @param $all_program_bonus_ids
     * @param $specific_program_bonus_ids
     * @param $all_affiliate_bonus_ids
     * @param $specific_affiliate_bonus_ids
     * @return array
     * @see static::calculateBonuses()
     */

    public static function checkCommissionAvailable($rule, $eddOrder)
    {
        // Get the payment meta
        $payment_meta = edd_get_payment_meta($eddOrder->id);
        $order_line_items = $payment_meta['cart_details'] ?? []; // Retrieve cart details

        $commission_data = $rule->commission_data;
        $commission_data = Functions::jsonDecode($commission_data);

        $product_ids = $commission_data['product_ids'] ?? [];
        $category_ids = $commission_data['category_ids'] ?? [];
        $based_on = $commission_data['based_on'];

        $product_amount = 0;
        $excludeTaxes = (bool)Settings::get('general_settings.commission_settings.exclude_taxes', false);

        $additional_data = [];
        $commission_available = false;

        foreach ($order_line_items as $item) {
            $available = false;

            // Get the product ID
            $product_id = $item['id'];

            if ($based_on === 'all_products') {
                $available = true;
            }

            if ($based_on === 'product_in_list') {
                if (in_array($product_id, $product_ids)) {
                    $available = true;
                }
            }

            if ($based_on === 'product_not_in_list') {
                if (!in_array($product_id, $product_ids)) {
                    $available = true;
                }
            }

            if ($based_on === 'category_in_list') {
                $product_category_ids = self::edd_get_download_category_ids($product_id);

                if (!empty(array_intersect($product_category_ids, $category_ids))) {
                    $available = true;
                }
            }

            if ($based_on === 'category_not_in_list') {
                $product_category_ids = self::edd_get_download_category_ids($product_id);

                if (empty(array_intersect($product_category_ids, $category_ids))) {
                    $available = true;
                }
            }

            if ($available) {
                $commission_available = true;
                $additional_data[$rule->rule_id]['exclude_taxes'] = $excludeTaxes;
                $additional_data[$rule->rule_id]['bonus_data'] = $commission_data;

                $amount = static::getItemAmountForBonusCalculation($item, $excludeTaxes);
                $product_amount += ($amount > 0) ? static::getBonusCommission($amount, $commission_data['type'], $commission_data['value']) : 0;
            }
        }

        return [$commission_available, $product_amount, $additional_data];
    }

    /**
     * Helper function to get the categories for a given EDD download (product)
     */
    private static function edd_get_download_category_ids($download_id)
    {
        $terms = wp_get_post_terms($download_id, 'download_category', ['fields' => 'ids']);
        return is_wp_error($terms) ? [] : $terms;
    }

    public static function getItemAmountForBonusCalculation($item, $excludeTaxes)
    {
        $lineItemTaxAmount = $item['tax'];

        //        if customer want to issue commisison amount for each product need to update logi by using filter

        $total_amount = $item['price']+ $lineItemTaxAmount;

        if ($excludeTaxes) {
            // Get the tax data
            $total_amount -= $lineItemTaxAmount;
        }

        return $total_amount;
    }

    public static function getAvailableRules($program_id, $affiliate_id)
    {
        $rulesTable = Rules::getTableName();
        $rulesMetaTable = RulesMeta::getTableName();

        $current_utc_time = Functions::currentUTCTime();

        //STEP = 1
        $rules = Rules::query()->select("{$rulesTable}.id as rule_id, commission_data, affiliates_type, GROUP_CONCAT({$rulesMetaTable}.model_id SEPARATOR ',') as affiliate_ids")
            ->leftJoin($rulesMetaTable, "{$rulesMetaTable}.rule_id = {$rulesTable}.id AND {$rulesMetaTable}.type = 'affiliate'")
            ->where("program_id = %d", [$program_id])
            ->where("status = %s", ['active'])
            ->where("(start_date <= %s OR start_date is null)", [$current_utc_time])
            ->where("(end_date > %s OR end_date is null)", [$current_utc_time])
            ->where("deleted_at is null")
            ->groupBy("{$rulesTable}.id");


        $rules = $rules->get();

        $available_rules = [];

        foreach ($rules as $rule) {
            if ($rule->affiliates_type == 'all') {
                $available_rules[] = $rule;
            } else {
                $ids = $rule->affiliate_ids;
                if (empty($ids)) continue;

                $current_affiliate_ids = explode(',', $ids);
                if (in_array($affiliate_id, $current_affiliate_ids)) {
                    $available_rules[] = $rule;
                }
            }
        }
        return $available_rules;
    }


    public static function calculateBonusCommission($data, $order, $relayWpOrder, $commissionTier)
    {
        $data = apply_filters("rwpa_get_commission_details_for_rule_based_type", [], $order, $relayWpOrder, $commissionTier);
        return apply_filters('rwpa_commission_details', $data, $order);
    }

    public static function isProductIsValidForAffiliateLink($status, $product_id, $program_id, $affiliate_id)
    {
        if (empty($program_id) || empty($affiliate_id)) {
            return false;
        }

        $rules = static::getAvailableRules($program_id, $affiliate_id);

        $product = edd_get_download($product_id);

        if (!$product) {
            return false;
        }

        $available = false;

        foreach ($rules as $rule) {
            $commission_data = $rule->commission_data;

            $commission_data = Functions::jsonDecode($commission_data);

            $product_ids = $commission_data['product_ids'] ?? [];
            $category_ids = $commission_data['category_ids'] ?? [];
            $based_on = $commission_data['based_on'];

            if ($based_on === 'all_products') {
                $available = true;
            } elseif ($based_on === 'product_in_list') {
                if (in_array($product_id, $product_ids)) {
                    $available = true;
                }
            } elseif ($based_on === 'product_not_in_list') {
                if (!in_array($product_id, $product_ids)) {
                    $available = true;
                }
            } elseif ($based_on === 'category_in_list') {
                $product_category_ids = wp_get_post_terms($product_id, 'download_category', ['fields' => 'ids']);
                if (!empty(array_intersect($product_category_ids, $category_ids))) {
                    $available = true;
                }
            } elseif ($based_on === 'category_not_in_list') {
                $product_category_ids = wp_get_post_terms($product_id, 'download_category', ['fields' => 'ids']);
                if (empty(array_intersect($product_category_ids, $category_ids))) {
                    $available = true;
                }
            }

            if ($available) {
                return true;
            }
        }

        return false;
    }

}
