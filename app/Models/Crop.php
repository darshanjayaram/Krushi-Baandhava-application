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
        'market_radius_km',
        'default_market_sort',
        'allow_user_sort_toggle',
        'enable_smart_badges',
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
        'market_radius_km' => 'integer',
        'allow_user_sort_toggle' => 'boolean',
        'enable_smart_badges' => 'boolean',
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

    public function sourceMappings(): HasMany
    {
        return $this->hasMany(CropSourceMapping::class);
    }

    /**
     * Get real photo URL for this commodity.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (!empty($this->icon)) {
            $icon = trim($this->icon);
            if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://')) {
                return $icon;
            }
            if (file_exists(public_path($icon))) {
                return asset($icon);
            }
            if (file_exists(public_path('uploads/crops/' . ltrim($icon, '/')))) {
                return asset('uploads/crops/' . ltrim($icon, '/'));
            }
            if (file_exists(public_path('images/crops/' . ltrim($icon, '/')))) {
                return asset('images/crops/' . ltrim($icon, '/'));
            }
        }

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
            'jowar' => 'jowar.jpg',
            'tur' => 'tur.jpg',
            'green-chilli' => 'green_chilli.jpg',
            'groundnut' => 'groundnut.jpg',
            'sunflower' => 'sunflower.jpg',
            'cotton' => 'cotton.jpg',
        ];

        $file = $map[$this->slug] ?? null;

        if ($file && file_exists(public_path('images/crops/' . $file))) {
            return asset('images/crops/' . $file);
        }

        if (file_exists(public_path('images/crops/' . $this->slug . '.jpg'))) {
            return asset('images/crops/' . $this->slug . '.jpg');
        }

        return asset('images/crops/arecanut.jpg');
    }

    /**
     * Get localized crop display name based on current locale.
     */
    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'en') {
            return $this->name;
        }
        return $this->name_kn ?: $this->name;
    }
}
