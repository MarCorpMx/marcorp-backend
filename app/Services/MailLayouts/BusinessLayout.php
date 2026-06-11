<?php

namespace App\Services\MailLayouts;

use App\Models\Organization;
use App\Models\Branch;
use App\Models\Subsystem;
use App\Helpers\SocialNetwork;
use App\Services\LocationService;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class BusinessLayout
{
    public static function render(
        string $content,
        ?Organization $organization = null,
        ?Branch $branch = null
    ): string {

        // Resolvemos el plan
        $appointmentsPlan = null;

        if ($organization) {

            $appointmentsPlan = $organization
                ->organizationSubsystems()
                ->with('plan', 'subsystem')
                ->whereHas(
                    'subsystem',
                    fn($q) => $q->where('key', 'citas')
                )
                ->first()?->plan;
        }

        // Definir si debe mostrarse el brandig de rombi
        $showRombiBranding = true;

        if ($appointmentsPlan) {

            $showRombiBranding = !in_array(
                $appointmentsPlan->key,
                [
                    //'pro',
                    'premium',
                    'founder',
                ]
            );
        }


        // Data 
        $businessName = $organization?->name ?? 'Mi Negocio';

        $primaryColor = $organization?->primary_color ?? '#10b981';

        $logoUrl =
            $organization?->logo_url
            ? url(Storage::url($organization->logo_url))
            : null;

        $website =
            $branch?->website
            ?? $organization?->website;

        $email =
            $branch?->email
            ?? $organization?->email;

        $phone =
            $branch?->phone['internationalNumber']
            ?? $organization?->phone['internationalNumber']
            ?? null;

        $whatsapp =
            $branch?->whatsapp_phone['e164Number']
            ?? null;

        $mapsUrl = $branch
            ? app(LocationService::class)
            ->buildGoogleMapsUrl($branch)
            : null;

        $socialLinks =
            $branch?->social_links
            ?? [];

        // Flags de visibilidad
        $showPhone =
            $branch?->show_phone ?? true;

        $showEmail =
            $branch?->show_email ?? true;

        $showWebsite =
            $branch?->show_website ?? true;

        $showWhatsapp =
            $branch?->show_whatsapp ?? false;

        $showAddress =
            $branch?->show_address ?? true;

        $showSocialLinks =
            $branch?->show_social_links ?? true;

        // Footer text   
        $rombiUrl = config('services.rombi.front_principal_url')
            . '?utm_source=rombi_mail&utm_medium=powered_by&utm_campaign=business_footer';

        $footerText = '';
        if ($showRombiBranding) {
            $footerText = '
<div
    style="
        text-align:center;
        color:#999;
    "
>

    <div
        style="
            font-size:11px;
            letter-spacing:.5px;
            text-transform:uppercase;
            margin-bottom:6px;
        "
    >
        Potenciado por
    </div>

    <a
        href="' . $rombiUrl . '"
        style="
            text-decoration:none;
            font-weight:800;
            font-size:18px;
            color:#10b981;
            letter-spacing:.5px;
        "
    >
        RO<span style="color:#10b981;">M</span>BI
    </a>

    <div
        style="
            font-size:11px;
            color:#aaa;
            margin-top:4px;
        "
    >
        La forma inteligente de agendar
    </div>

</div>
';
        }



        $socialNetworks = SocialNetwork::all();

        // Construir bloque de contacto
        $contactItems = [];

        if ($showWebsite && $website) {
            $contactItems[] = '
<a
    href="' . e($website) . '"
    style="
        display:inline-block;
        padding:8px 12px;
        border-radius:20px;
        background:#f5f5f5;
        color:#444;
        text-decoration:none;
        font-size:13px;
        margin:3px;
    "
>
    🌐 Sitio web
</a>
';
        }

        if ($showEmail && $email) {
            $contactItems[] = '
<a
    href="mailto:' . e($email) . '"
    style="
        display:inline-block;
        padding:8px 12px;
        border-radius:20px;
        background:#f5f5f5;
        color:#444;
        text-decoration:none;
        font-size:13px;
        margin:3px;
    "
>
    ✉️ Correo
</a>
';
        }

        if ($showPhone && $phone) {
            $contactItems[] = '
<a
    href="tel:' . preg_replace('/\D/', '', $phone) . '"
    style="
        display:inline-block;
        padding:8px 12px;
        border-radius:20px;
        background:#f5f5f5;
        color:#444;
        text-decoration:none;
        font-size:13px;
        margin:3px;
    "
>
    📞 Teléfono
</a>
';
        }

        if ($showWhatsapp && $whatsapp) {
            $contactItems[] = '
<a
    href="https://wa.me/' . preg_replace('/\D/', '', $whatsapp) . '"
    style="
        display:inline-block;
        padding:8px 12px;
        border-radius:20px;
        background:#ecfdf5;
        color:#15803d;
        text-decoration:none;
        font-size:13px;
        margin:3px;
        font-weight:600;
    "
>
    <img
        src="https://cdn-icons-png.flaticon.com/24/733/733585.png"
        width="14"
        height="14"
        style="
            vertical-align:middle;
            margin-right:4px;
        "
    >
    WhatsApp
</a>
';
        }

        if ($showAddress && $mapsUrl) {
            $contactItems[] = '
<a
    href="' . e($mapsUrl) . '"
    style="
        display:inline-block;
        padding:8px 12px;
        border-radius:20px;
        background:#f5f5f5;
        color:#444;
        text-decoration:none;
        font-size:13px;
        margin:3px;
    "
>
    📍 Ubicación
</a>
';
        }

        $contactLinksHtml = implode('', $contactItems);

        // Construir HTML de redes
        $socialHtml = '';

        if (
            $showSocialLinks &&
            !empty($socialLinks)
        ) {

            foreach ($socialLinks as $network => $url) {

                if (
                    empty($url)
                    || !isset($socialNetworks[$network])
                ) {
                    continue;
                }

                $socialHtml .= '
            <td style="padding:0 6px;">
                <a href="' . e($url) . '">
                    <img
                        src="' . $socialNetworks[$network]['icon'] . '"
                        width="24"
                        height="24"
                        style="display:block;"
                    >
                </a>
            </td>
        ';
            }
        }


        $header = $logoUrl
            ? '
                <img
                    src="' . $logoUrl . '"
                    alt="' . e($businessName) . '"
                    style="max-height:80px;max-width:220px;"
                >
            '
            : '
                <div
                    style="
                        color:#ffffff;
                        font-size:24px;
                        font-weight:bold;
                    "
                >
                    ' . e($businessName) . '
                </div>
            ';

        return '
        <html>
        <body
            style="
                margin:0;
                padding:0;
                background:#f4f4f4;
                font-family:Arial, sans-serif;
            "
        >

        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="
                background:#f4f4f4;
                padding:30px 0;
            "
        >
            <tr>
                <td align="center">

                    <table
                        width="600"
                        cellpadding="0"
                        cellspacing="0"
                        style="
                            background:#ffffff;
                            border-radius:12px;
                            overflow:hidden;
                        "
                    >

                        <!-- HEADER -->
                        <tr>
                            <td
                                align="center"
                                style="
                                    background:' . $primaryColor . ';
                                    padding:24px;
                                "
                            >
                                ' . $header . '
                            </td>
                        </tr>

                        <!-- BODY -->
                        <tr>
                            <td
                                style="
                                    padding:32px;
                                    color:#333333;
                                    font-size:16px;
                                    line-height:1.6;
                                "
                            >
                                ' . $content . '
                            </td>
                        </tr>

                        <!-- FOOTER -->
                        <tr>
                            <td
                                style="
                                    padding:24px;
                                    text-align:center;
                                    font-size:12px;
                                    color:#888888;
                                    border-top:1px solid #eeeeee;
                                "
                            >


<div
    style="
        margin-top:12px;
        font-size:13px;
        line-height:1.8;
    "
>
    ' . $contactLinksHtml . '
</div>' .

            ($socialHtml
                ? '
    <br><br>

    <table
        cellpadding="0"
        cellspacing="0"
        align="center"
    >
        <tr>
            ' . $socialHtml . '
        </tr>
    </table>
'
                : '') . '

<hr
    style="
        border:none;
        border-top:1px solid #f0f0f0;
        margin:24px 0 18px 0;
    "
>

' . $footerText . '

                               

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
