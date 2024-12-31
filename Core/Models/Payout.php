<?php

namespace RelayWp\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Model;
use RelayWp\Affiliate\App\Services\Settings;

class Payout extends Model
{
    protected static $table = 'payouts';

    public function createTable()
    {
        $table = static::getTableName();
        $affiliateTable = Affiliate::getTableName();
        $charset = static::getCharSetCollate();

        return "CREATE TABLE {$table} (
                    id BIGINT UNSIGNED AUTO_INCREMENT,
                    amount DECIMAL(10, 2) NOT NULL,
                    affiliate_note text NULL,
                    admin_note text NULL,
                    paid_at TIMESTAMP NOT NULL,
                    paid_by BIGINT UNSIGNED NULL,
                    is_system_reverted BOOL DEFAULT FALSE,
                    payment_source VARCHAR(255) NOT NULL,
                    currency VARCHAR(255) NOT NULL,
                    status VARCHAR(255) default  'success',
                    payout_details  JSON NULL,
                    revert_reason  LONGTEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    affiliate_id BIGINT UNSIGNED NULL,
                    deleted_at timestamp NULL,
                    PRIMARY KEY (id)
                ) {$charset};";
    }

    public static function getPayoutById($id)
    {
        $payout = Payout::query()->find($id);

        return $payout;
    }

    public static function markAsSucceeded($payoutId, $additional)
    {
        $payout = self::query()->find($payoutId);

        if ($payout->status == 'success') {
            return;
        }

        self::query()->update([
            'status' => 'success',
            'payout_details' => empty($additional) ? null : wp_json_encode($additional)
        ], ['id' => $payoutId]);

        $affiliate = Affiliate::query()->findOrFail($payout->affiliate_id);
        $member = Member::query()->findOrFail($affiliate->member_id);

        if (Settings::get('email_settings.affiliate_emails.payment_processed')) {
            do_action('rwpa_payment_processed_email', [
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'email' => $member->email,
                'referral_code' => $affiliate->referral_code,
                'referral_link' => Affiliate::getReferralCodeURL($affiliate),
                'amount' => $payout->amount,
                'currency' => WC::getWcCurrencySymbol(),
                'payment_source' => ucwords($payout->payment_source),
                'payout_date' => Functions::utcToWPTime($payout->created_at),
                'payout_affiliate_notes' => $payout->affiliate_note,
            ]);
        }
    }

    public static function markAsFailed($payoutId, $additional = [])
    {
        $payout = self::query()->find($payoutId);

        if ($payout->status == 'failed') {
            return;
        }

        self::query()->update([
            'status' => 'failed',
            'payout_details' => empty($additional) ? null : wp_json_encode($additional)
        ], ['id' => $payoutId]);

        Transaction::query()->create([
            'affiliate_id' => $payout->affiliate_id,
            'type' => Transaction::CREDIT,
            'currency' => $payout->currency,
            'amount' => $payout->amount,
            'transactionable_id' => $payoutId,
            'transactionable_type' => 'payout',
            'system_note' => "Payout Failed #{$payoutId}",
            'created_at' => Functions::currentUTCTime(),
        ]);
    }

    public static function getPaymentMethods()
    {
        $paymentMethods = apply_filters('rwpa_get_payment_methods', []);

        $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
        $data = Functions::jsonDecode($rwpa_settings);
        $rwpa_settings = $data ? $data : [];

        $paymentSettings = isset($rwpa_settings['payment_settings']) ? $rwpa_settings['payment_settings'] : [];

        foreach ($paymentMethods as &$paymentMethod) {
            $value = $paymentMethod['value'];

            if ($paymentMethod['value'] == 'manual') {
                $paymentMethod['additional'] = [
                    'enabled' => true
                ];
            } else if (isset($paymentSettings[$value])) {
                $paymentMethod['additional'] = $paymentSettings[$value];
            } else {
                $paymentMethod['additional'] = [];
            }
        }

        return $paymentMethods;
    }


    public static function isPaymentModeEnabled($source)
    {
        $paymentMethods = self::getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod['value'] == $source) {
                return Functions::getBoolValue($paymentMethod['additional']['enabled']);
            }
        }

        return false;
    }

    public static function isPaymentValidationsPassed($source, $rwpCurrency, $amount)
    {

        if ($source == 'coupon') {
            $options = get_option(CouponPayout::OPTION_KEY, "[]");
            $options = Functions::jsonDecode($options);
            $enable_minimum_payout_threshold = $options['enable_minimum_payout_threshold'] ?? false;
            $minimum_thersold_payout_amount = $options['minimum_thersold_payout_amount'] ?? 0;

            if ($enable_minimum_payout_threshold) {
                if ($amount < $minimum_thersold_payout_amount) {
                    return [
                        false,
                        __(vsprintf('Minimum payout threshold Amount should be %s for coupon payout for each', [$minimum_thersold_payout_amount]), 'relay-affiliate-marketing')
                    ];
                }
            }
        }

        return [
            true,
            ''
        ];
    }
}
