<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Services\Settings;
use WC_Email;


class AffiliateRegisteredEmail extends WC_Email
{

    public function __construct()
    {
        // Email slug we can use to filter other data.
        $this->id = 'rwpa_affiliate_registered_email';
        $this->title = __('New Affiliate Registration Email', 'relay-affiliate-marketing');
        $this->description = __('An email sent to the store owner when they register.', 'relay-affiliate-marketing');
        // For admin area to let the user know we are sending this email to customers.
        $this->customer_email = false;
        $email = Settings::get('general_settings.contact_information.merchant_email');
        $this->recipient =  $email ?  $email : get_bloginfo('admin_email');
        $this->heading = __("[{site_title}] New Affiliate Application Received", 'relay-affiliate-marketing');

        $this->subject = __("[{site_title}] New Affiliate Application Received", 'relay-affiliate-marketing');

        // Template paths.
        $this->template_html = 'affiliate-registered-email.php';

        $this->template_plain = 'plain/affiliate-registered-email.php';
        parent::__construct();

        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';
    }

    public function trigger($data)
    {
        $email = Settings::get('general_settings.contact_information.merchant_email');

        if (empty($email)) {
            $email  = get_bloginfo('admin_email');
        }

        $html = $this->get_content();

        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{affiliate_email}}' => $data['email'],
            '{{admin_dashboard}}' => WC::getAdminDashboard(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        $short_codes = apply_filters('rwpa_affiliate_registered_email_short_codes', $short_codes);

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
