<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

use RelayWp\Affiliate\App\Helpers\WC;
use WC_Email;

defined('ABSPATH') or exit;

class PaymentProcessedEmail extends WC_Email
{

    public function __construct()
    {
        // Email slug we can use to filter other data.
        $this->id = 'rwpa_affiliate_payment_processed_email';
        $this->title = __('Payment Processed Email', 'relay-affiliate-marketing');
        $this->description = __('An Email sent when the affiliate payment is processed', 'relay-affiliate-marketing');
        // For admin area to let the user know we are sending this email to customers.
        $this->customer_email = true;

        $this->heading = __("[{site_title}]  We've processed your affiliate payout", 'relay-affiliate-marketing');

        $this->subject = __("[{site_title}]  We've processed your affiliate payout", 'relay-affiliate-marketing');

        // Template paths.
        $this->template_html = 'affiliate-payment-processed.php';

        $this->template_plain = 'plain/affiliate-payment-processed.php';
        parent::__construct();

        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Action to which we hook onto to send the email.
    }

    public function trigger($data)
    {
        $email = $data['email'];
        $html = $this->get_content();

        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{email}}' => $data['email'],
            '{{payout_amount}}' => $data['amount'] . ' ' . $data['currency'],
            '{{payment_source}}' => $data['payment_source'],
            '{{payout_date}}' => $data['payout_date'],
            '{{payout_affiliate_notes}}' => $data['payout_affiliate_notes'],
            '{{affiliate_dashboard}}' => WC::getAffilateEndPoint(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        $short_codes = apply_filters('rwpa_affiliate_rejected_email_short_codes', $short_codes);

        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        $this->send($email, $this->get_subject(), $html, $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html()
    {
        return wc_get_template_html($this->template_html, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => $this
        ), '', $this->template_base);
    }

    public function get_content_plain()
    {
        $html = wc_get_template_html($this->template_plain, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => true,
            'email' => $this
        ), '', $this->template_base);


        return $html;
    }
}
