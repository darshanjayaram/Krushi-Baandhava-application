<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketPriceRaw extends Model
{
    use HasFactory;

    protected $table = 'market_price_raw';

    protected $fillable = [
        'data_source_id',
        'external_record_id',
        'payload',
        'checksum',
        'received_at',
        'processed_at',
        'processing_status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function canonicalPrice(): HasOne
    {
        return $this->hasOne(MarketPrice::class, 'raw_record_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeDuplicate(Builder $query): Builder
    {
        return $query->where('processing_status', 'duplicate');
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('processing_status', 'processed');
    }
}
