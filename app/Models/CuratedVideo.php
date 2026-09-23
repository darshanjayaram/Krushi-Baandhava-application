<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuratedVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_kn',
        'youtube_video_id',
        'youtube_url',
        'crop_id',
        'category',
        'channel_name',
        'duration_text',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return "https://img.youtube.com/vi/{$this->youtube_video_id}/hqdefault.jpg";
    }

    public function getEmbedUrlAttribute(): string
    {
        return "https://www.youtube.com/embed/{$this->youtube_video_id}?autoplay=1&rel=0";
    }

    /**
     * Extract clean YouTube video ID from any YouTube URL format.
     */
    public static function extractYoutubeId(string $url): ?string
    {
        $pattern = '%^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$%x';
        if (preg_match($pattern, trim($url), $matches)) {
            return $matches[1];
        }

        // If user already typed the raw 11-char ID
        if (strlen(trim($url)) === 11 && !str_contains($url, '/')) {
            return trim($url);
        }

        return null;
    }
}
