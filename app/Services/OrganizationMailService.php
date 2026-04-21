<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use App\Models\NotificationTemplate;
use App\Models\OrganizationMailSetting;
use App\Services\MailLayouts\CitaraLayout;

class OrganizationMailService
{
    /**
     * Obtiene los mail settings activos ordenados por prioridad
     */
    /*protected function getActiveMailers(Organization $organization)
    {
        return $organization->mailSettings()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }*/

    protected function getActiveMailers(Organization $organization)
    {
        $mailers = $organization->mailSettings()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        // SI NO TIENE CONFIGURACIÓN USAR GLOBAL
        if ($mailers->isEmpty()) {
            return collect([
                (object)[
                    'provider' => 'global',
                    'mailer' => 'smtp',
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username'),
                    'password' => config('mail.mailers.smtp.password'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ]
            ]);
        }

        return $mailers;
    }

    /**
     * Configura dinámicamente el mailer
     */
    /*protected function configureMailer(OrganizationMailSetting $settings): void
    {
        if (!isset($settings->host)) {
            throw new \Exception('Invalid mail configuration');
        }

        Config::set('mail.mailers.dynamic', [
            'transport'  => 'smtp',
            'host'       => $settings->host,
            'port'       => $settings->port,
            'encryption' => $settings->encryption,
            'username'   => $settings->username,
            'password'   => $settings->password,
            'timeout'    => null,
        ]);

        Config::set('mail.from.address', $settings->from_address);
        Config::set('mail.from.name', $settings->from_name);
    }*/

    protected function configureMailer($settings): void
    {
        $required = ['host', 'port', 'username', 'password'];

        foreach ($required as $field) {
            if (!isset($settings->$field)) {
                throw new \Exception("Missing mail config field: {$field}");
            }
        }

        Config::set('mail.mailers.dynamic', [
            'transport'  => 'smtp',
            'host'       => $settings->host,
            'port'       => $settings->port,
            'encryption' => $settings->encryption ?? 'tls',
            'username'   => $settings->username,
            'password'   => $settings->password,
            'timeout'    => null,
        ]);

        Config::set('mail.from.address', $settings->from_address ?? config('mail.from.address'));
        Config::set('mail.from.name', $settings->from_name ?? config('mail.from.name'));
    }

    /**
     * Reemplaza variables tipo {{variable}}
     */
    protected function parseTemplate(string $content, array $variables): string
    {
        // Condicionales tipo {{#var}} ... {{/var}}
        $content = preg_replace_callback('/{{#(.*?)}}(.*?){{\/\1}}/s', function ($matches) use ($variables) {

            $key = trim($matches[1]);
            $inner = $matches[2];

            if (!empty($variables[$key])) {
                return $inner;
            }

            return '';
        }, $content);

        // Reemplazo normal
        $content = preg_replace_callback('/{{\s*(.*?)\s*}}/', function ($matches) use ($variables) {

            $key = $matches[1];

            if (!array_key_exists($key, $variables)) {
                return '';
            }

            $value = $variables[$key];

            if (is_array($value)) {
                return implode(', ', $value);
            }

            if (!is_scalar($value)) {
                return '';
            }

            return (string) $value;
        }, $content);

        return $content;
    }

    protected function applyLayout($template, string $body): string
    {
        if (empty($template->layout_type)) {
            return $body;
        }

        $layouts = [
            'citara' => \App\Services\MailLayouts\CitaraLayout::class,
            //'pdc' => \App\Services\MailLayouts\PdcLayout::class,
            //'general' => \App\Services\MailLayouts\GeneralLayout::class,
        ];

        $layoutClass = $layouts[$template->layout_type] ?? null;

        if (!$layoutClass) {
            return $body;
        }

        return $layoutClass::render($body);
    }

    /**
     * Envía correo basado en plantilla con fallback automático
     */
    public function sendTemplate_BKP(
        ?Organization $organization,
        string $type,
        array|string|null $to = null,
        array $variables = [],
        bool $applyNotificationRecipients = false
    ): void {

        $isInternal = is_null($organization);

        $notificationSettings = $organization->notificationSetting;

        if ($applyNotificationRecipients) {
            $to = $notificationSettings?->notification_to
                ?? [$organization->email];
        } else {
            $to = $to ?? [$organization->email];
        }

        $bcc = $notificationSettings?->notification_bcc ?? [];
        $cc  = $notificationSettings?->notification_cc ?? [];

        if (empty($to)) {
            throw new \Exception("No recipients defined for notification '{$type}'");
        }

        //$template = OrganizationMailTemplate::where(function ($query) use ($organization) {
        $template = NotificationTemplate::where(function ($query) use ($organization) {
            $query->where('organization_id', $organization->id)
                ->orWhereNull('organization_id');
        })
            ->where('type', $type)
            ->where('channel', 'email')
            ->where('is_active', true)
            ->orderByRaw('organization_id IS NULL') // prioriza el específico
            ->first();

        if (!$template) {
            throw new \Exception("Mail template '{$type}' not found for organization {$organization->slug}.");
        }

        $subject  = $this->parseTemplate($template->subject, $variables);

        //$bodyHtml = $this->parseTemplate($template->body ?? '', $variables);
        //$bodyText = $this->parseTemplate($template->body_text ?? '', $variables) ?: strip_tags($bodyHtml);


        // rombi implemantacion para unificar templates
        $bodyHtml = $this->parseTemplate($template->body ?? '', $variables);
        $bodyText = $this->parseTemplate($template->body_text ?? '', $variables) ?: strip_tags($bodyHtml);
        $bodyHtml = $this->applyLayout($template, $bodyHtml);



        $mailers = $this->getActiveMailers($organization);

        if ($mailers->isEmpty()) {
            throw new \Exception("No active mail settings found for organization {$organization->slug}.");
        }

        $lastException = null;

        foreach ($mailers as $settings) {

            try {

                $this->configureMailer($settings);

                /*$to = $to
                    ?? $notificationSettings?->notification_to
                    ?? [$organization->email];*/

                // Normalizar SIEMPRE a array
                $to = is_array($to) ? $to : [$to];
                $to = array_filter($to);
                $to = array_unique($to);

                $mail = Mail::mailer('dynamic')->to($to);

                if ($applyNotificationRecipients) {
                    $cc = is_array($cc) ? $cc : [$cc];
                    $cc = array_filter($cc);
                    $cc = array_unique($cc);

                    $bcc = is_array($bcc) ? $bcc : [$bcc];
                    $bcc = array_filter($bcc);
                    $bcc = array_unique($bcc);

                    $mail->cc($cc);
                    $mail->bcc($bcc);
                }

                $replyToEmail = null;
                if ($applyNotificationRecipients && !empty($variables['email'])) {
                    $replyToEmail = $variables['email'];
                }

                $mail->send(new class($subject, $bodyHtml, $bodyText, $replyToEmail) extends \Illuminate\Mail\Mailable {

                    public $subjectLine;
                    public $htmlContent;
                    public $textContent;
                    public $replyToEmail;

                    public function __construct($subject, $html, $text, $replyToEmail = null)
                    {
                        $this->subjectLine = $subject;
                        $this->htmlContent = $html;
                        $this->textContent = $text;
                        $this->replyToEmail = $replyToEmail;
                    }

                    public function build()
                    {
                        $this->subject($this->subjectLine)
                            ->html($this->htmlContent)
                            ->text('emails.raw-text', [
                                'textContent' => $this->textContent
                            ]);

                        if (!empty($this->replyToEmail)) {
                            $this->replyTo($this->replyToEmail);
                        }
                        return $this;
                    }
                });

                // Si llega aquí, se envió correctamente
                Log::info(
                    "Mail sent successfully using provider '{$settings->provider}' for organization '{$organization->slug}' to: "
                        . implode(', ', $to)
                );

                return;
            } catch (\Exception $e) {

                Log::warning("Mail failed using provider '{$settings->provider}' for organization '{$organization->slug}': " . $e->getMessage());

                $lastException = $e;

                // Intenta con el siguiente provider
            }
        }

        // Si todos fallaron
        throw $lastException ?? new \Exception("All mail providers failed for organization {$organization->slug}.");
    }

    public function sendTemplate(
        ?Organization $organization,
        string $type,
        array|string|null $to = null,
        array $variables = [],
        bool $applyNotificationRecipients = false
    ): void {

        $isInternal = is_null($organization);
        $orgLabel = $organization?->slug ?? 'system';

        // 🔹 Notification settings (solo si hay organization)
        $notificationSettings = $organization?->notificationSetting;

        // 🔹 Recipients
        if ($isInternal) {
            $to = is_array($to) ? $to : [$to];
        } else {
            if ($applyNotificationRecipients) {
                $to = $notificationSettings?->notification_to
                    ?? [$organization->email];
            } else {
                $to = $to ?? [$organization->email];
            }
        }

        $to = is_array($to) ? $to : [$to];
        $to = array_filter($to);
        $to = array_unique($to);

        if (empty($to)) {
            throw new \Exception("No recipients defined for notification '{$type}'");
        }

        // 🔹 CC / BCC (solo aplican si hay organization y flag activo)
        $cc = [];
        $bcc = [];

        if (!$isInternal && $applyNotificationRecipients) {
            $cc = is_array($notificationSettings?->notification_cc)
                ? $notificationSettings->notification_cc
                : [$notificationSettings?->notification_cc];

            $bcc = is_array($notificationSettings?->notification_bcc)
                ? $notificationSettings->notification_bcc
                : [$notificationSettings?->notification_bcc];

            $cc = array_filter($cc);
            $bcc = array_filter($bcc);
        }

        // 🔹 Template
        $template = NotificationTemplate::where(function ($query) use ($organization, $isInternal) {

            if ($isInternal) {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $organization->id)
                    ->orWhereNull('organization_id');
            }
        })
            ->where('type', $type)
            ->where('channel', 'email')
            ->where('is_active', true)
            ->orderByRaw('organization_id IS NULL') // prioriza el específico
            ->first();

        if (!$template) {
            throw new \Exception(
                $isInternal
                    ? "Mail template '{$type}' not found for system."
                    : "Mail template '{$type}' not found for organization {$organization->slug}."
            );
        }

        // 🔹 Parseo
        $subject  = $this->parseTemplate($template->subject, $variables);

        $bodyHtml = $this->parseTemplate($template->body ?? '', $variables);
        $bodyText = $this->parseTemplate($template->body_text ?? '', $variables) ?: strip_tags($bodyHtml);
        $bodyHtml = $this->applyLayout($template, $bodyHtml);

        // 🔹 Mailers
        $mailers = $isInternal
            ? collect([(object)[
                'provider' => 'global',
                'mailer' => 'smtp',
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ]])
            : $this->getActiveMailers($organization);

        if ($mailers->isEmpty()) {
            throw new \Exception(
                $isInternal
                    ? "No global mail configuration available."
                    : "No active mail settings found for organization {$organization->slug}."
            );
        }

        $lastException = null;

        foreach ($mailers as $settings) {

            try {

                $this->configureMailer($settings);

                $mail = Mail::mailer('dynamic')->to($to);

                if (!empty($cc)) {
                    $mail->cc($cc);
                }

                if (!empty($bcc)) {
                    $mail->bcc($bcc);
                }

                $replyToEmail = null;
                if (!$isInternal && $applyNotificationRecipients && !empty($variables['email'])) {
                    $replyToEmail = $variables['email'];
                }

                $mail->send(new class($subject, $bodyHtml, $bodyText, $replyToEmail) extends \Illuminate\Mail\Mailable {

                    public $subjectLine;
                    public $htmlContent;
                    public $textContent;
                    public $replyToEmail;

                    public function __construct($subject, $html, $text, $replyToEmail = null)
                    {
                        $this->subjectLine = $subject;
                        $this->htmlContent = $html;
                        $this->textContent = $text;
                        $this->replyToEmail = $replyToEmail;
                    }

                    public function build()
                    {
                        $this->subject($this->subjectLine)
                            ->html($this->htmlContent)
                            ->text('emails.raw-text', [
                                'textContent' => $this->textContent
                            ]);

                        if (!empty($this->replyToEmail)) {
                            $this->replyTo($this->replyToEmail);
                        }

                        return $this;
                    }
                });

                Log::info(
                    "Mail sent successfully using provider '{$settings->provider}' for '{$orgLabel}' to: "
                        . implode(', ', $to)
                );

                return;
            } catch (\Exception $e) {

                Log::warning(
                    "Mail failed using provider '{$settings->provider}' for '{$orgLabel}': " . $e->getMessage()
                );

                $lastException = $e;
            }
        }

        throw $lastException ?? new \Exception(
            $isInternal
                ? "All global mail providers failed."
                : "All mail providers failed for organization {$organization->slug}."
        );
    }
}
