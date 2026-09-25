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

    public function sourceMappings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CropSourceMapping::class, 'crop_variety_id');
    }

    /**
     * Clean English name with any Kannada script in parentheses stripped out.
     */
    public function getCleanNameAttribute(): string
    {
        $raw = $this->name ?? '';
        // Remove Kannada characters in parentheses, e.g. "Rashi (ರಾಶಿ)" -> "Rashi"
        $clean = preg_replace('/\s*\([\x{0C80}-\x{0CFF}\s\/\-\.]+\)/u', '', $raw);
        $clean = trim($clean ?: $raw);
        return $clean !== '' ? $clean : 'Standard';
    }

    /**
     * Clean Kannada name with any Latin characters in parentheses stripped out.
     */
    public function getCleanNameKnAttribute(): string
    {
        $rawKn = trim($this->name_kn ?? '');

        // If name_kn has Kannada inside parentheses e.g. "Medium (ಮಧ್ಯಮ)" or "Garbled (ಗಾರ್ಬಲ್ಡ್)", extract the Kannada
        if (preg_match('/\(([\x{0C80}-\x{0CFF}\s\/\-\.]+)\)/u', $rawKn, $m)) {
            return trim($m[1]);
        }

        // If name_kn has English characters inside parentheses e.g. "ಮಧ್ಯಮ (Medium)", strip out the parentheses
        if ($rawKn !== '') {
            $clean = preg_replace('/\s*\([A-Za-z0-9\s\/\-\.]+\)/', '', $rawKn);
            $clean = trim($clean);
            if (preg_match('/[\x{0C80}-\x{0CFF}]/u', $clean)) {
                return $clean;
            }
        }

        // If name_kn did not have Kannada, check if Kannada is in parentheses in name: "Rashi (ರಾಶಿ)"
        if (preg_match('/\(([\x{0C80}-\x{0CFF}\s\/\-\.]+)\)/u', $this->name ?? '', $matches)) {
            return trim($matches[1]);
        }

        return $rawKn !== '' ? $rawKn : $this->clean_name;
    }

    /**
     * Return properly localized display name for UI rendering without mixed scripts.
     */
    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $locale === 'en' ? $this->clean_name : $this->clean_name_kn;
    }
}
