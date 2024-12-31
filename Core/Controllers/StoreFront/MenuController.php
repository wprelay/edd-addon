<?php

namespace RelayWp\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\StoreFront\AffiliateUpdateRequest;

class MenuController
{

    public static function updateAffiliate(Request $request)
    {
        $request->validate(new AffiliateUpdateRequest());

        try {
            $affiliateId = $request->get('affiliate_id');


            $affiliate = Affiliate::query()->findOrFail($affiliateId);
            $member = Member::query()->findOrFail($affiliate->member_id);

            //create member
            $memberData = [];

            $memberData['first_name'] = $request->get('first_name');
            $memberData['last_name'] = $request->get('last_name');
            $memberData['email'] = $request->get('email');
            $memberData['updated_at'] = Functions::currentUTCTime();

            Member::query()->update($memberData, ['id' => $member->id]);

            $socialLinks = [
                'instagram_url' => $request->get('instagram_url'),
                'twitter_url' => $request->get('twitter_url'),
                'website_url' => $request->get('website_url'),
                'tiktok_url' => $request->get('tiktok_url'),
                'youtube_url' => $request->get('youtube_url'),
                'facebook_url' => $request->get('facebook_url'),
                'linkedin_url' => $request->get('linkedin_url')
            ];

            $affiliateData['social_links'] = Functions::arrayToJson($socialLinks);

            $shippingAddress = [
                'address' => $request->get('address'),
                'city' => $request->get('city'),
                'zip_code' => $request->get('zip_code'),
                'country' => $request->get('country'),
                'state' => $request->get('state'),
            ];

            $affiliateData['shipping_address'] = Functions::arrayToJson($shippingAddress);

            $affiliateData['phone_number'] = $request->get('phone_number');

            $affiliateData['payment_email'] = $request->get('billing_email');

            $affiliateData['updated_at'] = Functions::currentUTCTime();

            Affiliate::query()->update($affiliateData, ['id' => $affiliate->id]);

            Database::commit();

            return Response::success([
                'message' => 'Affiliate Updated Successfully'
            ]);
        } catch (\Exception | \Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
