<?php

namespace EDDA\Affiliate\App\Helpers;

use EDDA\Affiliate\Core\Models\CommissionTier;

class Product
{
    /*
     * Used in Snippets
     * s_wprelay_add_affiliate_copy_link_button - df8658c133c383f97a40372ac4a4c7dc
     */
    public static function showAffiliateLink($affiliate, $product, $commissionTier = null)
    {
        $commissionTier = CommissionTier::findBy('program_id', $affiliate->id);

        if (empty($commissionTier)) return false;

        if (empty($affiliate) || empty($product)) return false;

        $type = $commissionTier->base_type;

        if ($type == CommissionTier::SIMPLE || $type == CommissionTier::ADVANCED) {
            return true;
        }
        return apply_filters('rwp_is_product_falls_in_any_rule', false, $product->get_id(), $affiliate->program_id, $affiliate->id);
    }
}
