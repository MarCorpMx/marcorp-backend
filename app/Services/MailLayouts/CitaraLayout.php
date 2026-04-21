<?php

namespace App\Services\MailLayouts;

class CitaraLayout
{
    public static function render(string $content): string
    {
        return '
        <html>
        <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial, sans-serif;">

            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0;">
                <tr>
                    <td align="center">

                        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                            
                            <!-- HEADER -->
                            <tr>
                                <td style="background:#10b981;padding:20px;text-align:center;color:#ffffff;font-size:24px;font-weight:bold;">
                                    CITARA
                                </td>
                            </tr>

                            <!-- BODY -->
                            <tr>
                                <td style="padding:30px;color:#333333;font-size:16px;line-height:1.5;">
                                    ' . $content . '
                                </td>
                            </tr>

                            <!-- FOOTER -->
<tr>
    <td style="padding:20px;text-align:center;font-size:12px;color:#888888;">
        
        © ' . date('Y') . ' CITARA<br>
        Sistema de gestión de citas<br><br>

        <span style="font-size:11px;color:#aaaaaa;">
Desarrollado por MarCorp
</span><br><br>

        <a href="https://www.marcorp.mx" style="color:#10b981;text-decoration:none;">Sitio web</a> |
        <a href="https://www.marcorp.mx/diamar/contacto" style="color:#10b981;text-decoration:none;">Soporte</a>

        <br><br>

        <!-- Redes -->
        <table cellpadding="0" cellspacing="0" align="center">
            <tr>

                <td style="padding:0 5px;">
                    <a href="https://www.facebook.com/profile.php?id=61585221730768">
                        <img src="https://cdn-icons-png.flaticon.com/24/733/733547.png" width="24" height="24" style="display:block;">
                    </a>
                </td>

                <td style="padding:0 5px;">
                    <a href="https://www.instagram.com/marcorp_99">
                        <img src="https://cdn-icons-png.flaticon.com/24/733/733558.png" width="24" height="24" style="display:block;">
                    </a>
                </td>

                <td style="padding:0 5px;">
                    <a href="https://wa.me/5217702021345">
                        <img src="https://cdn-icons-png.flaticon.com/24/733/733585.png" width="24" height="24" style="display:block;">
                    </a>
                </td>

            </tr>
        </table>

    </td>
</tr>

                        </table>

                    </td>
                </tr>
            </table>

        </body>
        </html>
        ';
    }
}
