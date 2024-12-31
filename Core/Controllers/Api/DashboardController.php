<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\Core\Resources\Dashboard\CommissionCollection;
use RelayWp\Affiliate\Core\Resources\Dashboard\SalesCollection;
use RelayWp\Affiliate\Core\Resources\Dashboard\VisitsCollection;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\Customer;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Visit;
use WC_Countries;
use WpOrg\Requests\Exception;

class DashboardController
{
    public function playground(Request $request)
    {
        $relayWpOrder = Order::query()->find(1105);
    }

    public function getBenchMarkDetails(Request $request)
    {
        try {
            $rwpCurrency = Functions::getSelectedCurrency();
            $totalAffiliatesCount = Affiliate::select("count(*) as count")->where("status = %s", ['approved'])->first();
            $pendingAffiliatesCount = Affiliate::select("count(*) as count")
                ->where("status = %s", ['pending'])
                ->first();

            $successfulOrderStatus = Settings::get('affiliate_settings.successful_order_status');
            $successfulOrderStatus = implode("','", $successfulOrderStatus);
            $totalSales = Order::query()
                ->select("sum(total_amount) as total_sales")
                ->where("currency = %s", [$rwpCurrency])
                ->where("order_status IN ('" . $successfulOrderStatus . "')")
                ->first();

            $payout = Payout::query()->select("sum(amount) as total_paid")
                ->where("currency = %s", [$rwpCurrency])
                ->where("deleted_at is NULL")
                ->first();

            Response::success([
                'pending_affiliate' => [
                    'count' => $pendingAffiliatesCount->count,
                    'label' => 'Pending Affiliate'
                ],
                'total_sales' => [
                    'currency' => WC::getWcCurrencySymbol($rwpCurrency),
                    'formatted_amount' => Functions::formatAmount($totalSales->total_sales, $rwpCurrency),
                    'amount' => $totalSales->total_sales,
                    'label' => 'Total Sales',
                ],
                'total_affiliate' => [
                    'count' => $totalAffiliatesCount->count,
                    'label' => 'Total Affiliate'
                ],
                'total_payouts' => [
                    'currency' => WC::getWcCurrencySymbol($rwpCurrency),
                    'formatted_amount' => Functions::formatAmount($payout->total_paid, $rwpCurrency),
                    'amount' => $payout->total_paid,
                    'label' => 'Total Payouts',
                ],
            ]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getOrders(Request $request)
    {
        try {
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $search = $request->get('search', null);
            $orderStatuses = $request->get('order_status', []);

            $rwpCurrency = Functions::getSelectedCurrency();

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $affiliateTable = Affiliate::getTableName();

            $query = Order::query()
                ->select("$orderTable.id as order_id, $orderTable.currency as currency, $orderTable.order_status as order_status, $orderTable.medium as medium, $orderTable.woo_order_id as woo_order_id,  $customerTable.id as customer_id, $memberTable.first_name as customer_first_name, $affiliateTable.id as affiliate_id,
                    $memberTable.id as member_id, $memberTable.last_name as customer_last_name, $orderTable.total_amount total_amount, $orderTable.ordered_at as ordered_at, $orderTable.recurring_parent_id as recurring_parent_id, affiliates.first_name as affiliate_first_name, affiliates.last_name as affiliate_last_name")
                ->leftJoin($affiliateTable, "$affiliateTable.id = $orderTable.affiliate_id")
                ->leftJoin($customerTable, "$customerTable.id = $orderTable.customer_id")
                ->leftJoin($memberTable, "$memberTable.id = $customerTable.member_id")
                ->leftJoin("$memberTable as affiliates", "affiliates.id = {$affiliateTable}.member_id")
                ->when(!empty($orderStatuses), function (Database $query) use ($orderTable, $orderStatuses) {
                    return $query->where("{$orderTable}.order_status in ('" . implode("','", $orderStatuses) . "')");
                })
                ->when(!empty($search), function (Database $query) use ($search, $memberTable, $orderTable) {
                    return $query->nameLike("{$memberTable}.first_name", "{$memberTable}.last_name", $search)
                        ->nameLike("affiliates.first_name", "affiliates.last_name", $search, false)
                        ->orderIdLike("{$orderTable}.woo_order_id", $search, false);
                })
                ->where("$orderTable.currency = %s", [$rwpCurrency])
                ->groupBy("{$orderTable}.id")
                ->orderBy("$orderTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return SalesCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (\Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getCommissions(Request $request)
    {
        try {
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $search = $request->get('search', null);
            $commissionStatuses = $request->get('commission_status', []);
            $rwpCurrency = Functions::getSelectedCurrency();

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $affiliateTable = Affiliate::getTableName();
            $commissionEarningTable = CommissionEarning::getTableName();

            $query = CommissionEarning::query()
                ->select("$orderTable.id as order_id, $commissionEarningTable.commission_currency as currency, $commissionEarningTable.type as commission_type,  $orderTable.woo_order_id as woo_order_id, $orderTable.total_amount as order_amount, $orderTable.recurring_parent_id as recurring_parent_id, $affiliateTable.id as affiliate_id,
                    affiliate_member.first_name as affiliate_first_name, affiliate_member.last_name as affiliate_last_name, $commissionEarningTable.commission_amount, $commissionEarningTable.status as commission_status")
                ->leftJoin($orderTable, "$orderTable.id = $commissionEarningTable.order_id")
                ->leftJoin($affiliateTable, "$affiliateTable.id = $orderTable.affiliate_id")
                ->leftJoin("$memberTable as affiliate_member", "affiliate_member.id = {$affiliateTable}.member_id")
                ->when(!empty($commissionStatuses), function (Database $query) use ($commissionEarningTable, $commissionStatuses) {
                    return $query->where("{$commissionEarningTable}.status in ('" . implode("','", $commissionStatuses) . "')");
                })
                ->when(!empty($search), function (Database $query) use ($search, $affiliateTable, $orderTable) {
                    return $query->nameLike("affiliate_member.first_name", "affiliate_member.last_name", $search)
                        ->orderIdLike("{$orderTable}.woo_order_id", $search, false);
                })
                ->where("$commissionEarningTable.commission_currency = %s", [$rwpCurrency])
                ->where("$commissionEarningTable.show_commission = %d", [1])
                ->groupBy("$commissionEarningTable.id")
                ->orderBy("$commissionEarningTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return CommissionCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (\Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getVisits(Request $request)
    {
        try {
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $search = $request->get('search', null);

            $visitTable = Visit::getTableName();
            $affiliateTable = Affiliate::getTableName();
            $memberTable = Member::getTableName();

            $query = Visit::query()->select("{$affiliateTable}.id as affiliate_id, {$memberTable}.first_name as affiliate_first_name, {$memberTable}.last_name as affiliate_last_name, {$visitTable}.landing_url as landing_url, {$visitTable}.ip_address as ip_address")
                ->leftJoin($affiliateTable, "{$affiliateTable}.id = {$visitTable}.affiliate_id")
                ->leftJoin($memberTable, "{$affiliateTable}.member_id = {$memberTable}.id")
                ->orderBy("{$visitTable}.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return VisitsCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
