<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;
use App\Models\NotificationRule;

class NotificationRuleSeeder extends Seeder
{
    public function run(): void
    {
        NotificationTemplate::where('is_active', true)
            ->chunk(100, function ($templates) {

                foreach ($templates as $template) {

                    // 🔥 validar lo mínimo necesario
                    if (!$template->type || !$template->channel) {
                        continue;
                    }

                    NotificationRule::updateOrCreate(
                        [
                            'organization_id' => $template->organization_id,
                            'type' => $template->type,
                            'channel' => $template->channel,
                            'recipient_type' => 'default', // 👈 no lo uses, solo para cumplir constraint
                        ],
                        [
                            'is_enabled' => true,
                            'delay_minutes' => 0,
                            'template_id' => $template->id,
                        ]
                    );
                }
            });
    }
}