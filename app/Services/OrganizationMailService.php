<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use App\Models\OrganizationMailTemplate;
use App\Models\OrganizationMailSetting;

class OrganizationMailService
{
    /**
     * Obtiene los mail settings activos ordenados por prioridad
     */
    protected function getActiveMailers(Organization $organization)
    {
        return $organization->mailSettings()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Configura dinámicamente el mailer
     */
    protected function configureMailer(OrganizationMailSetting $settings): void
    {
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

    /**
     * Envía correo basado en plantilla con fallback automático
     */
    public function sendTemplate(
        Organization $organization,
        string $type,
        array|string|null $to = null,
        array $variables = [],
        bool $applyNotificationRecipients = false
    ): void {

        $notificationSettings = $organization->notificationSetting;
        $to = $to
            ?? $notificationSettings?->notification_to
            ?? [$organization->email];

        $bcc = $notificationSettings?->notification_bcc ?? [];
        $cc  = $notificationSettings?->notification_cc ?? [];

        $template = OrganizationMailTemplate::where(function ($query) use ($organization) {
            $query->where('organization_id', $organization->id)
                ->orWhereNull('organization_id');
        })
            ->where('type', $type)
            ->where('is_active', true)
            ->orderByRaw('organization_id IS NULL') // prioriza el específico
            ->first();

        if (!$template) {
            throw new \Exception("Mail template '{$type}' not found for organization {$organization->slug}.");
        }

        $subject  = $this->parseTemplate($template->subject, $variables);
        $bodyHtml = $this->parseTemplate($template->body_html ?? '', $variables);
        $bodyText = $this->parseTemplate($template->body_text ?? '', $variables) ?: strip_tags($bodyHtml);

        $mailers = $this->getActiveMailers($organization);

        if ($mailers->isEmpty()) {
            throw new \Exception("No active mail settings found for organization {$organization->slug}.");
        }

        $lastException = null;

        foreach ($mailers as $settings) {

            try {

                $this->configureMailer($settings);

                $to = $to
                    ?? $notificationSettings?->notification_to
                    ?? [$organization->email];

                // Normalizar SIEMPRE a array
                $to = is_array($to) ? $to : [$to];
                $to = array_filter($to);

                $mail = Mail::mailer('dynamic')->to($to);

                if ($applyNotificationRecipients) {
                    $cc = is_array($cc) ? $cc : [$cc];
                    $bcc = is_array($bcc) ? $bcc : [$bcc];
                    
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
}
