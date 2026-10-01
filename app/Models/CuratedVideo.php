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
        'growth_stage',
        'language',
        'channel_name',
        'duration_text',
        'display_order',
        'is_active',
        'is_featured',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
        'views_count' => 'integer',
    ];

    public const GROWTH_STAGES = [
        'general' => ['name_en' => 'General Guide', 'name_kn' => 'ಸಾಮಾನ್ಯ ಮಾರ್ಗದರ್ಶಿ', 'icon' => '📌'],
        'nursery' => ['name_en' => 'Nursery & Sowing', 'name_kn' => 'ಬಿತ್ತನೆ & ಸಸಿ ಮಡಿ', 'icon' => '🌱'],
        'vegetative' => ['name_en' => 'Growth & Nutrition', 'name_kn' => 'ಬೆಳವಣಿಗೆ & ಪೋಷಕಾಂಶ', 'icon' => '🌿'],
        'pest_control' => ['name_en' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ & ರೋಗ ನಿರ್ವಹಣೆ', 'icon' => '🐛'],
        'irrigation' => ['name_en' => 'Irrigation & Water Mgmt', 'name_kn' => 'ನೀರಾವರಿ ನಿರ್ವಹಣೆ', 'icon' => '💧'],
        'harvest' => ['name_en' => 'Harvesting & Picking', 'name_kn' => 'ಕೊಯ್ಲು & ಕಟಾವು', 'icon' => '✂️'],
        'post_harvest' => ['name_en' => 'Storage & Value Addition', 'name_kn' => 'ಸಂಸ್ಕರಣೆ & ಮೌಲ್ಯವರ್ಧನೆ', 'icon' => '📦'],
    ];

    public const CATEGORIES = [
        'cultivation' => ['name_en' => 'Cultivation Techniques', 'name_kn' => 'ಬೇಸಾಯ ಪದ್ಧತಿ', 'icon' => '🌾'],
        'pest_control' => ['name_en' => 'Pest & Disease Control', 'name_kn' => 'ಕೀಟ ಹಾಗೂ ರೋಗ ನಿರ್ವಹಣೆ', 'icon' => '🐛'],
        'organic' => ['name_en' => 'Organic & Natural Farming', 'name_kn' => 'ಸಾವಯವ & ನೈಸರ್ಗಿಕ ಕೃಷಿ', 'icon' => '🍃'],
        'machinery' => ['name_en' => 'Farm Machinery & Drones', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜'],
        'success_story' => ['name_en' => 'Farmer Success Story', 'name_kn' => 'ರೈತರ ಯಶೋಗಾಥೆ', 'icon' => '🏆'],
    ];

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCrop(Builder $query, $cropId): Builder
    {
        return $query->where('crop_id', $cropId);
    }

    public function scopeByStage(Builder $query, string $stage): Builder
    {
        return $query->where('growth_stage', $stage);
    }

    public static function getCategories(): array
    {
        try {
            $taxonomies = VideoTaxonomy::category()->active()->ordered()->get();
            if ($taxonomies->isNotEmpty()) {
                $result = [];
                foreach ($taxonomies as $t) {
                    $result[$t->slug] = [
                        'name_en' => $t->name,
                        'name_kn' => $t->name_kn ?: $t->name,
                        'icon' => $t->icon ?: '🌾',
                    ];
                }
                return $result;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return self::CATEGORIES;
    }

    public static function getGrowthStages(): array
    {
        try {
            $taxonomies = VideoTaxonomy::growthStage()->active()->ordered()->get();
            if ($taxonomies->isNotEmpty()) {
                $result = [];
                foreach ($taxonomies as $t) {
                    $result[$t->slug] = [
                        'name_en' => $t->name,
                        'name_kn' => $t->name_kn ?: $t->name,
                        'icon' => $t->icon ?: '📌',
                    ];
                }
                return $result;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return self::GROWTH_STAGES;
    }

    public function getGrowthStageName(string $locale = 'kn'): string
    {
        $stages = self::getGrowthStages();
        $stage = $stages[$this->growth_stage] ?? ($stages['general'] ?? ['name_en' => ucfirst($this->growth_stage), 'name_kn' => ucfirst($this->growth_stage)]);
        return $locale === 'kn' ? ($stage['name_kn'] ?? $stage['name_en']) : $stage['name_en'];
    }

    public function getCategoryName(string $locale = 'kn'): string
    {
        $categories = self::getCategories();
        $cat = $categories[$this->category] ?? null;
        if (!$cat) {
            return ucfirst($this->category);
        }
        return $locale === 'kn' ? ($cat['name_kn'] ?? $cat['name_en']) : $cat['name_en'];
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
