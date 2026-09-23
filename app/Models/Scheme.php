<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Scheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_kn',
        'slug',
        'category',
        'sponsoring_agency',
        'benefit_amount',
        'benefit_amount_kn',
        'eligibility_criteria',
        'eligibility_criteria_kn',
        'documents_required',
        'official_url',
        'apply_url',
        'icon_emoji',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Scheme $scheme) {
            if (empty($scheme->benefit_amount) && !empty($scheme->attributes['benefits'])) {
                $scheme->benefit_amount = $scheme->attributes['benefits'];
            }
            if (empty($scheme->benefit_amount_kn) && !empty($scheme->attributes['benefits_kn'])) {
                $scheme->benefit_amount_kn = $scheme->attributes['benefits_kn'];
            }
            if (empty($scheme->eligibility_criteria) && !empty($scheme->attributes['eligibility'])) {
                $scheme->eligibility_criteria = $scheme->attributes['eligibility'];
            }
            if (empty($scheme->eligibility_criteria_kn) && !empty($scheme->attributes['eligibility_kn'])) {
                $scheme->eligibility_criteria_kn = $scheme->attributes['eligibility_kn'];
            }
            if (empty($scheme->slug)) {
                $scheme->slug = Str::slug($scheme->title ?? $scheme->title_kn ?? Str::random(8));
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // Benefits alias
    public function getBenefitsAttribute(): ?string
    {
        return $this->benefit_amount;
    }

    public function setBenefitsAttribute(?string $value): void
    {
        $this->attributes['benefit_amount'] = $value;
    }

    public function getBenefitsKnAttribute(): ?string
    {
        return $this->benefit_amount_kn;
    }

    public function setBenefitsKnAttribute(?string $value): void
    {
        $this->attributes['benefit_amount_kn'] = $value;
    }

    // Eligibility alias
    public function getEligibilityAttribute(): ?string
    {
        return $this->eligibility_criteria;
    }

    public function setEligibilityAttribute(?string $value): void
    {
        $this->attributes['eligibility_criteria'] = $value;
    }

    public function getEligibilityKnAttribute(): ?string
    {
        return $this->eligibility_criteria_kn;
    }

    public function setEligibilityKnAttribute(?string $value): void
    {
        $this->attributes['eligibility_criteria_kn'] = $value;
    }

    // Summary alias
    public function getSummaryAttribute(): ?string
    {
        return $this->benefit_amount ?? $this->eligibility_criteria;
    }

    public function getSummaryKnAttribute(): ?string
    {
        return $this->benefit_amount_kn ?? $this->eligibility_criteria_kn;
    }

    // How to apply alias
    public function getHowToApplyAttribute(): ?string
    {
        return $this->apply_url ?? $this->official_url;
    }

    public function getCategoryLabelKnAttribute(): string
    {
        return match ($this->category) {
            'subsidy' => 'ಸಬ್ಸಿಡಿ & ಅನುದಾನ (Subsidies & Grants)',
            'machinery' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ (Machinery)',
            'irrigation' => 'ಸೂಕ್ಷ್ಮ ನೀರಾವರಿ (Irrigation)',
            'insurance' => 'ಬೆಳೆ ವಿಮೆ (Insurance)',
            'organic' => 'ಸಾವಯವ & ಮಣ್ಣು (Organic)',
            default => 'ಸಾಮಾನ್ಯ ಯೋಜನೆ (General)',
        };
    }
}
