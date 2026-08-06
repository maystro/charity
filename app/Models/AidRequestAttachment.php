<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AidRequestAttachment extends Model
{
    protected $fillable = [
        'aid_request_id',
        'aid_request_item_id',
        'attachment_type_id',
        'original_name',
        'stored_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'document_date',
        'expires_at',
        'verification_status',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expires_at' => 'date',
    ];

    public function aidRequest(): BelongsTo
    {
        return $this->belongsTo(AidRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AidRequestItem::class, 'aid_request_item_id');
    }
}
