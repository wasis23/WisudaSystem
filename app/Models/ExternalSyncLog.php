<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSyncLog extends Model
{
    protected $table = 'external_sync_logs';

    protected $fillable = [
        'source', 'action',
        'records_fetched', 'records_inserted', 'records_updated',
        'status', 'notes', 'filter_params', 'triggered_by',
    ];

    protected $casts = [
        'filter_params' => 'array',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }
}
