<?php

namespace EDDA\Affiliate\Core\Models;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Model;
use RelayWp\Affiliate\App\Services\Settings;

class CommissionEarning extends Model
{
    protected static $table = 'commission_earnings';

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public function createTable()
    {
        $table = static::getTableName();
        $affiliateTable = Affiliate::getTableName();
        $orderTable = Order::getTableName();
        $commissionTierTable = CommissionTier::getTableName();
        $charset = static::getCharSetCollate();

        return "CREATE TABLE {$table} (
                      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                      affiliate_id BIGINT UNSIGNED NULL,
                      order_id BIGINT UNSIGNED NULL,
                      program_id BIGINT UNSIGNED NOT NULL,
                      commission_amount DECIMAL(10, 2) NOT NULL,
                      show_commission boolean default 1,
                      commission_currency VARCHAR(255) NOT NULL,
                      reason TEXT NULL,
                      type VARCHAR(255) default 'commission' NOT NULL,
                      status VARCHAR(255) NOT NULL,
                      date_created TIMESTAMP NOT NULL,
                      created_at TIMESTAMP NULL,
                      updated_at TIMESTAMP NULL,
                      deleted_at timestamp NULL,
                      PRIMARY KEY (id)
                      
        ) {$charset};";
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
    public static function triggerAutoApproveJob($commissionEarningIds)
    {
        $delay = apply_filters('rwpa_auto_approval_delay_in_days', Settings::get('affiliate_settings.general.auto_approve_delay_in_days'));
        $delay = $delay ?: 0;
        wp_schedule_single_event(strtotime("+{$delay} days"), 'rwpa_auto_approve_commission', [$commissionEarningIds]);
    }

    /*public static function triggerAutoApproveJob($commissionEarningIds)
    {
        if (\ActionScheduler::is_initialized()) {
            $delay = apply_filters('rwpa_auto_approval_delay_in_days', Settings::get('affiliate_settings.general.auto_approve_delay_in_days'));
            $delay = $delay ?: 0;
            as_schedule_single_action(strtotime("+{$delay} days"), 'rwpa_auto_approve_commission', [$commissionEarningIds]);
        }
    }*/

    public static function sendCommissionStatusMail($commissionStatus, $commissionEarning, $affiliate, $member)
    {
        $relayWpOrder = Order::query()->find($commissionEarning->order_id);

        $email_data = [
            'affiliate_name' => $member->first_name . ' ' . $member->last_name,
            'last_name' => $member->first_name,
            'email' => $member->email,
            'commission_amount' => $commissionEarning->commission_amount,
            'commission_type' => $commissionEarning->type,
            'commission_currency' => edd_get_currency(),
            'commission_order_id' => $commissionEarning->order_id,
            'sale_date' => Functions::utcToWPTime($relayWpOrder->created_at),
            'relay_wp_order' => $relayWpOrder
        ];

        if ($commissionStatus == CommissionEarning::APPROVED && Settings::get('email_settings.affiliate_emails.commission_approved')) {
            do_action('rwpa_affiliate_commission_approved_email', $email_data);
        } else if ($commissionStatus == CommissionEarning::REJECTED && Settings::get('email_settings.affiliate_emails.commission_rejected')) {
            do_action('rwpa_affiliate_commission_rejected_email', $email_data);
        }
    }
}
