<?php

namespace EDDA\Affiliate\Core\Controllers\StoreFront;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use RelayWp\Affiliate\Core\Models\Customer;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Program;

class OrderController
{
    public static function isRecurringOrder($data, $order)
    {
        if (!empty($data['affiliate']) && !empty($data['medium'])) return $data;

        $email = $order->email;

        $member = Member::query()
            ->where("email = %s", [$email])
            ->where("type = %s", ['customer'])
            ->first();

        if (empty($member)) {
            return $data;
        }

        $customers = Customer::query()->where("member_id  = %d", [$member->id])->get();

        if (empty($customers)) return $data;

        $customer_ids = [];

        foreach ($customers as $customer) {
            $customer_ids[] = $customer->id;
        }

        if (empty($customer_ids)) return $data;

        $customer_ids = implode("','", $customer_ids);

        // ISSUE: 216

        $affiliate = null;

        //GET affiliate from a cookie if present
        $referralCode = Functions::isAffiliateCookieSet();
        if (!empty($referralCode)) {
            $affiliate = Affiliate::query()->findBy('referral_code', $referralCode);
        }

        $previous_order = Order::query()->where("customer_id IN ('" . $customer_ids . "')")
            ->when(!empty($affiliate), function ($query) use ($affiliate) {
                return $query->where("affiliate_id = %d", [$affiliate->id]);
            })
            ->orderBy('created_at', 'DESC')
            ->first();

        if (empty($previous_order)) return [];

        if ($previous_order->recurring_parent_id) {
            $recurring_order = Order::query()->find($previous_order->recurring_parent_id);
        } else {
            $recurring_order = $previous_order;
        }

        if (empty($affiliate)) {
            $affiliate = Affiliate::query()->find($recurring_order->affiliate_id);
        }

        if (empty($affiliate)) return [];

        if ($affiliate->program_id != $recurring_order->program_id) {
            return [];
        }

        $program = Program::query()->find($recurring_order->program_id);

        if (!Program::isValid($program)) return [];

        $commission_tier = CommissionTier::query()->where("program_id = %d", [$program->id])->first();

        if (!$commission_tier->recurring_commission_enabled) {

            return [];
        }

        $options = $commission_tier->recurring_commission_options;

        $options = Functions::jsonDecode($options);

        if (empty($options)) return [];

        $recurring_type = $options['type'];

        if ($recurring_type == 'lifetime') {
            return [
                'recurring_order_from' => $recurring_order,
                'affiliate' => $affiliate,
                'medium' => 'recurring',
            ];
        }

        $allowed_count = $options['value'];

        $recurring_orders_count = Order::query()->where("program_id = %d AND recurring_parent_id = %d", [$program->id, $recurring_order->id])->count();

        if ($recurring_orders_count < $allowed_count) {
            return [
                'recurring_order_from' => $recurring_order,
                'affiliate' => $affiliate,
                'medium' => 'recurring',
            ];
        }

        return $data;
    }
}