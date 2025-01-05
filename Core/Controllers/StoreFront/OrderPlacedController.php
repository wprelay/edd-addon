<?php

namespace EDDA\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use EDDA\Affiliate\Core\Models\Customer;
use EDDA\Affiliate\Core\Models\Member;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Product;
use RelayWp\Affiliate\Core\Models\Program;
//use Relaywp\Affiliate\Core\Models\Rules;
use RelayWp\Affiliate\Core\Models\Transaction;
use EDD_Customer;
use EDD_Payment;

class OrderPlacedController
{

    public static function orderCreatedFromBlockCheckout($order_id)
    {
        static::orderCreated($order_id, []);
    }
    //order created only store the affiliate id to order meta

    //order status updated - create or update order in our db when processing / on hold / completed.

    // calculate commission and update into commission earnings
    public static function orderCreated($order_id, $data)
    {
        $order = edd_get_order($order_id);
        $track_order_data = apply_filters('rwpa_edd_track_affiliate_order', [], $order);
        if (empty($track_order_data)) {
            return false;
        }

        $affiliate = $track_order_data['affiliate'];
        $medium = $track_order_data['medium'];
        if (empty($affiliate)) {
            return;
        }

        if (!Affiliate::isAffiliateApproved($affiliate->status)) {
            return;
        }

        $program = Program::query()->find($affiliate->program_id);

        $commission_tier = CommissionTier::query()->where("program_id = %d", [$program->id])->first();

        if (!Program::isValid($program)) {
            return;
        }
        $billingEmail = $order->email;
        $member = Member::query()->find($affiliate->member_id);

        if (empty($member)) return;

        if ($member->email == $billingEmail) {
            //Affiliate made a sale using their own link.
            return;
        }

        $meta_key = Affiliate::AFFILIATE_META_KEY_FOR_ORDER;

        $meta_value = $affiliate->referral_code;

        $order_id = $order->id;

        edd_update_payment_meta($order_id, $meta_key, $meta_value);
        //        update_post_meta($order_id, );
        edd_update_payment_meta($order_id, Affiliate::ORDER_FROM_META_KEY, $medium);

        edd_update_payment_meta($order_id, Affiliate::ORDER_AFFILIATE_FOR, $affiliate->id);

        edd_update_payment_meta($order_id, Affiliate::ORDER_AFFILIATE_SESSION_EMIL, $order->email);

        if ($medium == 'recurring' && isset($track_order_data['recurring_order_from']) && is_object($track_order_data['recurring_order_from'])) {
            edd_update_payment_meta($order_id,Affiliate::ORDER_RECURRING_ID, $track_order_data['recurring_order_from']->id);
        }
        $member = Member::query()->find($affiliate->member_id);
        $new_sale_made_email = apply_filters('rwpa_affiliate_new_sale_made_email_enabled', Settings::get('email_settings.admin_emails.affiliate_sale_made'));
        $customer_id=$order->customer_id;
        $customer = new EDD_Customer($customer_id);
        if ($new_sale_made_email) {
            do_action('rwpa_send_new_sale_made_email', [
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'email' => $member->email,
                'referral_code' => $affiliate->referral_code,
                'referral_link' => Affiliate::getReferralCodeURL($affiliate),
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'order_amount' => $order->total,
                'order_created_at' => $order->date_created
            ], $order_id);
        }
    }


    public static function orderStatusUpdated($order_id,$new_status, $old_status)
    {
        $order = edd_get_order($order_id);
        $session_email = edd_get_payment_meta($order_id, Affiliate::ORDER_AFFILIATE_SESSION_EMIL, true);
        if ($session_email != $order->email) {
            return;
        }
        $affiliateReferralCode = edd_get_payment_meta($order_id, Affiliate::AFFILIATE_META_KEY_FOR_ORDER, true);

        if (!$affiliateReferralCode) {
            return;
        }

        $affiliate = Affiliate::query()->findBy('referral_code', $affiliateReferralCode);

        if (empty($affiliate)) {
            return;
        }
        $payment = new EDD_Payment($order_id);
        $status = $payment->status;

        $successful_order_statuses = Settings::get('affiliate_settings.successful_order_status');
        $failure_order_statues = Settings::get('affiliate_settings.failure_order_status');

        $program = Program::query()->find($affiliate->program_id);

        $order_already_exists = Order::query()->where('woo_order_id = %s', [$order->id])->first();

        if (!Program::isValid($program) && empty($order_already_exists)) {
            return;
        }

        $relayWpOrder = static::captureOrder($order, $affiliate);

        if (in_array($status, $successful_order_statuses)) {
            static::captureCommission($order, $relayWpOrder, $affiliate, $program);
        } else if (in_array($status, $failure_order_statues)) {
            static::revertCommission($order, $relayWpOrder, $affiliate, $program);
        }
    }

    private static function captureCommission($order, $relayWpOrder, $affiliate, $program)
    {
        $commissionEarnings = CommissionEarning::query()
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("order_id = %d", [$relayWpOrder->id])
            ->get();

        if (!empty($commissionEarnings)) {
            //Commission Already Calculated Reverting
            return false;
        }

        $commissionTier = CommissionTier::findBy('program_id', $program->id);

        $commissionDetails = Program::calculateCommission($order, $relayWpOrder, $program);

        $commissionEarningIds = [];
        if ($commissionDetails) {
            $commissionEarningId = CommissionEarning::createCommissionEarning($commissionDetails, $affiliate, $order, $relayWpOrder, $program);

            $commissionEarningIds[] = $commissionEarningId;
        }

        $active_rules_available = apply_filters('rwpa_wprelay_get_active_rules_count', 0, $program);


        if ($commissionTier->rate_type != CommissionTier::RULE_BASED_TYPE && $active_rules_available) {
            $bonusCommissionDetails = apply_filters('rwpa_wprelay_calculate_bonus_from_rules', [], $order, $relayWpOrder, $program);

            $bonusCommissionDetails['type'] = 'bonus';

            $bonusCommissionEarningId = CommissionEarning::createCommissionEarning($bonusCommissionDetails, $affiliate, $order, $relayWpOrder, $program);

            $commissionEarningIds[] = $bonusCommissionEarningId;
        }

        $commissionEarningIds = array_unique(array_filter($commissionEarningIds));

        if (!empty($commissionEarningIds)) {
            $autoApproveCommission = apply_filters('rwpa_is_auto_approval_enabled', Settings::get('affiliate_settings.general.auto_approve_commission'));

            if ($autoApproveCommission) {
                CommissionEarning::triggerAutoApproveJob($commissionEarningIds);
            }
        }
    }

    private static function revertCommission($order, $relayWpOrder, $affiliate, $program)
    {
        $commissionEarnings = CommissionEarning::query()->where("order_id = %d", [$relayWpOrder->id])->get();

        if (empty($commissionEarnings)) return;

        foreach ($commissionEarnings as $commissionEarning) {

            if ($commissionEarning->status == CommissionEarning::APPROVED) {
                Transaction::query()->create([
                    'affiliate_id' => $commissionEarning->affiliate_id,
                    'type' => Transaction::DEBIT,
                    'currency' => $commissionEarning->commission_currency,
                    'amount' => $commissionEarning->commission_amount,
                    'transactionable_id' => $commissionEarning->id,
                    'transactionable_type' => 'commission',
                    'system_note' => "Commission Rejected #{$commissionEarning->id} Due to Order Failure Status #{$order->woo_order_id} {$order->get_status()}",
                    'created_at' => Functions::currentUTCTime(),
                ]);
            } else if ($commissionEarning->status == CommissionEarning::REJECTED) {
                //already rejected
            }

            CommissionEarning::query()->update([
                'status' => CommissionEarning::REJECTED,
                'reason' => "From Failure Order {$order->get_status()}",
                'updated_at' => Functions::currentUTCTime(),
            ], [
                'id' => $commissionEarning->id
            ]);
        }
    }


    private static function captureOrder($order, $affiliate)
    {
        $relayWpMember = Member::query()
            ->where("type = %s", ['customer'])
            ->where("email = %s", [$order->email])
            ->first();

        $relayWpMemberId = empty($relayWpMember) ? Member::createMemberFromOrder($order) : $relayWpMember->id;

        if (empty($relayWpMemberId)) return false;

        $relayWpCustomer = Customer::query()->where("member_id = %d", [$relayWpMemberId])
            ->where("affiliate_id = %d", [$affiliate->id])
            ->first();

        $relayWpCustomerId = empty($relayWpCustomer) ? Customer::createCustomer($order, $affiliate, $relayWpMemberId) : $relayWpCustomer->id;

        if (empty($relayWpCustomerId)) return false;

        $relayWpOrder = Order::query()->where("woo_order_id = %d", [$order->get_id()])->first();

        //TODO: Need to improve this logic
        $orderAlreadyCreated = false;

        if (!empty($relayWpOrder)) {
            $orderAlreadyCreated = true;
        }

        $relayWpOrderId = empty($relayWpOrder) ? Order::createOrder($order, $relayWpCustomerId, $affiliate) : Order::updateOrder($order, $relayWpOrder);

        if ($orderAlreadyCreated) {
            return $relayWpOrder;
        }

        //Getting the newly created order
        $relayWpOrder = Order::query()->where("woo_order_id = %d", [$order->get_id()])->first();

        $order_items = $order->get_items();

        // Loop through the order items to get product details.
        foreach ($order_items as $item_id => $item) {
            // Product details for the current item.
            $wooProductId = $item->get_product_id();
            $wooProductName = $item->get_name();
            $wooProductQuantity = $item->get_quantity();
            $wooProductPrice = $item->get_total();

            Product::query()->create([
                'woo_product_id' => $wooProductId,
                'order_id' => $relayWpOrderId,
                'name' => $wooProductName,
                'quantity' => $wooProductQuantity,
                'price' => $wooProductPrice,
                'created_at' => Functions::currentUTCTime(),
                'updated_at' => Functions::currentUTCTime(),
            ]);
        }

        return $relayWpOrder;
    }
}
