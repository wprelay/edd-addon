<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\WC;
use Cartrabbit\Request\Request;
use RelayWp\Affiliate\App\Model;

class CustomerDiscount extends Model
{
    protected static $table = 'customer_discounts';

    public function createTable()
    {
        $table = static::getTableName();
        $charset = static::getCharSetCollate();
        return "
                CREATE TABLE {$table} (
                id BIGINT UNSIGNED AUTO_INCREMENT,
                program_id BIGINT UNSIGNED NOT NULL,
                discount_type VARCHAR(255) NOT NULL,
                min_requirements_enabled bool default false,
                coupon_amount BIGINT UNSIGNED NOT NULL,
                product_ids  JSON NULL,
                exclude_product_ids  JSON NULL,
                category_ids  JSON NULL,
                exclude_category_ids  JSON NULL,
                expiry_date  VARCHAR(255) NULL,
                individual_use  bool default false,
                exclude_sale_items  bool default false,
                free_shipping  bool default false,
                usage_limit_enabled bool default false,
                usage_limit_per_user  INTEGER NULL,
                usage_limit_per_coupon  INTEGER NULL,
                minimum_amount  BIGINT NULL,
                maximum_amount  BIGINT NULL,
                allowed_emails  JSON NULL,
                additional_data  JSON NULL,
                update_coupons boolean default 0,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                deleted_at timestamp NULL,
                PRIMARY KEY (id)
            ) {$charset};";
    }

    public static function getCustomerDiscountJson(Request $request)
    {
        $value = $request->get('customer_discount_options.value');

        $products = $request->get('customer_discount_options.min_requirements.products');
        $products = is_array($products) ? $products : [];
        $exclude_products = $request->get('customer_discount_options.min_requirements.exclude_products');
        $exclude_products = is_array($exclude_products) ? $exclude_products : [];

        $categories = $request->get('customer_discount_options.min_requirements.categories');
        $categories = is_array($categories) ? $categories : [];

        $exclude_categories = $request->get('customer_discount_options.min_requirements.exclude_categories');
        $exclude_categories = is_array($exclude_categories) ? $exclude_categories : [];

        $minRequirements = [
            'enabled' => $request->get('customer_discount_options.min_requirements.enabled'),
            'minimum_spend' => $request->get('customer_discount_options.min_requirements.minimum_spend') ?? null,
            'maximum_spend' => $request->get('customer_discount_options.min_requirements.maximum_spend') ?? null,
            'individual_use' => $request->get('customer_discount_options.min_requirements.individual_use') ?? false,
            'exclude_sale_items' => $request->get('customer_discount_options.min_requirements.exclude_sale_items') ?? false,
            'products' => $products,
            'exclude_products' => $exclude_products,
            'categories' => $categories,
            'exclude_categories' => $exclude_categories,
        ];

        $usageLimits = [
            'enabled' => $request->get('customer_discount_options.usage_limits.enabled') ?? false,
            'usage_limit_per_user' => $request->get('customer_discount_options.usage_limits.usage_limit_per_user') ?? 1,
            'usage_limit_per_coupon' => $request->get('customer_discount_options.usage_limits.usage_limit_per_coupon') ?? -1,
        ];


        $data = [
            'value' => $value,
            'min_requirements' => $minRequirements,
            'usage_limits' => $usageLimits
        ];

        return wp_json_encode($data);
    }

    public static function getCouponDiscountType($type)
    {
        switch ($type) {
            case 'percent':
                return 'percent';
            case 'fixed_product':
                return 'fixed_product';
            default:
                return 'fixed_cart';
        }
    }


    public static function reslveCustomerDiscountOptions(string $options)
    {
        $options = Functions::jsonDecode($options);

        return [];
    }

    /**
     * @param $programId
     * @param $discountType
     * @param $options
     * @return array
     */
    public static function getCustomerDiscountData($programId, $discountType, $options): array
    {
        $usageLimitEnabled = Functions::getBoolValue($options['usage_limits']['enabled'] ?? false);
        $minimumRequirementsEnabled = Functions::getBoolValue($options['min_requirements']['enabled'] ?? false);

        return [
            'program_id' => $programId,
            'discount_type' => $discountType,
            'coupon_amount' => $options['value'] ?? 0,
            'expiry_date' => $options['expiry_date'] ?? null,
            'min_requirements_enabled' => $minimumRequirementsEnabled,
            'free_shipping' => $discountType != 'no_discount' && Functions::getBoolValue($options['allow_free_shipping'] ?? false),
            'product_ids' => $minimumRequirementsEnabled ? wp_json_encode(WC::getIdsFromLabelsArray($options['min_requirements']['products'] ?? [])) : null,
            'exclude_product_ids' => $minimumRequirementsEnabled ? wp_json_encode(WC::getIdsFromLabelsArray($options['min_requirements']['exclude_products'] ?? [])) : null,
            'category_ids' => $minimumRequirementsEnabled ? wp_json_encode(WC::getIdsFromLabelsArray($options['min_requirements']['categories'] ?? [])) : null,
            'exclude_category_ids' => $minimumRequirementsEnabled ? wp_json_encode(WC::getIdsFromLabelsArray($options['min_requirements']['exclude_categories'] ?? [])) : null,
            'individual_use' => $minimumRequirementsEnabled && Functions::getBoolValue($options['min_requirements']['individual_use'] ?? false),
            'exclude_sale_items' => $minimumRequirementsEnabled && Functions::getBoolValue($options['min_requirements']['exclude_sale_items'] ?? false),
            'usage_limit_enabled' => $usageLimitEnabled,
            'usage_limit_per_user' => $usageLimitEnabled ? ($options['usage_limits']['usage_limit_per_user'] ?? null) : null,
            'usage_limit_per_coupon' => null,
            'minimum_amount' => $minimumRequirementsEnabled ? ($options['min_requirements']['minimum_spend'] ?? null) : null,
            'maximum_amount' => $minimumRequirementsEnabled ? ($options['min_requirements']['maximum_spend'] ?? null) : null,
        ];
    }

    public static function getCustomerDiscountValue($item)
    {
        $currency = WC::getWooCommerceCurrencySymbol();

        switch ($type = $item->discount_type) {
            case 'percent':
                return "{$item->coupon_amount}% - Percentage";
            case 'fixed_cart':
                return "{$item->coupon_amount}{$currency} -  Fixed Cart";
            case 'fixed_product':
                return "{$item->coupon_amount}{$currency} -  Per Product";
            case 'no_discount':
                return "No Discount";
        }
        $type = strtoupper($item->discount_type);
        return "{$item->coupon_amount} - {$type}";
    }
}
