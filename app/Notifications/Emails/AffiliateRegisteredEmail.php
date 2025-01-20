<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use EDD_Emails;
use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Services\Settings;

class AffiliateRegisteredEmail extends EDD_Emails
{
    public function __construct()
    {
        // Email details
        $this->id = 'rwpa_affiliate_registered_email';
        $this->title = __('New Affiliate Registration Email', 'relay-affiliate-marketing');
        $this->description = __('An email sent to the store owner when they register.', 'relay-affiliate-marketing');
        $this->customer_email = false;

        // Set the recipient for the email (store owner)
        $email = Settings::get('general_settings.contact_information.merchant_email');
        $this->recipient = $email ? $email : get_bloginfo('admin_email');

        // Email heading and subject
        $this->heading = __("[{site_title}] New Affiliate Application Received", 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}] New Affiliate Application Received", 'relay-affiliate-marketing');

        // Template paths
        $this->template_html = RWPA_PLUGIN_PATH . 'resources/emails/affiliate-registered-email.php';
        $this->template_plain = RWPA_PLUGIN_PATH . 'resources/emails/plain/affiliate-registered-email.php';
        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Ensure parent constructor is called
        parent::__construct();
    }

    public function trigger($data)
    {
        // Define the recipient (store owner)
        $email = Settings::get('general_settings.contact_information.merchant_email');
        if (empty($email)) {
            $email = get_bloginfo('admin_email');
        }

        // Get the email content
        $html = $this->get_content_html();

        // Define the shortcodes for the email
        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{affiliate_email}}' => $data['email'],
            '{{admin_dashboard}}' => WC::getAdminDashboard(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        // Apply any custom filters for shortcodes
        $short_codes = apply_filters('rwpa_affiliate_registered_email_short_codes', $short_codes);

        // Replace shortcodes in the HTML content
        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        // Send the email to the store owner
        $this->send($email, $this->subject, $html, $this->get_headers());
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
