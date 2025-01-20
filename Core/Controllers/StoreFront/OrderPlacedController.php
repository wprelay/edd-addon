<?php

namespace EDDA\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\EDD;
use Relaywp\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use EDDA\Affiliate\Core\Models\Customer;
use EDDA\Affiliate\Core\Models\Member;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Product;
use EDDA\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\Models\Transaction;
use EDD_Customer;

class OrderPlacedController
{

    public static function orderCreatedFromBlockCheckout($order_id)
    {
        static::orderCreated($order_id, []);
    }
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
        $status = EDD::getOrderStatus($order->status);

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
            $commissionEarningId =apply_filters('rwpa_edd_create_commission_earning',$commissionDetails, $affiliate, $order, $relayWpOrder, $program);

            $commissionEarningIds[] = $commissionEarningId;
        }

        $active_rules_available = apply_filters('rwpa_wprelay_get_active_rules_count', 0, $program);


        if ($commissionTier->rate_type != CommissionTier::RULE_BASED_TYPE && $active_rules_available) {
            $bonusCommissionDetails = apply_filters('rwpa_edd_wprelay_calculate_bonus_from_rules', [], $order, $relayWpOrder, $program);

            $bonusCommissionDetails['type'] = 'bonus';

            $bonusCommissionEarningId = apply_filters('rwpa_edd_create_commission_earning',$bonusCommissionDetails, $affiliate, $order, $relayWpOrder, $program);

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
                    'system_note' => "Commission Rejected #{$commissionEarning->id} Due to Order Failure Status #{$order->woo_order_id} {$order->status}",
                    'created_at' => Functions::currentUTCTime(),
                ]);
            } else if ($commissionEarning->status == CommissionEarning::REJECTED) {
                //already rejected
            }

            CommissionEarning::query()->update([
                'status' => CommissionEarning::REJECTED,
                'reason' => "From Failure Order {$order->status}",
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

        $relayWpOrder = Order::query()->where("woo_order_id = %d", [$order->id])->first();

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
        $relayWpOrder = Order::query()->where("woo_order_id = %d", [$order->id])->first();

        $order_items =edd_get_payment_meta_cart_details($order->id);
        // Loop through the order items to get product details.
        foreach ($order_items as $item_id => $item) {
            // Product details for the current item.
            $wooProductId = $item['id'];
            $wooProductName = $item['name'];
            $wooProductQuantity = $item['quantity'];
            $wooProductPrice = $item['price'];

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
