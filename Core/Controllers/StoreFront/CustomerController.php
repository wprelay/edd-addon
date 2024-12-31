<?php

namespace RelayWp\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\PluginHelper;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Visit;

class CustomerController
{

    public static function captureVisit(Request $request)
    {
        try {
            $referralCode = $request->get('referral_code');
            $landingUrl = $request->get('landing_url');

            $affiliate = Affiliate::query()
                ->where("referral_code = %s", [$referralCode])
                ->first();

            Visit::query()->create([
                'affiliate_id' => $affiliate->id,
                'referral_code' => $referralCode,
                'ip_address' => Request::server('REMOTE_ADDR'),
                'landing_url' => $landingUrl
            ]);

            return Response::success([
                'message' => 'Customer Visit Captured'
            ]);
        } catch (\Exception | \Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
