<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use EDD_Emails;
use RelayWp\Affiliate\App\Helpers\WC;

class PaymentProcessedEmail extends EDD_Emails
{

    public function __construct()
    {
        parent::__construct();

        // Email details
        $this->id = 'rwpa_affiliate_payment_processed_email';
        $this->title = __('Payment Processed Email', 'relay-affiliate-marketing');
        $this->description = __('An Email sent when the affiliate payment is processed', 'relay-affiliate-marketing');
        $this->customer_email = true;

        // Email heading and subject
        $this->heading = __("[{site_title}]  We've processed your affiliate payout", 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}]  We've processed your affiliate payout", 'relay-affiliate-marketing');

        // Template paths
        $this->template_html = RWPA_PLUGIN_PATH . 'resources/emails/affiliate-payment-processed.php';
        $this->template_plain = RWPA_PLUGIN_PATH . 'resources/emails/plain/affiliate-payment-processed.php';
        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';
    }

    public function trigger($data)
    {
        if (empty($data['email'])) {
            return;
        }

        // Get the email content
        $html = $this->get_content_html();

        // Define the shortcodes for the email
        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{email}}' => $data['email'],
            '{{payout_amount}}' => $data['amount'] . ' ' . $data['currency'],
            '{{payment_source}}' => $data['payment_source'],
            '{{payout_date}}' => $data['payout_date'],
            '{{payout_affiliate_notes}}' => $data['payout_affiliate_notes'],
            '{{affiliate_dashboard}}' => "WC::getAffilateEndPoint()",
            '{{store_name}}' =>' WC::getStoreName()',
        ];

        // Apply any custom filters for shortcodes
        $short_codes = apply_filters('rwpa_affiliate_payment_processed_email_short_codes', $short_codes);

        // Replace shortcodes in the HTML content
        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        // Send the email to the affiliate
        $this->send($data['email'], $this->subject, $html, $this->get_headers());
    }

    public function get_content_html()
    {
        // Get the HTML content for the email template
        return $this->get_content($this->template_html);
    }

    public function get_content_plain()
    {
        // Get the plain-text content for the email template
        return $this->get_content($this->template_plain);
    }

    public function get_content($template)
    {
        ob_start();
        include $template;
        $html = ob_get_clean();
        return $html;
    }
}
