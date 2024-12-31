<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\WC;
use WC_Email;


class CommissionRejectedEmail extends WC_Email
{

    public function __construct()
    {
        $storeName = WC::getStoreName();
        // Email slug we can use to filter other data.
        $this->id = 'rwpa_affiliate_commission_rejected_email';
        $this->title = __('Commission Rejected Email', 'relay-affiliate-marketing');
        $this->description = __('An Email sent when the affiliate commission is approved', 'relay-affiliate-marketing');
        // For admin area to let the user know we are sending this email to customers.
        $this->customer_email = true;
        $this->heading = __("[{site_title}] Commission Rejected Email", 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}] Commission Rejected Email", 'relay-affiliate-marketing');

        // Template paths.
        $this->template_html = 'affiliate-commission-rejected.php';

        $this->template_plain = 'plain/affiliate-commission-rejected.php';
        parent::__construct();

        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Action to which we hook onto to send the email.
    }

    public function trigger($data)
    {
        $email = $data['email'];
        $html = $this->get_content();

        $short_codes = [
            '{{affiliate_name}}' => "{$data['affiliate_name']}",
            '{{email}}' => $data['email'],
            '{{commission_amount}}' => $data['commission_amount'] . ' ' . $data['commission_currency'],
            '{{commission_order_id}}' => $data['commission_order_id'],
            '{{woo_order_id}}' => $data['relay_wp_order']->woo_order_id,
            '{{affiliate_dashboard}}' => WC::getAffilateEndPoint(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        $short_codes = apply_filters('rwpa_affiliate_commission_rejected_email_short_codes', $short_codes);

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
