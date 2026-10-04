<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'type',
        'title_kn',
        'title_en',
        'body_kn',
        'body_en',
        'target_url',
        'status',
        'total_recipients',
        'success_count',
        'failure_count',
        'error_details',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
