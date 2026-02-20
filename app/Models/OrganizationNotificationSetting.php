<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationNotificationSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'notification_to',
        'notification_cc',
        'notification_bcc',
        'auto_reply_enabled',
        'emergency_footer_enabled',
        'office_hours',
    ];

    protected $casts = [
        'notification_to' => 'array',
        'notification_cc' => 'array',
        'notification_bcc' => 'array',
        'office_hours' => 'array',
        'auto_reply_enabled' => 'boolean',
        'emergency_footer_enabled' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
