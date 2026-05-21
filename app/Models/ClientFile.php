<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
Ejemplo de uso:
Subir archivo
$path = $file->store(
    'clients/' . $client->id,
    'public'
);

$client->files()->create([
    'organization_id' => $client->organization_id,
    'uploaded_by' => auth()->id(),

    'name' => 'Consentimiento firmado',
    'original_name' => $file->getClientOriginalName(),

    'path' => $path,
    'disk' => 'public',

    'mime_type' => $file->getMimeType(),
    'extension' => $file->extension(),
    'size' => $file->getSize(),

    'category' => 'consent',
]);*/

class ClientFile extends Model
{
    protected $fillable = [
        'organization_id',
        'client_id',
        'appointment_id',
        'uploaded_by',

        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'extension',
        'size',

        'category',
        'is_private',
        'visible_to_client',

        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'visible_to_client' => 'boolean',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
