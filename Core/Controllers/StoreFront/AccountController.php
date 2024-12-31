<?php

namespace RelayWp\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Helpers\AssetHelper;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Helpers\WordpressHelper;
use RelayWp\Affiliate\App\Hooks\AssetsActions;
use RelayWp\Affiliate\App\Route;
use RelayWp\Affiliate\Core\Models\CouponPayout;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateCommissionCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliatePayoutCollection;
use RelayWp\Affiliate\Core\Resources\Affiliate\AffiliateSalesCollection;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\AffiliateCoupon;
use RelayWp\Affiliate\Core\Models\CommissionEarning;
use RelayWp\Affiliate\Core\Models\Customer;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Order;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Models\Program;

class AccountController
{
    public static function addAffiliateMenu($menu)
    {
        $user = wp_get_current_user();

        if ($user) {

            $member = Member::query()
                ->where("email = %s", [$user->user_email])
                ->where("type = %s", ['affiliate'])
                ->first();

            if ($member) {
                $affiliate = Affiliate::query()->where('member_id = %d', [$member->id])->first();

                if ($affiliate && $affiliate->status != 'rejected') {
                    $lastIndex = count($menu) - 1;
                    $pluginName = PluginHelper::getPluginName();
                    $menu = array_slice($menu, 0, $lastIndex) + ['relay-affiliate-marketing' => $pluginName] + array_slice($menu, $lastIndex);
                    return $menu;
                }
            } else {
                return $menu;
            }
        }

        return $menu;
    }

    public static function registerAffiliateEndPoint()
    {
        add_rewrite_endpoint('relay-affiliate-marketing', EP_ROOT | EP_PAGES);
    }

    public static function registerAffiliateEndpointContent()
    {
        $pluginSlug = RWPA_PLUGIN_SLUG;
        $menuJsHandle = "{$pluginSlug}-menu";
        $menuPageStyle = "{$pluginSlug}-menu-page-style";
        $resourceUrl = AssetHelper::getResourceURL();
        wp_enqueue_script($menuJsHandle, "{$resourceUrl}/scripts/wprelay-menu.js", array('jquery'), RWPA_VERSION, true);
        wp_enqueue_style($menuPageStyle, "{$resourceUrl}/css/wprelay.css", [], RWPA_VERSION);
        wp_enqueue_style("{$pluginSlug}-styles-font-awesome", "{$resourceUrl}/admin/css/rwp-fonts.css", [], RWPA_VERSION);
        $storeConfig = AssetsActions::getStoreConfigValues();
        wp_localize_script($menuJsHandle, 'rwpa_relay_store', $storeConfig);

        $path = RWPA_PLUGIN_PATH . 'resources/templates/';

        $user = wp_get_current_user();

        $member = Member::query()
            ->where("email = %s", [$user->user_email])
            ->where("type = %s", ['affiliate'])
            ->first();

        if ($member) {
            $affiliate = Affiliate::query()->where('member_id = %d', [$member->id])->first();
        }

        $isNewAffiliate = false;

        if (empty($affiliate)) {
            $isNewAffiliate = true;
        }

        $url = WordpressHelper::getCurrentURL();

        $parsedUrl = wp_parse_url($url);

        $queryParams = [];

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        $section = 'overview';

        if (isset($queryParams['section'])) {
            $section = $queryParams['section'];
        }

        if ($affiliate->status != 'approved') {
            $section = 'settings';
        }

        $selected_currency = get_woocommerce_currency();

        if (isset($queryParams['wprelay-currency'])) {
            $selected_currency = $queryParams['wprelay-currency'];
        }

        $overview_details = self::getOverviewData($affiliate, $selected_currency);

        $fileName = static::getSectionFile($section);
        $data = $section == 'overview' ? $overview_details : static::getData($section, $affiliate, $selected_currency);
        $current_url = WordpressHelper::getCurrentURL();

        $file_path = "{$path}menu-sections/{$fileName}";

        $pagination_data = static::getPaginationData($data);

        if (function_exists('WC')) {
            $countries = WC()->countries->get_countries();
        } else {
            $countries = [];
        }

        $countries = apply_filters('rwpa_wprelay_get_countries_to_show_for_registration', $countries);

        wc_get_template('wprelay-menu.php', [
            'wp_user' => $user,
            'member' => $member,
            'affiliate' => $affiliate,
            'is_new_affiliate' => $isNewAffiliate,
            'file_path' => $file_path,
            'data' => $data,
            'pagination_data' => $pagination_data,
            'menu_nonce' => WordpressHelper::createNonce('menu_nonce'),
            'countries' => $countries,
            'current_url' => $current_url,
            'overview' => $overview_details,
            'primaryColor' => Settings::get('general_settings.color_settings.primary_color'),
            'secondaryColor' => Settings::get('general_settings.color_settings.secondary_color'),
            'currencies' => WC::getCurrencyList(),
            'selected_currency' => $selected_currency,
            'current_active_section' => $section
        ], $path, $path);
    }


    public static function getSectionFile($section)
    {

        $fileName = 'affiliate-detail.php';
        switch ($section) {
            case 'overview':
                $fileName = 'affiliate-overview.php';
                break;

            case 'sales':
                $fileName = 'affiliate-sales.php';
                break;

            case 'commissions':
                $fileName = 'affiliate-commissions.php';
                break;

            case 'payouts':
                $fileName = 'affiliate-payouts.php';
                break;
        }

        return $fileName;
    }

    public static function getData($section, $affiliate, $selected_currency)
    {
        $data = [];
        switch ($section) {
            case 'overview':
                $data = static::getOverviewData($affiliate, $selected_currency);
                break;

            case 'sales':
                $data = static::getSalesData($affiliate, $selected_currency);
                break;

            case 'commissions':
                $data = static::getCommissionsData($affiliate, $selected_currency);
                break;

            case 'payouts':
                $data = static::getPayoutsData($affiliate, $selected_currency);
                break;
        }

        return $data;
    }

    public static function getSalesData($affiliate, $selectedCurrency)
    {
        try {
            $affiliateId = $affiliate->id;

            $affiliate = Affiliate::find($affiliateId);

            $member = Member::query()->find($affiliate->member_id);

            $rwpCurrency = $selectedCurrency ? $selectedCurrency : get_woocommerce_currency();

            [$perPage, $currentPage] = static::getPaginationQueryParams();

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $programTable = Program::getTableName();

            $query = Order::query()
                ->select("$orderTable.id as order_id, $orderTable.currency as currency, $orderTable.woo_order_id as woo_order_id, $orderTable.order_status as order_status, $orderTable.medium as medium, $customerTable.id as customer_id, $memberTable.first_name as customer_first_name, 
                    $memberTable.last_name as customer_last_name, $memberTable.email as customer_email, $orderTable.recurring_parent_id as recurring_parent_id, $orderTable.total_amount total_amount, $orderTable.created_at as ordered_at, $programTable.title as program_name, $programTable.id as program_id")
                ->leftJoin($customerTable, "$customerTable.id = $orderTable.customer_id")
                ->leftJoin($memberTable, "$memberTable.id = $customerTable.member_id")
                ->leftJoin($programTable, "$orderTable.program_id = $programTable.id")
                ->where("$orderTable.affiliate_id = %d", [$affiliateId])
                ->where("$orderTable.currency = %s", [$rwpCurrency])
                ->orderBy("$orderTable.id", "DESC");

            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            $data = AffiliateSalesCollection::collection([$data, $affiliate, $member, $totalCount, $perPage, $currentPage], false);

            return $data;
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return [];
        }
    }

    public static function getCommissionsData($affiliate, $selectedCurrency)
    {
        try {
            $affiliateId = $affiliate->id;

            $rwpCurrency = $selectedCurrency ? $selectedCurrency : get_woocommerce_currency();

            $affiliate = Affiliate::find($affiliateId);

            $orderTable = Order::getTableName();
            $customerTable = Customer::getTableName();
            $memberTable = Member::getTableName();
            $commissionEarningTable = CommissionEarning::getTableName();

            [$perPage, $currentPage] = static::getPaginationQueryParams();

            $query = CommissionEarning::query()
                ->select("$commissionEarningTable.id as commission_id, $commissionEarningTable.commission_currency as currency, $commissionEarningTable.type as commission_type, $orderTable.id as order_id, $orderTable.woo_order_id as woo_order_id, $customerTable.id as customer_id, $memberTable.first_name as customer_first_name, 
                    $memberTable.last_name as customer_last_name, {$memberTable}.email as customer_email, $orderTable.total_amount as order_amount, $orderTable.recurring_parent_id as recurring_parent_id, $commissionEarningTable.commission_amount as commission_amount, $commissionEarningTable.status as commission_status, $commissionEarningTable.date_created as commission_created_at, $orderTable.ordered_at as ordered_at")
                ->leftJoin($orderTable, "$orderTable.id = $commissionEarningTable.order_id")
                ->leftJoin($customerTable, "$customerTable.id = $orderTable.customer_id")
                ->leftJoin($memberTable, "$memberTable.id = $customerTable.member_id")
                ->where("$commissionEarningTable.affiliate_id = %d", [$affiliate->id])
                ->where("$commissionEarningTable.commission_currency = %s", [$rwpCurrency])
                ->where("$commissionEarningTable.show_commission = %d", [1])
                ->groupBy("{$commissionEarningTable}.id")
                ->orderBy("$commissionEarningTable.id", "DESC");


            $totalCount = $query->count();


            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return AffiliateCommissionCollection::collection([$data, $totalCount, $perPage, $currentPage], false);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return [];
        }
    }

    public static function getPayoutsData($affiliate, $selectedCurrency)
    {
        try {
            $affiliateId = $affiliate->id;
            [$perPage, $currentPage] = static::getPaginationQueryParams();
            $rwpCurrency = $selectedCurrency ? $selectedCurrency : get_woocommerce_currency();

            $affiliate = Affiliate::find($affiliateId);
            $payoutTable = Payout::getTableName();
            $couponPayoutTable = CouponPayout::getTableName();

            $query = Payout::query()
                ->select("amount, currency, {$payoutTable}.created_at as created_at, affiliate_note, admin_note, payment_source, status, revert_reason, {$payoutTable}.deleted_at as deleted_at,
                    {$couponPayoutTable}.coupon_code
                    ")
                ->leftJoin("{$couponPayoutTable}", "{$couponPayoutTable}.payout_id = {$payoutTable}.id")
                ->where("affiliate_id = %d", [$affiliateId])
                ->where("currency = %s", [$rwpCurrency])
                ->orderBy("$payoutTable.id", "DESC");

            error_log($query->toSql());
            $totalCount = $query->count();

            $data = $query
                ->limit($perPage)
                ->offset(($currentPage - 1) * $perPage)
                ->get();

            return AffiliatePayoutCollection::collection([$data, $affiliate, $totalCount, $perPage, $currentPage], false);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return [];
        }
    }

    public static function getOverviewData($affiliate, $selectedCurrency)
    {

        $rwpCurrency = $selectedCurrency ? $selectedCurrency : get_woocommerce_currency();
        $payout = Payout::query()->select('sum(amount) as total_paid, currency')
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("status NOT IN (%s, %s)", ['failed', 'pending'])
            ->where("currency = %s", [$rwpCurrency])
            ->where("deleted_at is null")
            ->first();

        $commission = CommissionEarning::query()->select("
                            SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as earned_commission,
                            commission_currency as currency
                            ")
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("commission_currency = %s", [$rwpCurrency])
            ->first();

        $currency = WC::getWcCurrencySymbol();

        $affiliate_coupons = AffiliateCoupon::query()
            ->where("affiliate_id = %d", [$affiliate->id])
            ->where("deleted_at is null")
            ->get();

        $program = Program::query()->find($affiliate->program_id);

        $overview = [
            'total_paid' => $payout->total_paid,
            'total_paid_formatted_amount' => Functions::formatAmount($payout->total_paid, $rwpCurrency),
            'total_earnings' => $commission->earned_commission ?? 0 . ' ',
            'total_earnings_formatted_amount' => Functions::formatAmount($commission->earned_commission ?? 0, $rwpCurrency),
            'commission_balance' => $commissionBalance = $commission->earned_commission - $payout->total_paid,
            'commission_balance_formatted_amount' => Functions::formatAmount($commissionBalance, $rwpCurrency),
            'affiliate_coupons' => $affiliate_coupons ?? [],
            'program' => $program ?? new \stdClass(),
        ];

        return $overview;
    }

    public static function getPaginationData($data)
    {
        if (!isset($data['total']) || !isset($data['per_page']) || !isset($data['current_page'])) {
            return [];
        }

        $total_pages = $data['total_pages'];
        $current_page = $data['current_page'];
        $per_page = $data['per_page'];
        $total = $data['total'];

        $pages = [];

        foreach (range(1, $total_pages) as $index) {
            $link = static::addQueryParamInCurrentURL([
                'current_page' => $index,
                'per_page' => $per_page
            ]);
            $pages[] = ['index' => $index, 'link' => $link];
        }

        return [
            'show_pagination' => $per_page < $total,
            'pages' => $pages,
            'total_pages' => $total_pages,
            'total' => $total,
            'current_page' => $current_page,
            'per_page' => $data['per_page'],
            'previous_page' => ($current_page - 1) ? [
                'index' => $current_page - 1,
                'link' => static::addQueryParamInCurrentURL([
                    'current_page' => $current_page - 1,
                    'per_page' => $per_page
                ])
            ] : null,
            'next_page' => ($current_page) != $total_pages ? [
                'index' => $current_page + 1,
                'link' => static::addQueryParamInCurrentURL([
                    'current_page' => $current_page + 1,
                    'per_page' => $per_page
                ])
            ] : null,
        ];
    }

    public static function addQueryParamInCurrentURL($array)
    {

        $url = WordpressHelper::getCurrentURL();

        $parsedUrl = wp_parse_url($url);

        $queryParams = [];

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            $queryParams = array_merge($queryParams, $array);
        }

        // Rebuild the query string
        return "{$parsedUrl['path']}?" . http_build_query($queryParams);
    }

    private static function getPaginationQueryParams()
    {
        $request = Route::getRequestObject();

        $perPage = $request->get('per_page', 5) ?? 5;

        $currentPage = $request->get('current_page', 1) ?? 1;

        return [$perPage, $currentPage];
    }
}
