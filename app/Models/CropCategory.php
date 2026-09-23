<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_kn',
        'slug',
        'icon',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'category_id')->orderBy('name');
    }
}
