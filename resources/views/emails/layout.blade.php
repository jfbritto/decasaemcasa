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
<body style="margin:0; padding:0; background-color:#ece6d9; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; -webkit-font-smoothing:antialiased;">
    <!-- Wrapper -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ece6d9; padding:30px 0;">
        <tr>
            <td align="center">
                <!-- Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 16px rgba(26,46,110,0.10);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1a2e6e; padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; color:#e88a2d; font-size:26px; font-weight:800; letter-spacing:0.5px;">
                                De Casa em Casa
                            </h1>
                            <p style="margin:8px 0 0; color:#bfc8e0; font-size:13px; font-weight:400;">
                                Uma turnê que acontece onde a vida acontece
                            </p>
                        </td>
                    </tr>

                    <!-- Orange accent line -->
                    <tr>
                        <td style="background-color:#e88a2d; height:4px; font-size:0; line-height:0;">&nbsp;</td>
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
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f3ed; border-radius:10px; border-left:4px solid #e88a2d;">
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
                            <a href="@yield('cta_url')" target="_blank" style="display:inline-block; background-color:#e88a2d; color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:15px; font-weight:700; letter-spacing:0.3px;">
                                @yield('cta_text', 'Acompanhar Inscrição')
                            </a>
                        </td>
                    </tr>
                    @endif

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 40px;">
                            <hr style="border:none; border-top:1px solid #e8e2d6; margin:0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px 28px; text-align:center;">
                            <p style="margin:0 0 8px; color:#1a2e6e; font-size:13px; font-weight:600;">
                                Equipe De Casa em Casa
                            </p>
                            <p style="margin:0; color:#9a9384; font-size:11px; line-height:1.5;">
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
