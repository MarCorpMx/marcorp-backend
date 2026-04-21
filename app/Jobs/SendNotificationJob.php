<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Organization;
use App\Services\OrganizationMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\NotificationTemplate;
use App\Services\MailLayouts\CitaraLayout;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public int $notificationId
    ) {}

    public function handle(OrganizationMailService $mailService): void
    {
        $notification = Notification::find($this->notificationId);

        if (!$notification || $notification->status === 'sent') {
            return;
        }

        $organization = $notification->organization_id
            ? Organization::find($notification->organization_id)
            : null;

        /*if (!$organization) {
            return;
        }*/

        try {

            $notification->increment('attempts');

            $notification->update([
                'status' => 'processing',
            ]);

            $payload = $notification->payload ?? [];

            $apply = $payload['_apply_notification_recipients'] ?? false;

            unset($payload['_apply_notification_recipients']);

            $mailService->sendTemplate(
                $organization,
                $notification->template,
                $notification->recipient,
                $payload,
                $apply
            );

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {

            Log::error("Notification failed", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification) {
            $notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);
        }
    }
}
