<!doctype html>
<html lang="en">

<head>
    <title>Email</title>
</head>

<body style="background-color:#f1f4f5; margin:0">
<div style="background-color:#f1f4f5; padding:0 0 20px 0;font-family:Roboto,RobotoDraft,Helvetica,Arial,sans-serif;line-height:1.2; font-size: 14px;">
    <div style="margin:0px auto;max-width:960px;">
        <div style="/*background-color:#5a1d56;*/margin:0px auto;max-width:720px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                <tbody>
                <tr>
                    <td style="direction:ltr;font-size:0;padding:20px 0 20px 0;text-align:center;">
                        <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:0;word-break:break-word;">
                                        <table border="0" cellpadding="0" cellspacing="0"
                                               style="border-collapse:collapse;border-spacing:0px;">
                                            <tbody>
                                            <tr>
                                                <td style="width:130px;">
                                                    <img alt="" width="260" height="auto"
                                                         src="{{ asset('img/logo.png') }}" />
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="background-color:#f4f4f4;margin:0px auto;max-width:960px;">
        <div style="background-color:#FFFFFF;margin:0px auto;max-width:720px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;width:100%;">
                <tbody>
                <tr>
                    <td style="direction:ltr;font-size:0;text-align:center;">
                        <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" style="padding:0 25px 15px;word-break:break-word;">
                                        <div style="font-size:16px;text-align:left;color:#000000;">
                                            @yield('content')
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="background-color:#f4f4f4;margin:0px auto;max-width:960px;">
        <div style="background-color:#eeeeee;margin:0px auto;max-width:720px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#eeeeee;width:100%;">
                <tbody>
                <tr>
                    <td style="direction:ltr;font-size:0;text-align:center;">
                        <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center"
                                        style="padding:10px 25px;padding-top:20px;padding-right:25px;padding-bottom:20px;padding-left:25px;word-break:break-word;">
                                        <div style="font-size:16px;text-align:center;color:#000000;">
												<span style="font-size:14px">
													@section('regards')
                                                        Kind Regards,<br/><strong>Recomm</strong>
                                                    @show
												</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    @section('brand')
        <div style="background-color:#f4f4f4;margin:0px auto;max-width:960px;">
            <div style="background-color:#FFFFFF;margin:0px auto;max-width:720px;">
                <table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;width:100%;">
                    <tbody>
                    <tr>
                        <td style="direction:ltr;font-size:0;text-align:center;">
                            <div style="direction:ltr;font-size:0;display:inline-block;width:100%;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="left" style="padding:20px 25px 0; word-break:break-word;">
                                            <div style="text-align:left;color:#000000;">
                                                <p style="font-size:12px; margin:0">
                                                    T:
                                                    <a href="tel:01494 442265"
                                                       style="font-size:12px;color:#000000; text-decoration: none">01494 442265</a>
                                                </p>
                                            </div>
                                        </td>
                                        <td align="left" style="padding:20px 25px 0; word-break:break-word;">
                                            <div style="text-align:right;">
                                                <p style="font-size:11px; margin:0;color:#000000;">W: <a
                                                        href="http://www.recomm.co.uk"
                                                        style="font-size:13px;color:#000000; text-decoration: none"
                                                        target="_blank">www.recomm.co.uk</a></p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="left"
                                            style="padding:10px 25px;padding-top:20px;padding-right:25px;padding-bottom:20px;padding-left:25px;word-break:break-word;">
                                            <div style="font-size:16px;text-align:left;color:#000000;">
                                                <p style="font-size:12px; margin-top:0">
                                                    Company Registration No. 10775061 - Registered in England and Wales.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @show

    @yield('footer-company')

    @yield('customer-id')


    <div style="background-color:#f4f4f4;margin:0px auto;max-width:960px;">
        <div style="background-color:#FFFFFF;margin:0px auto;max-width:720px;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;width:100%;">
                <tbody>
                <tr>
                    <td style="direction:ltr;font-size:0;text-align:center;">
                        <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left"
                                        style="padding:0 25px 25px;word-break:break-word;">
                                        <div style="font-size:16px;text-align:left;color:#000000;">
                                            <p style="font-size:11px; margin:0">
                                                This email and any files transmitted with it are confidential
                                                and intended solely for the use of the individual or entity to whom
                                                they are addressed. If you are not the intended recipient, you should
                                                not copy it, re-transmit it, use it or disclose its contents, but
                                                should return it to the sender immediately and delete your copy from
                                                your system. Recommerce Ltd does not accept legal
                                                responsibility for the contents of this message. Any views or
                                                opinions presented are solely those of the author and do not
                                                necessarily represent those of Recommerce Ltd.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
