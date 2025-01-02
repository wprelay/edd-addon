<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD;

defined("ABSPATH") or exit;

use EDDA\Affiliate\App\Helpers\PluginHelper;
use EDDA\Affiliate\App\Notifications\Emails\AffiliateApprovedEmail;
use EDDA\Affiliate\App\Notifications\Emails\AffiliateRegisteredEmail;
use EDDA\Affiliate\App\Notifications\Emails\AffiliateRejectedEmail;
use EDDA\Affiliate\App\Notifications\Emails\AffiliateSaleMadeEmail;
use EDDA\Affiliate\App\Notifications\Emails\CommissionApprovedEmail;
use EDDA\Affiliate\App\Notifications\Emails\CommissionRejectedEmail;
use EDDA\Affiliate\App\Notifications\Emails\PaymentProcessedEmail;

class EmailController
{
    public static function addEmails($emails)
    {
        if (!isset($emails['AffiliateApprovedEmail']) && class_exists(AffiliateApprovedEmail::class)) {
            $emails['AffiliateApprovedEmail'] = new AffiliateApprovedEmail();
        }

        if (!isset($emails['AffiliateRejectedEmail']) && class_exists(AffiliateRejectedEmail::class)) {
            $emails['AffiliateRejectedEmail'] = new AffiliateRejectedEmail();
        }

        if (!isset($emails['AffiliateSaleMadeEmail']) && class_exists(AffiliateSaleMadeEmail::class)) {
            $emails['AffiliateSaleMadeEmail'] = new AffiliateSaleMadeEmail();
        }

        if (!isset($emails['PaymentProcessedEmail']) && class_exists(PaymentProcessedEmail::class)) {
            $emails['PaymentProcessedEmail'] = new PaymentProcessedEmail();
        }

        if (!isset($emails['CommissionApprovedEmail']) && class_exists(CommissionApprovedEmail::class)) {
            $emails['CommissionApprovedEmail'] = new CommissionApprovedEmail();
        }

        if (!isset($emails['CommissionRejectedEmail']) && class_exists(CommissionRejectedEmail::class)) {
            $emails['CommissionRejectedEmail'] = new CommissionRejectedEmail();
        }

        if (!isset($emails['AffiliateRegisteredEmail']) && class_exists(AffiliateRegisteredEmail::class)) {
            $emails['AffiliateRegisteredEmail'] = new AffiliateRegisteredEmail();
        }
        return $emails;
    }

    public static function sendAffiliateApprovedEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            $emails = edd_get_email_templates();
            if (isset($emails['AffiliateApprovedEmail'])) {
                $emails['AffiliateApprovedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Approved Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function sendAffiliateRejectedEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();
            if (isset($emails['AffiliateRejectedEmail'])) {
                $emails['AffiliateRejectedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Rejected Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function paymentProcessedEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();

            if (isset($emails['PaymentProcessedEmail'])) {
                // Trigger the email with the provided data
                $emails['PaymentProcessedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Payment Processed Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function commissionApprovedEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();

            if (isset($emails['CommissionApprovedEmail'])) {
                // Trigger the email with the provided data
                $emails['CommissionApprovedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Commission Approved Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function commissionRejectedEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();

            if (isset($emails['CommissionRejectedEmail'])) {
                // Trigger the email with the provided data
                $emails['CommissionRejectedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Commission Rejected Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function affiliateRegisteredEmail($data)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();

            if (isset($emails['AffiliateRegisteredEmail'])) {
                // Trigger the email with the provided data
                $emails['AffiliateRegisteredEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Registered Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function newSaleMadeEmail($data, $order_id)
    {
        add_filter('rwpa_is_need_to_send_email', function($value) {
            return false;
        });
        try {
            // Get the registered EDD email templates
            $emails = edd_get_email_templates();

            if (isset($emails['AffiliateSaleMadeEmail'])) {
                // Trigger the email with the provided data and order ID
                $emails['AffiliateSaleMadeEmail']->trigger($data, $order_id);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending New Sale Made Email to Store Owner', [__CLASS__, __FUNCTION__], $error);
        }
    }

}

