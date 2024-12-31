<?php

namespace RelayWp\Affiliate\Core\Controllers\StoreFront;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Services\Database;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\Models\Affiliate;
use RelayWp\Affiliate\Core\Models\Member;
use RelayWp\Affiliate\Core\Models\Program;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateRegisterRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Affiliate\AffiliateRegisterRequestForSpecificProgram;

class RegisterController
{
    public static function newAffiliateRequest(Request $request)
    {
        PluginHelper::verifyGoogleRecaptcha($request);

        $request->validate(new AffiliateRegisterRequest());

        Database::beginTransaction();

        try {
            //create member
            $memberData = [];

            $memberData['first_name'] = $request->get('first_name');
            $memberData['last_name'] = $request->get('last_name');
            $memberData['email'] = $email = $request->get('email');
            $memberData['type'] = $type = 'affiliate';
            $memberData['created_at'] = Functions::currentUTCTime();
            $memberData['updated_at'] = Functions::currentUTCTime();

            $isInserted = Member::query()->create($memberData);

            if (!$isInserted) {
                Response::error(PluginHelper::serverErrorMessage());
            }

            $affiliateData = [];

            $memberId = Member::query()->lastInsertedId();

            $affiliateData['status'] = 'pending';
            $affiliateData['member_id'] = $memberId;
            $affiliateData['phone_number'] = $request->get('phone_number');
            $shippingDetails = [
                'address' => $request->get('address'),
                'city' => $request->get('city'),
                'zip_code' => $request->get('zip_code'),
                'state' => $request->get('state'),
                'country' => $request->get('country'),
            ];

            $socialLinks = [
                'facebook_url' => $request->get('facebook_url'),
                'tiktok_url' => $request->get('tiktok_url'),
                'youtube_url' => $request->get('youtube_url'),
                'twitter_url' => $request->get('twitter_url'),
                'website_url' => $request->get('website_url'),
                'linkedin_url' => $request->get('linkedin_url'),
                'instagram_url' => $request->get('instagram_url'),
            ];

            $affiliateData['shipping_address'] = wp_json_encode($shippingDetails);
            $affiliateData['social_links'] = wp_json_encode($socialLinks);

            $affiliateData['created_at'] = Functions::currentUTCTime();
            $affiliateData['updated_at'] = Functions::currentUTCTime();
            //            TODO: Get the program id from affiliate settings

            Affiliate::query()->create($affiliateData);
            $affiliateId = Affiliate::query()->lastInsertedId();

            $affiliate = Affiliate::query()->findOrFail($affiliateId);

            $member = Member::query()->find($memberId);

            Affiliate::autoApprove($affiliate->id);

            Database::commit();

            $send_affiliate_registered_email = apply_filters('rwpa_is_affiliate_registered_email_enabled', Settings::get('email_settings.admin_emails.affiliate_registered'));

            if ($send_affiliate_registered_email) {
                do_action('rwpa_send_affiliate_registered_email', [
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'email' => $member->email,
                ]);
            }

            return Response::success([
                'message' => 'Affiliate Created Successfully',
                'affiliate' => [
                    'id' => $affiliateId
                ]
            ]);
        } catch (\Exception $exception) {
            Database::rollBack();
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }



}
