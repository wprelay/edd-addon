<?php
defined("ABSPATH") or exit;
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <title>
    </title>
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<div style="background-color: #ffffff; word-spacing:normal;">
    <div style="">
        <table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
            <tr>
                <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                    <div style="margin:0px auto;max-width:600px;">
                        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                               style="width:100%;">
                            <tbody>
                            <tr>
                                <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="" style="vertical-align:top;width:600px;"><![endif]-->
                                    <div class="mj-column-per-100 mj-outlook-group-fix"
                                         style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                                               style="vertical-align:top;" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_attr__('Notification', 'relay-affiliate-marketing') ?>
                                                        : <?php echo esc_html__("Rejection of Commission", 'relay-affiliate-marketing') ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_attr__('Dear', 'relay-affiliate-marketing') ?> {{affiliate_name}},
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php
                                                        /* translators: placeholder description */
                                                        echo vsprintf(esc_html__("We regret to inform you that your commission has been rejected %s for the order #%s. Upon review, it has been determined that the referral did not meet the eligibility criteria outlined in our affiliate program terms and conditions", 'relay-affiliate-marketing'), ["{{commission_amount}}", "{{woo_order_id}}"])
                                                        ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_html__("While we value your efforts and contributions as an affiliate, we must adhere to the guidelines and policies established to maintain the integrity of our affiliate program", 'relay-affiliate-marketing') ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_html__("If you have any questions or require further clarification regarding the rejection, please feel free to contact our affiliate support team for assistance", 'relay-affiliate-marketing') ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_html__("Thank you for your understanding and continued participation in our affiliate program", 'relay-affiliate-marketing') ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left"
                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_attr__("Best regards", 'relay-affiliate-marketing') ?>,<br/>{{store_name}}<br/>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    </td>
    </tr>
    </table>
</div>
</body>

</html>
