<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use EDD_Emails;
use RelayWp\Affiliate\App\Helpers\Functions;
use RelayWp\Affiliate\App\Helpers\WC;

class CommissionApprovedEmail extends EDD_Emails
{
    public $commission_type = 'commission';

    public function __construct()
    {
        // Initialize the email object with necessary details
        parent::__construct();

        // Email details
        $this->id = 'rwpa_affiliate_commission_approved_email';
        $this->title = __('Commission Approved Email', 'relay-affiliate-marketing');
        $this->description = __('An Email sent when the affiliate commission is approved', 'relay-affiliate-marketing');
        $this->customer_email = true;

        // Email heading and subject
        $this->heading = __("[{site_title}] Congratulations - You've Earned a Commission!", 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}] E Congratulations - You've Earned a Commission!", 'relay-affiliate-marketing');

        // Template paths
        $this->template_html = RWPA_PLUGIN_PATH . 'resources/emails/affiliate-commission-approved.php';
        $this->template_plain = RWPA_PLUGIN_PATH . 'resources/emails/plain/affiliate-commission-approved.php';
        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';
    }

    public function trigger($data)
    {
        if (empty($data['email'])) {
            return;
        }

        // Set the commission type
        $this->commission_type = $data['commission_type'] ?? 'commission';

        // Get the email content
        $html = $this->get_content_html();

        // Define the shortcodes for the email
        $short_codes = [
            '{{affiliate_name}}' => "{$data['affiliate_name']}",
            '{{email}}' => $data['email'],
            '{{commission_amount}}' => $data['commission_amount'] . ' ' . $data['commission_currency'],
            '{{commission_order_id}}' => $data['commission_order_id'],
            '{{sale_date}}' => $data['sale_date'],
            '{{commission_type}}' => $this->commission_type,
            '{{woo_order_id}}' => $data['relay_wp_order']->woo_order_id,
            '{{affiliate_dashboard}}' => WC::getAffilateEndPoint(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        // Apply any custom filters for shortcodes
        $short_codes = apply_filters('rwpa_affiliate_commission_approved_email_short_codes', $short_codes);

        // Replace shortcodes in the HTML content
        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        // Send the email to the affiliate
        $this->send($data['email'], $this->subject, $html, $this->get_headers(), $this->get_attachments());
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
