<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', 'De Casa em Casa')</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#f4f1eb; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; -webkit-font-smoothing:antialiased;">
    <!-- Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f1eb; padding:30px 0;">
        <tr>
            <td align="center">
                <!-- Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:0.5px;">
                                De Casa em Casa
                            </h1>
                            <p style="margin:6px 0 0; color:rgba(255,255,255,0.85); font-size:13px; font-weight:400;">
                                Uma turnê que acontece onde a vida acontece
                            </p>
                        </td>
                    </tr>

                    <!-- Status Badge -->
                    @hasSection('badge')
                    <tr>
                        <td align="center" style="padding:24px 40px 0;">
                            @yield('badge')
                        </td>
                    </tr>
                    @endif

                    <!-- Content -->
                    <tr>
                        <td style="padding:28px 40px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Event Info Box -->
                    @hasSection('event_info')
                    <tr>
                        <td style="padding:0 40px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f7f4; border-radius:10px; border:1px solid #e8e5de;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        @yield('event_info')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- CTA Button -->
                    @hasSection('cta_url')
                    <tr>
                        <td align="center" style="padding:0 40px 32px;">
                            <a href="@yield('cta_url')" target="_blank" style="display:inline-block; background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:15px; font-weight:600; letter-spacing:0.3px;">
                                @yield('cta_text', 'Acompanhar Inscrição')
                            </a>
                        </td>
                    </tr>
                    @endif

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 40px;">
                            <hr style="border:none; border-top:1px solid #e8e5de; margin:0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px 28px; text-align:center;">
                            <p style="margin:0 0 8px; color:#8a8578; font-size:13px; font-weight:500;">
                                Equipe De Casa em Casa
                            </p>
                            <p style="margin:0; color:#b0a99e; font-size:11px; line-height:1.5;">
                                Dentro de casas reais, com pessoas reais,<br>
                                criando um encontro inédito e poderoso.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Container -->
            </td>
        </tr>
    </table>
    <!-- End Wrapper -->
</body>
</html>
