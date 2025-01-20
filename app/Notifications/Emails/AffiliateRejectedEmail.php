<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use EDD_Emails;

class AffiliateRejectedEmail extends EDD_Emails
{
    public function __construct()
    {
        parent::__construct();

        // Email details
        $this->id = 'rwpa_affiliate_rejected_email';
        $this->title = __('Affiliate Rejected Email', 'relay-affiliate-marketing');
        $this->description = __('An email sent to the affiliate when an application is rejected', 'relay-affiliate-marketing');
        $this->customer_email = true;
        $this->heading = __('Affiliate Rejected Email', 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}] Your Affiliate Application: Update", 'relay-affiliate-marketing');
        $this->template_html = RWPA_PLUGIN_PATH . 'resources/emails/affiliate-rejected.php';
        $this->template_plain = RWPA_PLUGIN_PATH . 'resources/emails/plain/affiliate-rejected.php';
        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Additional initialization if needed.
    }

    public function trigger($data)
    {
        if (empty($data['email'])) {
            return;
        }

        $email = $data['email'];
        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{email}}' => $data['email'],
            '{{referral_code}}' => $data['referral_code'],
            '{{referral_link}}' => $data['referral_link'],
            '{{affiliate_dashboard}}' => 'www.google.com',
            '{{store_name}}' =>'edd store',
        ];

        $short_codes = apply_filters('rwpa_affiliate_rejected_email_short_codes', $short_codes);
        $html_content = $this->get_content($short_codes);

        // Send the email
        $this->send($email, $this->subject, $html_content);
    }

    public function get_content($short_codes)
    {
        ob_start();
        include $this->template_html;
        $html = ob_get_clean();

        // Replace shortcodes in the content
        foreach ($short_codes as $short_code => $value) {
            $html = str_replace($short_code, $value, $html);
        }

        return $html;
    }
}
