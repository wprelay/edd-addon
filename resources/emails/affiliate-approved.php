<?php
defined("ABSPATH") or exit;
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <title> <?php echo esc_html__("Congratulations! Your Affiliate Application Has Been Approved", 'relay-affiliate-marketing') ?> </title>
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="background-color: #ffffff; word-spacing:normal;">
    <div style="">
        <div style="margin:0px auto;max-width:600px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
                <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;">
                            <div class="mj-column-per-100 mj-outlook-group-fix"
                                style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
                                <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                                    style="vertical-align:top;" width="100%">
                                    <tbody>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__("Hey {{affiliate_name}}", 'relay-affiliate-marketing') ?>
                                                    ,
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__("Good news - your affiliate application is approved! Let's make this quick", 'relay-affiliate-marketing') ?>
                                                    :
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__('Dashboard', 'relay-affiliate-marketing') ?>
                                                    : <?php echo esc_html__("Check your", 'relay-affiliate-marketing') ?>
                                                    <a href="{{affiliate_dashboard}}"><?php echo esc_html__('Affiliate Dashboard', 'relay-affiliate-marketing') ?></a> <?php echo esc_html__('for links and earnings', 'relay-affiliate-marketing') ?>
                                                    .
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__("Earnings", 'relay-affiliate-marketing') ?>
                                                    : <?php echo esc_html__("See your commission details at your ", 'relay-affiliate-marketing') ?>
                                                    <a href="{{affiliate_dashboard}}"><?php echo esc_html__('Affiliate Dashboard', 'relay-affiliate-marketing') ?></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__('Questions', 'relay-affiliate-marketing') ?>
                                                    ?: <?php echo esc_html__("Just reply to this email. We're here to help", 'relay-affiliate-marketing') ?></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;"><?php echo esc_html__("Excited to see what we'll achieve together! Welcome aboard!", 'relay-affiliate-marketing') ?></div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                <div style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;">
                                                    <?php echo esc_attr__('Cheers', 'relay-affiliate-marketing') ?>, <br /><?php echo esc_attr__('Team', 'relay-affiliate-marketing') ?> {{store_name}}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
