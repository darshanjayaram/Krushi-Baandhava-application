<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'title',
        'title_kn',
        'slug',
        'category',
        'summary',
        'summary_kn',
        'body',
        'body_kn',
        'featured_image',
        'author_name',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if (empty($article->published_at)) {
                $article->published_at = now();
            }
        });
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getCategoryLabelKnAttribute(): string
    {
        return match ($this->category) {
            'cultivation' => 'ಬೇಸಾಯ ಕ್ರಮಗಳು (Cultivation)',
            'pest_control' => 'ಕೀಟ ಹಾಗೂ ರೋಗ ಬಾಧೆ (Pest & Disease)',
            'soil_fertilizer' => 'ಮಣ್ಣು & ರಸಗೊಬ್ಬರ (Soil & Fertilizer)',
            'harvest_storage' => 'ಕೊಯ್ಲು & ಸಂಗ್ರಹಣೆ (Harvest & Storage)',
            default => 'ಕೃಷಿ ಮಾಹಿತಿ (Agri Guide)',
        };
    }

    public function getCategoryLabelEnAttribute(): string
    {
        return match ($this->category) {
            'cultivation' => 'Cultivation Practices',
            'pest_control' => 'Pest & Disease Control',
            'soil_fertilizer' => 'Soil & Fertilizer',
            'harvest_storage' => 'Harvest & Storage',
            default => 'Agri Guide',
        };
    }
}
