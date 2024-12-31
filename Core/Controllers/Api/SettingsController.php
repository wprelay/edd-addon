<?php

namespace EDDA\Affiliate\Core\Controllers\Api;

defined("ABSPATH") or exit;

use Error;
use Exception;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\Core\Models\CouponPayout;
use RelayWp\Affiliate\Core\Models\Payout;
use RelayWp\Affiliate\Core\Resources\Settings\AffiliateSettings;
use RelayWp\Affiliate\Core\Resources\Settings\EmailSettings;
use RelayWp\Affiliate\Core\Resources\Settings\GeneralSettings;
use Cartrabbit\Request\Request;
use Cartrabbit\Request\Response;
use RelayWp\Affiliate\App\Services\Settings;
use RelayWp\Affiliate\Core\ValidationRequest\Settings\AffiliateSettingsRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Settings\EmailSettingsRequest;
use RelayWp\Affiliate\Core\ValidationRequest\Settings\GeneralSettingsRequest;
use RelayWp\Affiliate\Core\ValidationRequest\ValidateLicenseRequest;

class SettingsController
{
    public function saveGeneralSettings(Request $request)
    {
        $request->validate(new GeneralSettingsRequest());
        try {

            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $existing_general_settings = isset($rwpa_settings['general_settings']) ? $rwpa_settings['general_settings'] : [];

            $existing_general_settings['cookie_duration'] = $request->get('cookie_duration');
            $existing_general_settings['contact_information']['merchant_name'] = $request->get('contact_information.merchant_name');
            $existing_general_settings['contact_information']['merchant_email'] = $request->get('contact_information.merchant_email');
            $existing_general_settings['commission_settings']['exclude_shipping'] = Functions::getBoolValue($request->get('commission_settings.exclude_shipping', 0));
            $existing_general_settings['commission_settings']['exclude_taxes'] = Functions::getBoolValue($request->get('commission_settings.exclude_taxes', 0));
            $existing_general_settings['color_settings']['primary_color'] = $request->get('color_settings.primary_color', '#000000');
            $existing_general_settings['color_settings']['secondary_color'] = $request->get('color_settings.secondary_color', '#ffffff');

            $rwpa_settings['general_settings'] = $existing_general_settings;

            $data = wp_json_encode($rwpa_settings);

            update_option('rwpa_plugin_settings', $data);

            Response::success([
                'message' => 'General Settings Saved Successfully',
            ]);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    //Need to implement with Pagination
    public function getGeneralSettings(Request $request)
    {
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $general_settings = isset($rwpa_settings['general_settings']) ? $rwpa_settings['general_settings'] : [];

            if (empty($general_settings)) {
                $general_settings = Settings::getDefaultSettingsData('general_settings');
            }

            return GeneralSettings::resource([$general_settings]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public function saveAffiliateSettings(Request $request)
    {
        $request->validate(new AffiliateSettingsRequest());
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $existing_affiliate_settings = isset($rwpa_settings['affiliate_settings']) ? $rwpa_settings['affiliate_settings'] : [];

            $existing_affiliate_settings['general']['allow_affiliate_registration'] = $allow_registration = Functions::getBoolValue($request->get('general.allow_affiliate_registration'));
            $existing_affiliate_settings['general']['affiliate_registration_page_id'] = $allow_registration ? $request->get('general.affiliate_registration_page_id') : null;
            $existing_affiliate_settings['general']['default_program_id'] = $allow_registration ? $request->get('general.default_program_id') : false;
            $existing_affiliate_settings['general']['short_code_name'] = '[affiliate_go_registration_form]';
            $existing_affiliate_settings['general']['auto_approve_commission'] = Functions::getBoolValue($request->get('general.auto_approve_commission'));
            $existing_affiliate_settings['general']['auto_approve_delay_in_days'] = $request->get('general.auto_approve_delay_in_days');
            $existing_affiliate_settings['url_options']['url_variable'] = $request->get('url_options.url_variable');
            $existing_affiliate_settings['successful_order_status'] = $request->get('successful_order_status', []);
            $existing_affiliate_settings['failure_order_status'] = $request->get('failure_order_status', []);
            $existing_affiliate_settings['recaptcha']['site_key'] = $request->get('recaptcha.site_key', '');
            $existing_affiliate_settings['recaptcha']['secret_key'] = $request->get('recaptcha.secret_key', '');


            $rwpa_settings['affiliate_settings'] = $existing_affiliate_settings;

            $data = wp_json_encode($rwpa_settings);

            update_option('rwpa_plugin_settings', $data);

            Response::success([
                'message' => __('Affiliate Settings Saved Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getAffiliateSettings()
    {
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $affiliate_settings = isset($rwpa_settings['affiliate_settings']) ? $rwpa_settings['affiliate_settings'] : [];

            if (empty($affiliate_settings)) {
                $affiliate_settings = Settings::getDefaultSettingsData('affiliate_settings');
            }

            return AffiliateSettings::resource([$affiliate_settings]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getEmailSettings(Request $request)
    {
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $email_settings = isset($rwpa_settings['email_settings']) ? $rwpa_settings['email_settings'] : [];

            if (empty($email_settings)) {
                $email_settings = Settings::getDefaultSettingsData('email_settings');
            }

            return EmailSettings::resource([$email_settings]);
        } catch (Exception | Error $exception) {
            PluginHelper::logError('Error Occurred while Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function saveEmailSettings(Request $request)
    {
        $request->validate(new EmailSettingsRequest());
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $existing_email_settings = isset($rwpa_settings['email_settings']) ? $rwpa_settings['email_settings'] : [];

            $existing_email_settings['affiliate_emails']['affiliate_approved'] = Functions::getBoolValue($request->get('affiliate_emails.affiliate_approved'));
            $existing_email_settings['affiliate_emails']['affiliate_rejected'] = Functions::getBoolValue($request->get('affiliate_emails.affiliate_rejected'));
            $existing_email_settings['affiliate_emails']['commission_approved'] = Functions::getBoolValue($request->get('affiliate_emails.commission_approved'));
            $existing_email_settings['affiliate_emails']['commission_rejected'] = Functions::getBoolValue($request->get('affiliate_emails.commission_rejected'));
            $existing_email_settings['affiliate_emails']['payment_processed'] = Functions::getBoolValue($request->get('affiliate_emails.payment_processed'));
            $existing_email_settings['admin_emails']['affiliate_registered'] = Functions::getBoolValue($request->get('admin_emails.affiliate_registered'));
            $existing_email_settings['admin_emails']['affiliate_sale_made'] = Functions::getBoolValue($request->get('admin_emails.affiliate_sale_made'));

            $rwpa_settings['email_settings'] = $existing_email_settings;
            $data = wp_json_encode($rwpa_settings);

            update_option('rwpa_plugin_settings', $data);

            Response::success([
                'message' => __('Email Settings Saved Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function getPaymentSettings(Request $request)
    {
        try {
            $paymentMethods = Payout::getPaymentMethods();
            Response::success($paymentMethods);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }

    public static function savePaymentSettings(Request $request)
    {
        try {
            $rwpa_settings = get_option('rwpa_plugin_settings', get_option('rwp_plugin_settings', '[]'));
            $data = Functions::jsonDecode($rwpa_settings);
            $rwpa_settings = $data ? $data : [];

            $existing_payment_settings = isset($rwpa_settings['payment_settings']) ? $rwpa_settings['payment_settings'] : [];

            $payment_settings = $request->get('payment_settings', []);

            $updated_settings = [];

            foreach ($payment_settings as $key => $value) {
                $updated_settings[$key] = [
                    'enabled' => Functions::getBoolValue($value)
                ];
            }

            $updated_settings['manual'] = [
                'enabled' => true
            ];

            $rwpa_settings['payment_settings'] = $updated_settings;
            $data = wp_json_encode($rwpa_settings);

            update_option('rwpa_plugin_settings', $data);

            Response::success([
                'message' => __('Payment Settings Saved Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }


    public function saveCouponPaymentSettings(Request $request)
    {
        try {

            $enable_minimum_payout_threshold = Functions::getBoolValue($request->get('enable_minimum_payout_threshold'));
            $minimum_thersold_payout_amount = $request->get('minimum_thersold_payout_amount');
            $enable_coupon_expiry = Functions::getBoolValue($request->get('enable_coupon_expiry'));
            $coupon_expiry_days = $request->get('coupon_expiry_days');

            $settings =  [
                'enable_minimum_payout_threshold' => $enable_minimum_payout_threshold,
                'minimum_thersold_payout_amount' => $minimum_thersold_payout_amount,
                "enable_coupon_expiry" => $enable_coupon_expiry,
                "coupon_expiry_days" => $coupon_expiry_days,

            ];

            $data = wp_json_encode($settings);

            $settings_key = CouponPayout::OPTION_KEY;
            update_option($settings_key, $data);

            Response::success([
                'message' => __('Coupon Payout Settings Saved Successfully', 'relay-affiliate-marketing'),
            ]);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }


    public function getCouponPaymentSettings(Request $request)
    {
        try {
            $settings_key = CouponPayout::OPTION_KEY;

            $rwpa_coupon_settings = get_option($settings_key, '{}');
            $settings = Functions::jsonDecode($rwpa_coupon_settings);

            $data =  [
                'enable_minimum_payout_threshold' => $settings['enable_minimum_payout_threshold'] ?? false,
                'minimum_thersold_payout_amount' => $settings['minimum_thersold_payout_amount'] ?? 0,
                "enable_coupon_expiry" => $settings['enable_coupon_expiry'] ?? false,
                "coupon_expiry_days" => $settings['coupon_expiry_days'] ?? 0,
            ];

            return Response::success($data);
        } catch (Exception $exception) {
            PluginHelper::logError('Error Occurred While Processing', [__CLASS__, __FUNCTION__], $exception);
            return Response::error(PluginHelper::serverErrorMessage());
        }
    }
}
