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
        'price_source_type',
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

    public function isCoffeeBoard(): bool
    {
        return $this->price_source_type === 'coffee_board' || $this->slug === 'coffee';
    }

    public function isCoconutBoard(): bool
    {
        return $this->price_source_type === 'coconut_board' || in_array($this->slug, ['coconut', 'copra', 'tender-coconut']);
    }

    public function isBoardPriced(): bool
    {
        return $this->isCoffeeBoard() || $this->isCoconutBoard();
    }

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

    /**
     * Get real photo URL for this commodity.
     */
    public function getPhotoUrlAttribute(): string
    {
        $map = [
            'arecanut' => 'arecanut.jpg',
            'coffee' => 'coffee.jpg',
            'coconut' => 'coconut.jpg',
            'copra' => 'copra.jpg',
            'tender-coconut' => 'tender_coconut.jpg',
            'black-pepper' => 'black_pepper.jpg',
            'ginger' => 'ginger.jpg',
            'paddy' => 'paddy.jpg',
            'ragi' => 'ragi.jpg',
            'maize' => 'maize.jpg',
            'onion' => 'onion.jpg',
            'tomato' => 'tomato.jpg',
            'banana' => 'banana.jpg',
            'raw-banana' => 'banana.jpg',
            'jowar' => 'maize.jpg',
            'tur' => 'groundnut.jpg',
            'green-chilli' => 'green_chilli.jpg',
            'groundnut' => 'groundnut.jpg',
            'sunflower' => 'sunflower.jpg',
        ];

        $file = $map[$this->slug] ?? null;

        if ($file && file_exists(public_path('images/crops/' . $file))) {
            return asset('images/crops/' . $file);
        }

        return asset('images/crops/arecanut.jpg');
    }
}
