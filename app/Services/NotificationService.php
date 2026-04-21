<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRule;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

use App\Services\MailLayouts\CitaraLayout;
use App\Jobs\SendNotificationJob;


class NotificationService
{
    public function __construct(
        protected OrganizationMailService $mailService,
        protected SubsystemResolver $subsystemResolver
    ) {}

    public function trigger(
        string $type,
        array $data,
        ?Organization $organization, // Acepta null para envios/notificaciones de CITARA
        ?string $recipient,
        ?string $recipientName = null,
        $notifiable = null,
        ?string $subsystemCode = null,
        bool $applyNotificationRecipients = false
    ): void {

        $rule = $this->resolveRule($type, $organization);

        if (!$rule || !$rule->is_enabled) {
            return;
        }


        $subsystemId = $this->subsystemResolver->resolve($subsystemCode);
        if ($subsystemCode && !$subsystemId) {
            throw new \Exception("Subsystem '{$subsystemCode}' not found or inactive.");
        }



        $notification = new Notification([
            'organization_id' => $organization?->id,
            'subsystem_id'    => $subsystemId,

            'type'            => $type,
            'event_key'       => $this->buildEventKey($type, $notifiable),

            'channel'         => 'email',

            'recipient'       => $recipient,
            'recipient_name'  => $recipientName,

            'template'        => $type,

            'payload' => array_merge($data, [
                '_apply_notification_recipients' => $applyNotificationRecipients
            ]),

            'status'       => 'pending',
            'scheduled_at' => now()->addMinutes($rule->delay_minutes ?? 0),
        ]);

        if ($notifiable) {
            $notification->notifiable()->associate($notifiable);
        }
        $notification->save();

        // 2. Envío inmediato (MVP)
        SendNotificationJob::dispatch($notification->id)
            ->delay($notification->scheduled_at);
    }

    protected function buildEventKey(string $type, $notifiable = null): ?string
    {
        if (!$notifiable) {
            return null;
        }

        return strtolower(class_basename($notifiable)) . "_{$notifiable->id}_{$type}";
    }

    protected function resolveRule(string $type, ?Organization $organization): ?NotificationRule
    {
        return NotificationRule::where(function ($query) use ($organization) {

            if ($organization) {
                $query->where('organization_id', $organization->id)
                    ->orWhereNull('organization_id'); // 🔥 FIX
            } else {
                $query->whereNull('organization_id'); // 🔥 FIX
            }
        })
            ->where('type', $type)
            ->where('channel', 'email')
            ->where('recipient_type', 'default')
            ->when($organization, function ($query) use ($organization) {
                $query->orderByRaw('organization_id = ? DESC', [$organization->id]);
            })
            ->first();
    }
}
