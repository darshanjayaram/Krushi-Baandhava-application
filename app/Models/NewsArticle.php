<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'headline',
        'headline_kn',
        'title',
        'title_kn',
        'slug',
        'summary',
        'summary_kn',
        'content',
        'content_kn',
        'body',
        'body_kn',
        'source_name',
        'source_url',
        'priority',
        'image_url',
        'published_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (NewsArticle $news) {
            if (empty($news->headline) && !empty($news->attributes['title'])) {
                $news->headline = $news->attributes['title'];
            }
            if (empty($news->headline_kn) && !empty($news->attributes['title_kn'])) {
                $news->headline_kn = $news->attributes['title_kn'];
            }
            if (empty($news->content) && !empty($news->attributes['body'])) {
                $news->content = $news->attributes['body'];
            }
            if (empty($news->content_kn) && !empty($news->attributes['body_kn'])) {
                $news->content_kn = $news->attributes['body_kn'];
            }
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->headline ?? $news->headline_kn ?? Str::random(8));
            }
            if (empty($news->published_at)) {
                $news->published_at = now();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('priority', 'breaking');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    // Title alias accessors / mutators
    public function getTitleAttribute(): ?string
    {
        return $this->headline;
    }

    public function setTitleAttribute(?string $value): void
    {
        $this->attributes['headline'] = $value;
    }

    public function getTitleKnAttribute(): ?string
    {
        return $this->headline_kn;
    }

    public function setTitleKnAttribute(?string $value): void
    {
        $this->attributes['headline_kn'] = $value;
    }

    // Body alias accessors / mutators
    public function getBodyAttribute(): ?string
    {
        return $this->content;
    }

    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['content'] = $value;
    }

    public function getBodyKnAttribute(): ?string
    {
        return $this->content_kn;
    }

    public function setBodyKnAttribute(?string $value): void
    {
        $this->attributes['content_kn'] = $value;
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'breaking' => 'bg-red-100 text-red-800 border border-red-200',
            'high', 'important' => 'bg-amber-100 text-amber-800 border border-amber-200',
            default => 'bg-stone-100 text-stone-700 border border-stone-200',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'breaking' => '🚨 ಬ್ರೇಕಿಂಗ್ (Breaking)',
            'high', 'important' => '⚡ ಪ್ರಮುಖ (Important)',
            default => 'ಸಾಮಾನ್ಯ (General)',
        };
    }
}
