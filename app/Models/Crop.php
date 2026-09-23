<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'name_kn',
        'slug',
        'scientific_name',
        'standard_unit',
        'icon',
        'description',
        'is_major',
        'is_active',
    ];

    protected $casts = [
        'is_major' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CropCategory::class, 'category_id');
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(CropVariety::class)->orderBy('name');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MarketPrice::class)->orderBy('price_date', 'desc');
    }
}
