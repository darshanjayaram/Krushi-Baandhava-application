<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiHealthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source_id',
        'http_status',
        'response_time_ms',
        'auth_result',
        'records_found',
        'detected_fields',
        'status',
        'error_message',
        'sample_payload',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'response_time_ms' => 'integer',
            'records_found' => 'integer',
            'detected_fields' => 'array',
            'sample_payload' => 'array',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }
}
