<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Exception\ModelNotFoundException;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Resources\Program\ProgramCollection;
use RelayWp\Affiliate\Core\Resources\Program\ProgramCommissionTierCollection;
use RelayWp\Affiliate\Core\Resources\Program\ProgramResource;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\CommissionTier;
use RelayWp\Affiliate\Core\Models\CustomerDiscount;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\ValidationRequest\Program\ProgramIDRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Program\ProgramRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Program\ProgramStatusRequest;

class ProgramController
{
    public static function overview(Request $request)
    {
        try {
            $overview = Program::query()
                ->select("
                COUNT(*) as all_count, 
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count, 
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archive_count
                ")
                ->first();

            return Response::success([
                'all_count' => (int)$overview->all_count ?? 0,
                'active_count' => (int)$overview->active_count ?? 0,
                'draft_count' => (int)$overview->draft_count ?? 0,
                'archive_count' => (int)$overview->archive_count ?? 0,
            ]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }


    public function index(Request $request)
    {
        try {
            //code
            $programTableName = Program::getTableName();
            $commissionTierTableName = CommissionTier::getTableName();
            $orderTableName = Order::getTableName();
            $affiliateTable = Affiliate::getTableName();
            $commissionEarningTable = CommissionEarning::getTableName();
            $customerDiscountTable = CustomerDiscount::getTableName();
            $commissionTierTable = CommissionTier::getTableName();
            $rwpCurrency = Functions::getSelectedCurrency();

            $search = $request->get('search');
            $status = $request->get('status');
            $perPage = $request->get('per_page') ?? 10;
            $currentPage = $request->get('current_page') ?? 1;
            $commissionType = $request->get('commission_type') ?? 'all';


            $successfulOrderStatus = Settings::get('affiliate_settings.successful_order_status');
            $successfulOrderStatus = implode("','", $successfulOrderStatus);
            $query = Program::query()->select(
                "
            (select sum(commission_amount) from $commissionEarningTable where {$commissionEarningTable}.program_id = $programTableName.id and {$commissionEarningTable}.status = 'approved' and {$commissionEarningTable}.commission_currency = '$rwpCurrency')  as affiliate_commissions,
            COALESCE(SUM($orderTableName.total_amount),0) as total_revenue, 
            (select count({$affiliateTable}.id) from $affiliateTable where {$affiliateTable}.program_id = $programTableName.id) as total_affiliates,
                                    {$programTableName}.id as program_id, 
                                    {$programTableName}.title as title, 
                                    {$programTableName}.description as description, 
                                    {$programTableName}.status as status, 
                                    {$programTableName}.start_date as start_date, 
                                    {$programTableName}.end_date as end_date, 
                                    {$customerDiscountTable}.discount_type as discount_type, 
                                    {$customerDiscountTable}.coupon_amount as coupon_amount, 
                                    {$commissionTierTable}.rate_type as commission_type, 
                                    {$commissionTierTable}.base_type as base_type, 
                                    {$programTableName}.created_at as program_created_at, 
                                    {$programTableName}.updated_at as program_updated_at "
            )
                ->leftJoin($affiliateTable, "{$affiliateTable}.program_id = {$programTableName}.id")
                ->leftJoin($commissionTierTable, "{$commissionTierTable}.program_id = {$programTableName}.id")
                ->leftJoin($customerDiscountTable, "{$customerDiscountTable}.program_id = {$programTableName}.id")
                ->leftJoin($orderTableName, "{$orderTableName}.affiliate_id = {$affiliateTable}.id AND {$orderTableName}.currency = '{$rwpCurrency}' AND {$orderTableName}.order_status IN ('" . $successfulOrderStatus . "')")
                ->when(!empty($status), function (Database $query) use ($status, $programTableName) {
                    return $query->where("{$programTableName}.status = %s", [$status]);
                })
                ->when(!empty($search), function ($query) use ($search, $programTableName) {
                    return $query->where("({$programTableName}.title LIKE %s OR {$programTableName}.description LIKE %s)", ["%$search%", "%$search%"]);
                })
                ->when($commissionType != 'all', function ($query) use ($commissionType, $commissionTierTable) {
                    return $query->where("{$commissionTierTable}.rate_type = %s", [$commissionType]);
                })
                ->groupBy("{$programTableName}.id")
                ->groupBy("{$customerDiscountTable}.id")
                ->orderBy("{$programTableName}.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();


            return ProgramCollection::collection([$data, $rwpCurrency, $totalCount, $perPage, $currentPage]);
        } catch (\Exception | \Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function commissionTiersDetails(Request $request)
    {
        try {
            //code
            $programTableName = Program::getTableName();
            $commissionTierTableName = CommissionTier::getTableName();

            $perPage = $request->get('per_page');
            $currentPage = $request->get('current_page');

            $search = $request->get('search');
            $perPage = $perPage ? $perPage : 10;
            $currentPage = $currentPage ? $currentPage : 1;

            $query = Program::query()->select(
                "
                                    {$programTableName}.id as program_id, 
                                    {$programTableName}.description as description, 
                                    {$programTableName}.title as title, 
                                    {$programTableName}.start_date as start_date, 
                                    {$programTableName}.end_date as end_date, 
                                    {$programTableName}.status as status, 
                                    {$commissionTierTableName}.id as commission_tier_id, 
                                    {$commissionTierTableName}.rate_type as rate_type, 
                                    {$commissionTierTableName}.rate_json as rate_json, 
                                    {$programTableName}.created_at as program_created_at, 
                                    {$programTableName}.updated_at as program_updated_at "
            )
                ->leftJoin($commissionTierTableName, "{$programTableName}.id = {$commissionTierTableName}.program_id")
                ->where("{$programTableName}.status = %s", ['active'])
                ->groupBy("{$programTableName}.id")
                ->groupBy("{$commissionTierTableName}.id")
                ->orderBy("{$programTableName}.created_at", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();


            return ProgramCommissionTierCollection::collection([$data, $totalCount, $perPage, $currentPage]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function create(Request $request)
    {
        $request->validate(new ProgramRequest());

        Database::beginTransaction();

        try {
            $programData = [];

            $programData['title'] = $request->get('title');
            $programData['description'] = $request->get('description', '');
            $programData['status'] = Functions::getBoolValue($request->get('is_active')) ? 'active' : 'draft';
            $programData['auto_approve'] = Functions::getBoolValue($request->get('auto_approve'));
            $start_date = Functions::formatDate($request->get('start_date', null));
            $programData['start_date'] = empty($start_date) ? null : Functions::wpToUTCTime($start_date);
            $end_date = Functions::formatDate($request->get('end_date', null));;
            $programData['end_date'] = empty($end_date) ? null : Functions::wpToUTCTime($end_date);
            $programData['created_at'] = Functions::currentUTCTime();
            $programData['updated_at'] = Functions::currentUTCTime();

            $isInserted = Program::query()->create($programData);

            $programId = Program::query()->lastInsertedId();

            $commissionTierData = [];


            $commissionTierData['base_type'] = $request->get('commission_type');

            if ($commissionTierData['base_type'] == 'advanced') {
                $rate_type = 'tier_based';
            } else if ($commissionTierData['base_type'] == 'rule_based') {
                $rate_type = 'rule_based';
            } else {
                $rate_type = $request->get('commission_sub_type');
            }

            $commissionTierData['rate_type'] = $rate_type;
            $commissionTierData['rate_json'] = CommissionTier::rateJsonDataFromRequest($commissionTierData['rate_type'], $request);

            $commissionTierData = apply_filters('rwpa_get_recursive_data_to_store', $commissionTierData, $request);

            $commissionTierData['program_id'] = $programId;
            $commissionTierData['created_at'] = Functions::currentUTCTime();
            $commissionTierData['updated_at'] = Functions::currentUTCTime();
            $commissionTierInserted = CommissionTier::query()->create($commissionTierData);

            $options = $request->get('customer_discount_options');

            $discountType = $request->get('customer_discount_type');

            CustomerDiscount::query()->create(array_merge(CustomerDiscount::getCustomerDiscountData($programId, $discountType, $options), [
                'created_at' => Functions::currentUTCTime(),
                'updated_at' => Functions::currentUTCTime(),
            ]));

            Database::commit();
            return Response::success([
                'message' => __('Program Created Successfully', 'relay-affiliate-marketing'),
                'program_id' => $programId
            ]);
        } catch (Exception | Error $exception) {
            Database::rollBack();

            PluginHelper::logError('Error Occurred While Creating Program', [__CLASS__, __FUNCTION__], $exception);

            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function update(Request $request)
    {
        Database::beginTransaction();
        try {
            $request->validate(new ProgramRequest());

            $programId = $request->get('program_id');

            $program = Program::query()->findOrFail($programId);

            if (!$program) {
                Response::error(['message' => __('Program Not Found', 'relay-affiliate-marketing')]);
            }

            $programData = [];

            $programData['title'] = $request->get('title');
            $programData['description'] = $request->get('description', '');
            $programData['status'] = Functions::getBoolValue($request->get('is_active')) ? 'active' : 'draft';
            $programData['auto_approve'] = Functions::getBoolValue($request->get('auto_approve'));

            $start_date = Functions::formatDate($request->get('start_date', null));
            $programData['start_date'] = empty($start_date) ? null : Functions::wpToUTCTime($start_date);
            $end_date = Functions::formatDate($request->get('end_date', null));;
            $programData['end_date'] = empty($end_date) ? null : Functions::wpToUTCTime($end_date);

            $programData['updated_at'] = Functions::currentUTCTime();

            $isUpdated = Program::query()->update($programData, ['id' => $programId]);

            $commissionTier = CommissionTier::query()->where("program_id = {$programId}")->firstOrFail();

            $commissionTierData = [];

            $commissionTierData['base_type'] = $request->get('commission_type');

            if ($commissionTierData['base_type'] == 'advanced') {
                $rate_type = 'tier_based';
            } else if ($commissionTierData['base_type'] == 'rule_based') {
                $rate_type = 'rule_based';
            } else {
                $rate_type = $request->get('commission_sub_type');
            }

            $commissionTierData['rate_type'] = $rate_type;

            $commissionTierData['rate_json'] = CommissionTier::rateJsonDataFromRequest($commissionTierData['rate_type'], $request);

            $commissionTierData = apply_filters('rwpa_get_recursive_data_to_store', $commissionTierData, $request);

            $commissionTierData['program_id'] = $programId;
            $commissionTierData['updated_at'] = Functions::currentUTCTime();


            $commissionTierUpdated = CommissionTier::query()->update($commissionTierData, ['id' => $commissionTier->id]);

            $customerDiscount = CustomerDiscount::query()->where("program_id = {$programId}")->firstOrFail();

            $options = $request->get('customer_discount_options');

            $discountType = $request->get('customer_discount_type');

            CustomerDiscount::query()->update(array_merge(CustomerDiscount::getCustomerDiscountData($programId, $discountType, $options), [
                'update_coupons' => 1,
                'updated_at' => Functions::currentUTCTime(),
            ]), [
                'id' => $customerDiscount->id,
            ]);

            Database::commit();

            if (\ActionScheduler::is_initialized()) {
                $id = as_schedule_single_action(strtotime('now'), 'rwpa_update_affiliate_coupons');
            }

            return Response::success([
                'message' => __('Program Updated Successfully', 'relay-affiliate-marketing')
            ]);
        } catch (ModelNotFoundException $exception) {
            Database::rollBack();
            PluginHelper::logError('Model Not Found', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        } catch (Exception | Error $exception) {

            Database::rollBack();
            PluginHelper::logError('Error Occurred While Updating Program', [__CLASS__, __FUNCTION__], $exception);

            Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function updateStatus(Request $request)
    {
        $request->validate(new ProgramStatusRequest());

        Database::beginTransaction();

        try {
            $programId = $request->get('program_id');

            $program = Program::query()->findOrFail($programId);

            $status = $request->get('status');

            Program::query()->update(['status' => $status, 'updated_at' => Functions::currentUTCTime()], ['id' => $program->id]);

            Database::commit();

            Response::success(['message' => __('Program Status Updated Successfully', 'relay-affiliate-marketing')]);
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Creating Program', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function fetchProgram(Request $request)
    {
        $request->validate(new ProgramIDRequest());
        try {
            $programId = $request->get('program_id');

            $program = Program::query()->findOrFail($programId);

            $commissionTier = CommissionTier::query()->where("program_id = {$programId}")->firstOrFail();

            $customerDiscount = CustomerDiscount::query()->where("program_id = {$programId}")->firstOrFail();

            return ProgramResource::resource([$program, $commissionTier, $customerDiscount]);
        } catch (ModelNotFoundException $exception) {
            PluginHelper::logError('Model Not Found', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Getting Program', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function destroy(Request $request)
    {
        $request->validate(new ProgramIDRequest());

        Database::beginTransaction();

        try {
            $programId = $request->get('program_id');

            $program = Program::query()->findOrFail($programId);

            Program::query()->update(['is_archived' => 1, 'updated_at' => Functions::currentUTCTime()], ['id' => $program->id]);

            Database::commit();

            Response::success(['message' => __('Program Archived Successfully', 'relay-affiliate-marketing')]);
        } catch (ModelNotFoundException $exception) {
            Database::rollBack();
            PluginHelper::logError('Model Not Found', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        } catch (Exception | Error $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Archiving Program', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function getProgramListForSelect(Request $request)
    {
        $search = $request->get('search');

        try {
            $programs = Program::query()->select("id as value , title as label")
                ->where("status = %s", ['active'])
                ->where("title like %s", ["%{$search}%"])
                ->limit(5)
                ->get();

            Response::success([
                'programs' => $programs
            ]);
        } catch (\Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
