<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Exception\ModelNotFoundException;
use EDDA\Affiliate\App\Helpers\Functions;
use EDDA\Affiliate\App\Helpers\PluginHelper;
use EDDA\Affiliate\App\Helpers\EDD;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use RelayWp\Affiliate\App\Route;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateCommissionCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateCouponCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliatePayoutCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateSelectInfoCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateTransactionCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateProfileResource;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateSalesCollection;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use EDDA\Affiliate\Core\Models\Affiliate;
use EDDA\Affiliate\Core\Models\AffiliateCoupon;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use EDDA\Affiliate\Core\Models\Customer;
use EDDA\Affiliate\Core\Models\Member;
use EDDA\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use EDDA\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\Models\Transaction;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateProfileUpdateRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateUpdateCodeRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateUpdateRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateUpdateStatusRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\ChangeProgramRequest;

class AffiliateController
{
    //Need to implement with Pagination
    public function index(Request $request)
    {
        try {
            $memberTableName = Member::getTableName();
            $affiliateTableName = Affiliate::getTableName();
            $programTableName = Program::getTableName();
            $affiliateCouponTableName = AffiliateCoupon::getTableName();

            $currentPage = $request->get('current_page');
            $perPage = $request->get('per_page');
            $search = $request->get('search');
            $status = $request->get('status');
            $search_key = $request->get('search_key');

            $query = Affiliate::query()->select(
                " 
                       {$affiliateTableName}.status as status,
                       {$affiliateTableName}.referral_code as referral_code,
                       {$memberTableName}.first_name as first_name,
                       {$memberTableName}.last_name as last_name,
                      {$memberTableName}.id as member_id, 
                      {$affiliateTableName}.id as affiliate_id, 
                      {$memberTableName}.email as email, 
                      {$affiliateTableName}.phone_number as phone_number, 
                      {$affiliateTableName}.payment_email as payment_email, 
                      {$affiliateTableName}.tags as tags, 
                      {$affiliateTableName}.created_at as affiliate_created_at, 
                      {$affiliateTableName}.updated_at as affiliate_updated_at"
            )
                ->leftJoin($memberTableName, "{$memberTableName}.id = {$affiliateTableName}.member_id")
                ->leftJoin($programTableName, "{$programTableName}.id = {$affiliateTableName}.program_id")
                ->leftJoin($affiliateCouponTableName, "{$affiliateCouponTableName}.affiliate_id = {$affiliateTableName}.id")
                ->when($status, function ($query) use ($status, $affiliateTableName) {
                    return $query->where(" {$affiliateTableName}.status = %s ", [$status]);
                })
                ->when(!empty($search) && $search_key == 'email', function (Database $query) use ($search, $memberTableName) {
                    return $query->where(" {$memberTableName}.email LIKE %s ", ["%{$search}%"]);
                })
                ->when(!empty($search_key) && $search_key == 'name', function (Database $query) use ($search, $memberTableName) {
                    return $query->nameLike("{$memberTableName}.first_name", "{$memberTableName}.last_name", $search, true);
                })
                ->when(!empty($search_key) && $search_key == 'program', function (Database $query) use ($search, $programTableName) {
                    return $query->where("{$programTableName}.title LIKE %s", ["%{$search}%"]);
                })
                ->when(!empty($search_key) && $search_key == 'coupon' && $search != 'all', function (Database $query) use ($search, $affiliateCouponTableName) {
                    if ($search == 'active') {
                        return $query->where("{$affiliateCouponTableName}.deleted_at is NULL");
                    } else if ($search == 'deleted') {
                        return $query->where("{$affiliateCouponTableName}.deleted_at is NOT NULL");
                    }
                    return $query;
                })
                ->groupBy("{$affiliateTableName}.id")
                ->orderBy("{$affiliateTableName}.id", "DESC");

            $totalCount = $query->count();

            $affiliates = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            $overview = Affiliate::query()
                ->select("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count, 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                ")->first();

            $affiliate_count = [
                'approved_count' => $overview->approved_count ?? 0,
                'pending_count' => $overview->pending_count ?? 0,
                'rejected_count' => $overview->rejected_count ?? 0,
            ];

            return AffiliateCollection::collection([$affiliates, $affiliate_count, $totalCount, $currentPage, $perPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function create(Request $request)
    {
        $request->validate(new AffiliateRequest());

        Database::beginTransaction();
        try {
            //create member
            $memberData = [];

            $memberData['first_name'] = $request->get('first_name');
            $memberData['last_name'] = $request->get('last_name');
            $memberData['email'] = $email = $request->get('email', '', 'email');
            $memberData['type'] = $type = 'affiliate';

            $memberData['created_at'] = Functions::currentUTCTime();
            $memberData['updated_at'] = Functions::currentUTCTime();

            $isInserted = Member::query()->create($memberData);

            if (!$isInserted) {
                Response::error(['message' => __('Unable to Create Member Affiliate', 'relay-affiliate-marketing')]);
            }

            $affiliateData = [];

            $memberId = Member::query()->lastInsertedId();

            $affiliateData['status'] = 'pending';
            $affiliateData['member_id'] = $memberId;
            $affiliateData['program_id'] = $request->get('program_id');

            $affiliateData['created_at'] = Functions::currentUTCTime();
            $affiliateData['updated_at'] = Functions::currentUTCTime();

            $affiliateInserted = Affiliate::query()->create($affiliateData);
            $affiliateId = Affiliate::query()->lastInsertedId();

            $affiliate = Affiliate::query()->findOrFail($affiliateId);
            $program = Program::query()->findOrFail($affiliate->program_id);

            if ($program->auto_approve) {
                Affiliate::autoApprove($affiliate->id);
            }

            Database::commit();

            return Response::success([
                'message' => __('Affiliate Created Successfully', 'relay-affiliate-marketing'),
                'affiliate' => [
                    'id' => $affiliateId
                ]
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function show(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');

            $affiliate = Affiliate::findOrFail($affiliateId);
            $member = Member::findOrFail($affiliate->member_id);
            $program = Program::find($affiliate->program_id);

            $pending_payment_count = Payout::query()->where("status = %s", ['pending'])
                ->where("affiliate_id = %d", [$affiliate->id])
                ->count();

            return AffiliateProfileResource::resource([$affiliate, $member, $program, $pending_payment_count]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function update(Request $request)
    {
        $request->validate(new AffiliateProfileUpdateRequest());

        Database::beginTransaction();
        try {

            $memberId = $request->get('member_id');
            $affiliateId = $request->get('affiliate_id');

            $member = Member::findOrFail($memberId);
            $affiliate = Affiliate::findOrFail($affiliateId);

            //create member
            $memberData = [];

            $memberData['first_name'] = $request->get('first_name');
            $memberData['last_name'] = $request->get('last_name');
            $memberData['updated_at'] = Functions::currentUTCTime();

            Member::query()->update($memberData, ['id' => $member->id]);

            $affiliateData['tags'] = Affiliate::setTags($request->get('tags'));
            $affiliateData['social_links'] = Affiliate::setSocialLinks($request->get('social_links'));

            $affiliateData['shipping_address'] = Affiliate::setShippingAddress($request->get('shipping_address'));
            $affiliateData['phone_number'] = $request->get('phone_number');
            $affiliateData['payment_email'] = $request->get('billing_email');
            $affiliateData['updated_at'] = Functions::currentUTCTime();

            Affiliate::query()->update($affiliateData, ['id' => $affiliate->id]);

            Database::commit();

            return Response::success([
                'message' => __('Affiliate Updated Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (ModelNotFoundException $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function updateStatus(Request $request)
    {
        $request->validate(new AffiliateUpdateStatusRequest());
        Database::beginTransaction();
        try {
            $affiliateId = $request->get('affiliate_id');
            $status = $request->get('status');
            $program_id = $status == 'approved' ? $request->get('program_id') : null;

            $createWCAccount = $request->get('create_wc_account', false);

            $affiliate = Affiliate::query()->findOrFail($affiliateId);
            $member = Member::query()->findOrFail($affiliate->member_id);

            $affiliateData = [
                'status' => $status,
                'updated_at' => Functions::currentUTCTime(),
            ];

            if (is_null($affiliate->referral_code)) {
                $coupon_code = WordpressHelper::generateRandomString(12);
                $is_coupon_exists = EDD::isCouponExists($coupon_code);
                if (is_bool($is_coupon_exists) && !$is_coupon_exists) {
                    $affiliateData['referral_code'] = $coupon_code;
                }
            }

            if (!empty($program_id)) {
                $program = Program::query()->findOrFail($program_id);
                $affiliateData['program_id'] = $program_id;
            } else {
                $program = null;
            }

            $createCoupon = false;

            $affiliateCoupon = AffiliateCoupon::query()->where("affiliate_id = %d and is_primary = %d", [$affiliate->id, 1])->first();

            if ($program && empty($affiliateCoupon) && $status == 'approved') {
                $createCoupon = true;
            }

            if ($createWCAccount && $createWCAccount != 'false' && empty($affiliate->wc_customer_id)) {
                $user_id = !defined('WC_VERSION') ? Affiliate::createWPAccount($member, $affiliate) : apply_filters('rwpa_create_account', $member, $affiliate);
                if (!empty($user_id)) {
                    $wp_user_id = $user_id;
                    $affiliateData['wp_customer_id'] = $wp_user_id;
                }
            } else if (empty($affiliate->wc_customer_id) && email_exists($member->email)) {
                $user = get_user_by('email', $member->email);
                $affiliateData['wp_customer_id'] = $user->ID;
            }

            Affiliate::query()->update($affiliateData, ['id' => $affiliate->id]);

            //refresh the Affiliate
            $affiliate = Affiliate::query()->find($affiliateId);

            $data = [
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'email' => $member->email,
                'referral_code' => $affiliate->referral_code,
                'referral_link' => Affiliate::getReferralCodeURL($affiliate)
            ];

            if ($createCoupon) {
                Affiliate::createCoupon($affiliate, $program);
            } else if ($status != 'approved' && !empty($affiliateCoupon)) {
                //Delete Affiliate Coupon
                AffiliateCoupon::query()->delete([
                    'affiliate_id' => $affiliate->id,
                    'is_primary' => 1
                ]);
                wp_delete_post($affiliateCoupon->woo_coupon_id, true);
            }

            $send_affiliate_approved_email = Settings::get('email_settings.affiliate_emails.affiliate_approved');
            $send_affiliate_rejected_email = Settings::get('email_settings.affiliate_emails.affiliate_rejected');


            if ($status == 'approved' && $send_affiliate_approved_email) {
                $commissionTier = CommissionTier::query()->where("program_id = %d", [$affiliate->program_id])->first();
                $commission = CommissionTier::getCommissionTierInfo($commissionTier);
                $data['commission_type'] = $commission['type_formatted'] ?? '';
                $data['commission_value'] = $commission['commission_value'];
                do_action('rwpa_send_affiliate_approved_email', $data);
            } else if ($status == 'rejected' && $send_affiliate_rejected_email) {
                do_action('rwpa_send_affiliate_rejected_email', $data);
            }

            Database::commit();

            return Response::success([
                'message' => __('Affiliate Status Updated Successfully', 'relay-affiliate-marketing')
            ]);
        } catch (ModelNotFoundException $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Updating Affiliate Status', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        } catch (Exception $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Updating Affiliate Status', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function sales(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');
            $search = $request->get('search');
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $rwpCurrency = Functions::getSelectedCurrency();

            $affiliate = Affiliate::findOrFail($affiliateId);

            $program = Program::query()->find($affiliate->program_id);

            $member = Member::query()->findOrFail($affiliate->member_id);

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $programTable = Program::getTableName();

            $query = Order::query()
                ->select("$orderTable.id as order_id, $orderTable.currency as currency, $orderTable.order_status as order_status, $orderTable.woo_order_id as woo_order_id, $customerTable.id as customer_id, $memberTable.first_name as customer_first_name, 
                    $memberTable.last_name as customer_last_name, $memberTable.email as customer_email, $orderTable.recurring_parent_id as recurring_parent_id, $orderTable.total_amount total_amount, $orderTable.created_at as ordered_at, $programTable.title as program_name, $programTable.id as program_id")
                ->leftJoin($customerTable, "$customerTable.id = $orderTable.customer_id")
                ->leftJoin($memberTable, "$memberTable.id = $customerTable.member_id")
                ->leftJoin($programTable, "$orderTable.program_id = $programTable.id")
                ->where("$orderTable.affiliate_id = %d", [$affiliateId])
                ->when($search, function ($query) use ($memberTable, $orderTable, $search) {
                    return $query->nameLike("$memberTable.first_name", "$memberTable.last_name", $search)
                        ->orderIdLike("$orderTable.woo_order_id", $search, false);
                })
                ->where("$orderTable.currency = %s", [$rwpCurrency])
                ->orderBy("$orderTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return AffiliateSalesCollection::collection([$data, $affiliate, $member, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function commissions(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');
            $search = $request->get('search');
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $rwpCurrency = Functions::getSelectedCurrency();

            $affiliate = Affiliate::findOrFail($affiliateId);

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $commissionEarningTable = CommissionEarning::getTableName();

            $query = CommissionEarning::query()
                ->select("$commissionEarningTable.id as commission_id, $commissionEarningTable.type as commission_type,  $commissionEarningTable.commission_currency as currency, $orderTable.id as order_id, $orderTable.woo_order_id as woo_order_id, $customerTable.id as customer_id, $memberTable.first_name as customer_first_name, 
                    $memberTable.last_name as customer_last_name, {$memberTable}.email as customer_email, $orderTable.total_amount as order_amount, $commissionEarningTable.commission_amount as commission_amount, $commissionEarningTable.status as commission_status, $commissionEarningTable.date_created as commission_created_at, $orderTable.ordered_at as ordered_at, $orderTable.recurring_parent_id as recurring_parent_id")
                ->leftJoin($orderTable, "$orderTable.id = $commissionEarningTable.order_id")
                ->leftJoin($customerTable, "$customerTable.id = $orderTable.customer_id")
                ->leftJoin($memberTable, "$memberTable.id = $customerTable.member_id")
                ->where("$commissionEarningTable.affiliate_id = %d", [$affiliate->id])
                ->when($search, function (Database $query) use ($memberTable, $orderTable, $search) {
                    $query->nameLike("$memberTable.first_name", "$memberTable.last_name", $search)
                        ->orderIdLike("$orderTable.woo_order_id", $search, false);
                })
                ->where("{$commissionEarningTable}.commission_currency = %s", [$rwpCurrency])
                ->where("{$commissionEarningTable}.show_commission = %d", [1])
                ->groupBy("{$commissionEarningTable}.id")
                ->orderBy("$commissionEarningTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return AffiliateCommissionCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
    //edd works
    public static function payouts(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');
            $search = $request->get('search');
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;

            $rwpCurrency = Functions::getSelectedCurrency();

            $affiliate = Affiliate::findOrFail($affiliateId);
            $payoutTable = Payout::getTableName();

            $query = Payout::query()
                ->select("amount, created_at, currency, affiliate_note, admin_note, payment_source, status, revert_reason, deleted_at")
                ->where("affiliate_id = %d", [$affiliateId])
                ->where("currency = %s", [$rwpCurrency])
                ->orderBy("$payoutTable.id", "DESC");
            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();
            return AffiliatePayoutCollection::collection([$data, $affiliate, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function transactions(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 10);
            $currentPage = $request->get('current_page', 1);
            $rwpCurrency = Functions::getSelectedCurrency();

            $affiliate = Affiliate::query()->findOrFail($affiliateId);
            $transactionTable = Transaction::getTableName();

            $query = Transaction::query()
                ->select("type, currency, amount, system_note, created_at")
                ->when(!empty($search), function (Database $query) use ($search, $transactionTable) {
                    return $query->where(" {$transactionTable}.system_note LIKE %s ", ["%{$search}%"]);
                })
                ->where("currency =  %s", [$rwpCurrency])
                ->where("affiliate_id = %d", [$affiliateId])
                ->orderBy("$transactionTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return AffiliateTransactionCollection::collection([$data, $affiliate, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function coupons(Request $request)
    {
        try {
            $affiliateId = $request->get('affiliate_id');

            $affiliate = Affiliate::query()->findOrFail($affiliateId);

            $coupons = AffiliateCoupon::query()->where("affiliate_id = %d", [$affiliateId])
                ->where("is_primary = %d", [1])
                ->get();

            return AffiliateCouponCollection::collection([$coupons, $affiliate]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
