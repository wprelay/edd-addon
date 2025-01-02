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

<body style="background-color: #ffffff; word-spacing:normal;">
    <div style="">
        <!--[if mso | IE]>
    <table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
        <div style="margin:0px auto;max-width:600px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
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
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <?php echo esc_html__('Hey', 'relay-affiliate-marketing') ?> {{affiliate_name}}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <?php echo esc_html__("Hope this message finds you well. We've got some exciting news for you - you've just earned a commission!", 'relay-affiliate-marketing') ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <table cellpadding="0" cellspacing="0" width="100%" border="0"
                                                    style="color:#000000;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:22px;table-layout:auto;width:100%;border:none;">
                                                    <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
                                                        <th style="padding:0 15px 0 0;"><?php echo esc_attr__('Commission', 'relay-affiliate-marketing') ?></th>
                                                        <th style="padding:0 15px;">{{commission_amount}}</th>
                                                    </tr>
                                                    <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
                                                        <th style="padding:0 15px 0 0;"><?php echo esc_attr__('Order Id', 'relay-affiliate-marketing') ?></th>
                                                        <th style="padding:0 15px;">#{{woo_order_id}}</th>
                                                    </tr>
                                                    <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
                                                        <th style="padding:0 15px 0 0;"><?php echo esc_attr__('Sale Date', 'relay-affiliate-marketing') ?></th>
                                                        <th style="padding:0 15px;">{{sale_date}}</th>
                                                    </tr>
                                                </table>
                                                <p style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <?php echo esc_html__("See more details at", 'relay-affiliate-marketing') ?> <a href="{{affiliate_dashboard}}"><?php echo esc_attr__('Affiliate Dashboard', 'relay-affiliate-marketing') ?></a><br>
                                                    <?php echo esc_html__("Your hard work is paying off, and we couldn't be happier to share this success with you. Your commission will be processed and paid out according to our payment schedule, so keep an eye on your account", 'relay-affiliate-marketing') ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <?php echo esc_html__("Looking forward to celebrating more wins together. Keep up the fantastic work!", 'relay-affiliate-marketing') ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if ($commission_type == 'bonus'): // Display only for bonus commission type 
                                        ?>
                                            <tr>
                                                <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                    <div style="font-family:Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                        <?php echo esc_attr__('This commission has been awarded as a special bonus for this order, recognizing additional criteria or performance achievements', 'relay-affiliate-marketing'); ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#000000;">
                                                    <?php echo esc_attr__('Cheers', 'relay-affiliate-marketing') ?>, <br /><?php echo esc_attr__('Team', 'relay-affiliate-marketing') ?><br />{{store_name}}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!--[if mso | IE]></td></tr></table><![endif]-->
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!--[if mso | IE]></td></tr></table><![endif]-->
    </div>
</body>

</html>
