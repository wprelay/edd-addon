<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use EDD_Emails;
use RelayWp\Affiliate\App\Services\Settings;

class AffiliateSaleMadeEmail extends EDD_Emails
{
    public function __construct()
    {
        // Initialize the email object with necessary details
        parent::__construct();

        // Email details
        $this->id = 'rwpa_affiliate_sale_made_email';
        $this->title = __('New Affiliate Sale Email', 'relay-affiliate-marketing');
        $this->description = __('An email sent to the Store Owner when an affiliate sale is made', 'relay-affiliate-marketing');
        $this->customer_email = false;
        $this->heading = __('Affiliate Sale Made Email', 'relay-affiliate-marketing');
        $this->subject = __("[{site_title}] E Congratulations - An Affiliate Sale Has Been Made!", 'relay-affiliate-marketing');

        // Define the template paths
        $this->template_html = RWPA_PLUGIN_PATH . 'resources/emails/affiliate-sale-made.php';
        $this->template_plain = RWPA_PLUGIN_PATH . 'resources/emails/plain/affiliate-sale-made.php';
        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';

        // Define the recipient email (store owner)
        $email = Settings::get('general_settings.contact_information.merchant_email');
        $this->recipient = $email ? $email : get_bloginfo('admin_email');
    }

    public function trigger($data, $order_id)
    {
        if (empty($data['email'])) {
            return;
        }

        // Get the email content and replace shortcodes
        $html = $this->get_content_html();

        $short_codes = [
            '{{affiliate_name}}' => "{$data['first_name']} {$data['last_name']}",
            '{{email}}' => $data['email'],
            '{{customer_email}}' => $data['customer_email'],
            '{{customer_name}}' => $data['customer_name'],
            '{{order_id}}' => $order_id,
            '{{order_amount}}' => $data['order_amount'],
            '{{order_created_at}}' => $data['order_created_at'],
            '{{affiliate_dashboard}}' => WC::getAffilateEndPoint(),
            '{{store_name}}' => WC::getStoreName(),
        ];

        // Apply any custom filters for shortcodes
        $short_codes = apply_filters('rwpa_affiliate_sale_made_email_short_codes', $short_codes);

        // Replace the shortcodes in the content
        foreach ($short_codes as $short_code => $short_code_value) {
            $html = str_replace($short_code, $short_code_value, $html);
        }

        // Send the email to the store owner
        $this->send($this->recipient, $this->subject, $html, $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html()
    {
        // Render the HTML content from the template
        return $this->get_content($this->template_html);
    }

    public function get_content_plain()
    {
        // Render the plain-text content from the template
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
