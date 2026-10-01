<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VideoTaxonomy extends Model
{
    use HasFactory;

    protected $table = 'video_taxonomies';

    protected $fillable = [
        'type',
        'slug',
        'name',
        'name_kn',
        'icon',
        'description',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query): Builder
    {
        return $query->where('type', 'category');
    }

    public function scopeGrowthStage(Builder $query): Builder
    {
        return $query->where('type', 'growth_stage');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function getDisplayName(string $locale = 'kn'): string
    {
        if ($locale === 'en') {
            return $this->name;
        }

        return $this->name_kn ?: $this->name;
    }

    /**
     * Count how many curated videos are linked to this category or stage.
     */
    public function getVideosCountAttribute(): int
    {
        if ($this->type === 'category') {
            return CuratedVideo::where('category', $this->slug)->count();
        }

        return CuratedVideo::where('growth_stage', $this->slug)->count();
    }
}
