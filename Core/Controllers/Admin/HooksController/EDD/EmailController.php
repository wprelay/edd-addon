<?php

namespace EDDA\Affiliate\Core\Controllers\Admin\HooksController\EDD;

defined("ABSPATH") or exit;

use RelayWp\Affiliate\App\Helpers\PluginHelper;
use RelayWp\Affiliate\App\Notifications\Emails\AffiliateApprovedEmail;
use RelayWp\Affiliate\App\Notifications\Emails\AffiliateRegisteredEmail;
use RelayWp\Affiliate\App\Notifications\Emails\AffiliateRejectedEmail;
use RelayWp\Affiliate\App\Notifications\Emails\AffiliateSaleMadeEmail;
use RelayWp\Affiliate\App\Notifications\Emails\CommissionApprovedEmail;
use RelayWp\Affiliate\App\Notifications\Emails\CommissionRejectedEmail;
use RelayWp\Affiliate\App\Notifications\Emails\PaymentProcessedEmail;

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
        try {
            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['AffiliateApprovedEmail'])) {
                $emails['AffiliateApprovedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Approved Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function sendAffiliateRejectedEmail($data)
    {
        try {
            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['AffiliateRejectedEmail'])) {
                $emails['AffiliateRejectedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Rejected Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function paymentProcessedEmail($data)
    {
        try {
            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['PaymentProcessedEmail'])) {

                $emails['PaymentProcessedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Payment Processed Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function commissionApprovedEmail($data)
    {
        try {

            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['CommissionApprovedEmail'])) {

                $emails['CommissionApprovedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Commission Approved Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function commissionRejectedEmail($data)
    {
        try {
            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['CommissionRejectedEmail'])) {
                $emails['CommissionRejectedEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Commission Rejected Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function affiliateRegisteredEmail($data)
    {
        try {
            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['AffiliateRegisteredEmail'])) {
                $emails['AffiliateRegisteredEmail']->trigger($data);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending Affiliate Registered Email', [__CLASS__, __FUNCTION__], $error);
        }
    }

    public static function newSaleMadeEmail($data, $order_id)
    {
        try {

            \WC_Emails::instance();

            $emails = wc()->mailer()->get_emails();

            if (isset($emails['AffiliateSaleMadeEmail'])) {
                $emails['AffiliateSaleMadeEmail']->trigger($data, $order_id);
            }
        } catch (\Error $error) {
            PluginHelper::logError('Error Occurred While Sending New Sale Made Email to Store Owner', [__CLASS__, __FUNCTION__], $error);
        }
    }
}

