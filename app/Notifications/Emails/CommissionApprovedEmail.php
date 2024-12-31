<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\WC;
use WC_Email;

class CommissionApprovedEmail extends WC_Email
{

    public $commission_type = 'commission';

    public function __construct()
    {
        $this->id = 'rwpa_affiliate_commission_approved_email';
        $this->title = __('Commission Approved Email', 'relay-affiliate-marketing');
        $this->description = __('An Email sent when the affiliate commission is approved', 'relay-affiliate-marketing');
        // For admin area to let the user know we are sending this email to customers.
        $this->customer_email = true;

        $this->heading = __("[{site_title}] Congratulations - You've Earned a Commission!", 'relay-affiliate-marketing');

        $this->subject = __("[{site_title}] Congratulations - You've Earned a Commission!", 'relay-affiliate-marketing');

        // Template paths.
        $this->template_html = 'affiliate-commission-approved.php';

        $this->template_plain = 'plain/affiliate-commission-approved.php';
        parent::__construct();

        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Action to which we hook onto to send the email.
    }

    public function trigger($data)
    {
        $email = $data['email'];

        $this->commission_type = $data['commission_type'] ?? 'commission';

        $html = $this->get_content();
        $short_codes = [
            '{{affiliate_name}}' => "{$data['affiliate_name']}",
            '{{email}}' => $data['email'],
            '{{commission_amount}}' => $data['commission_amount'] . ' ' . $data['commission_currency'],
            '{{commission_order_id}}' => $data['commission_order_id'],
            '{{sale_date}}' => $data['sale_date'],
            '{{commission_type}}' => $data['commission_type'] ?? 'commission',
            '{{woo_order_id}}' => $data['relay_wp_order']->woo_order_id,
            '{{affiliate_dashboard}}' => WC::getAffilateEndPoint(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        $short_codes = apply_filters('rwpa_affiliate_commission_approved_email_short_codes', $short_codes);

        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        $this->send($email, $this->get_subject(), $html, $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html()
    {
        error_log('printing commission type');
        error_log($this->commission_type);

        return wc_get_template_html($this->template_html, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => $this,
            'commission_type' => $this->commission_type
        ), '', $this->template_base);
    }

    public function get_content_plain()
    {
        error_log('printing commission type');
        error_log($this->commission_type);
        $html = wc_get_template_html($this->template_plain, array(
            'order' => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => true,
            'email' => $this,
            'commission_type' => $this->commission_type
        ), '', $this->template_base);


        return $html;
    }
}
