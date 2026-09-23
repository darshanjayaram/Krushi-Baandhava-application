<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source_id',
        'started_at',
        'completed_at',
        'duration_ms',
        'records_received',
        'records_inserted',
        'records_updated',
        'records_duplicate',
        'records_rejected',
        'error_count',
        'status',
        'error_message',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_ms' => 'integer',
            'records_received' => 'integer',
            'records_inserted' => 'integer',
            'records_updated' => 'integer',
            'records_duplicate' => 'integer',
            'records_rejected' => 'integer',
            'error_count' => 'integer',
            'details' => 'array',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }
}
