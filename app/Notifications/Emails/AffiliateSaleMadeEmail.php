<?php

namespace EDDA\Affiliate\App\Notifications\Emails;

defined('ABSPATH') or exit;

use RelayWp\Affiliate\App\Helpers\WC;
use RelayWp\Affiliate\App\Services\Settings;
use WC_Email;

class AffiliateSaleMadeEmail extends WC_Email
{
    public function __construct()
    {
        $storeName = WC::getStoreName();
        // Email slug we can use to filter other data.
        $this->id = 'rwpa_affiliate_sale_made_email';
        $this->title = __('New Affiliate Sale Email', 'relay-affiliate-marketing');
        $this->description = __('An email sent to the Store Owner', 'relay-affiliate-marketing');
        // For admin area to let the user know we are sending this email to customers.
        $this->heading = __('Affiliate Sale Made Email', 'relay-affiliate-marketing');
        // translators: placeholder is {blogname}, a variable that will be substituted when email is sent out
        $this->subject = __("[{site_title}] Congratulations - An Affiliate Sale Has Been Made!", 'relay-affiliate-marketing');

        $this->customer_email = false;

        $email = Settings::get('general_settings.contact_information.merchant_email');

        $this->recipient =  $email ?  $email : get_bloginfo('admin_email');
        // Template paths.
        $this->template_html = 'affiliate-sale-made.php';

        $this->template_plain = 'plain/affiliate-sale-made.php';
        parent::__construct();

        $this->template_base = RWPA_PLUGIN_PATH . 'resources/emails/';
        // Action to which we hook onto to send the email.
    }

    public function trigger($data, $order_id)
    {
        $email = Settings::get('general_settings.contact_information.merchant_email');

        if (empty($email)) {
            $email  = get_bloginfo('admin_email');
        }

        $html = $this->get_content();

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

        $short_codes = apply_filters('rwpa_affiliate_sale_made_email_short_codes', $short_codes);

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
