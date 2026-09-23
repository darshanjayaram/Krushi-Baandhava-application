<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropSourceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source_id',
        'source_crop_name',
        'source_variety_name',
        'crop_id',
        'crop_variety_id',
        'confidence_score',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(CropVariety::class, 'crop_variety_id');
    }
}
