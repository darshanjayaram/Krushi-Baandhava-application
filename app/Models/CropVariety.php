<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropVariety extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'name',
        'name_kn',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
