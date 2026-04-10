<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

    use ResolvesOrganization;

    public function index(Request $request)
    {
        $organization = $this->getOrganization($request);

        $rules = DB::table('notification_rules as nr')
            ->leftJoin('notification_templates as nt', function ($join) {
                $join->on('nt.id', '=', 'nr.template_id');
            })
            ->where('nr.organization_id', $organization->id)
            ->select(
                'nr.id',
                'nr.type as event',
                'nr.channel',
                'nr.recipient_type',
                'nr.is_enabled as active',
                'nr.delay_minutes',

                'nt.name as template_name'
            )
            ->get();

        // Transformar estructura
        $grouped = [];

        foreach ($rules as $rule) {

            $key = $rule->event . '_' . $rule->channel;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $rule->id,
                    'name' => $rule->template_name ?? $rule->event,
                    'event' => $rule->event,
                    'channel' => $rule->channel,
                    'active' => $rule->active,
                    'delay_minutes' => $rule->delay_minutes,
                    'recipients' => [
                        'client' => false,
                        'staff' => false,
                    ]
                ];
            }

            // Mapear recipient_type → recipients
            if ($rule->recipient_type === 'client') {
                $grouped[$key]['recipients']['client'] = true;
            }

            if ($rule->recipient_type === 'admin') {
                $grouped[$key]['recipients']['staff'] = true;
            }
        }

        return response()->json(array_values($grouped));
    }

    // Activar/Descativar
    public function toggle(Request $request, $event, $channel)
    {
        $organization = $this->getOrganization($request);

        DB::table('notification_rules')
            ->where('organization_id', $organization->id)
            ->where('type', $event)
            ->where('channel', $channel)
            ->update([
                'is_enabled' => $request->active
            ]);

        return response()->json(['success' => true]);
    }

    // Actualizar destinatario
    public function updateRecipients(Request $request, $event, $channel)
    {
        $organization = $this->getOrganization($request);

        $recipients = $request->recipients;

        // CLIENT
        DB::table('notification_rules')
            ->updateOrInsert(
                [
                    'organization_id' => $organization->id,
                    'type' => $event,
                    'channel' => $channel,
                    'recipient_type' => 'client'
                ],
                [
                    'is_enabled' => $recipients['client']
                ]
            );

        // ADMIN (staff)
        DB::table('notification_rules')
            ->updateOrInsert(
                [
                    'organization_id' => $organization->id,
                    'type' => $event,
                    'channel' => $channel,
                    'recipient_type' => 'admin'
                ],
                [
                    'is_enabled' => $recipients['staff']
                ]
            );

        return response()->json(['success' => true]);
    }
}
