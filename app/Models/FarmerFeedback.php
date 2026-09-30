<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmerFeedback extends Model
{
    use HasFactory;

    protected $table = 'farmer_feedbacks';

    protected $fillable = [
        'ticket_no',
        'type',
        'category',
        'rating',
        'crop_name',
        'district',
        'market_name',
        'message',
        'voice_path',
        'voice_duration',
        'photo_path',
        'farmer_name',
        'farmer_phone',
        'farmer_email',
        'status',
        'admin_notes',
        'resolved_at',
        'resolved_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'rating' => 'integer',
        'voice_duration' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public const STATUS_NEW = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_ISSUE = 'issue';
    public const TYPE_FEEDBACK = 'feedback';

    protected static function booted(): void
    {
        static::creating(function ($feedback) {
            if (empty($feedback->ticket_no)) {
                $feedback->ticket_no = self::generateUniqueTicketNo();
            }
        });
    }

    /**
     * Generate unique ticket number like KB-26-8941
     */
    public static function generateUniqueTicketNo(): string
    {
        do {
            $year = date('y');
            $randomNum = mt_rand(1000, 9999);
            $ticket = "KB-{$year}-{$randomNum}";
        } while (self::where('ticket_no', $ticket)->exists());

        return $ticket;
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeIssues($query)
    {
        return $query->where('type', self::TYPE_ISSUE);
    }

    public function scopeFeedbacks($query)
    {
        return $query->where('type', self::TYPE_FEEDBACK);
    }

    public function scopeStatus($query, $status)
    {
        if ($status && in_array($status, [self::STATUS_NEW, self::STATUS_IN_REVIEW, self::STATUS_RESOLVED, self::STATUS_REJECTED])) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('ticket_no', 'like', "%{$term}%")
              ->orWhere('farmer_name', 'like', "%{$term}%")
              ->orWhere('farmer_phone', 'like', "%{$term}%")
              ->orWhere('crop_name', 'like', "%{$term}%")
              ->orWhere('market_name', 'like', "%{$term}%")
              ->orWhere('district', 'like', "%{$term}%")
              ->orWhere('message', 'like', "%{$term}%");
        });
    }

    public function getVoiceUrlAttribute(): ?string
    {
        if (!$this->voice_path) {
            return null;
        }

        if (request()->hasHeader('host')) {
            $base = rtrim(request()->schemeAndHttpHost() . request()->getBasePath(), '/');
            return $base . '/storage/' . ltrim($this->voice_path, '/');
        }

        return Storage::disk('public')->url($this->voice_path);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        if (request()->hasHeader('host')) {
            $base = rtrim(request()->schemeAndHttpHost() . request()->getBasePath(), '/');
            return $base . '/storage/' . ltrim($this->photo_path, '/');
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function getCategoryLabelAttribute(): string
    {
        $categories = [
            // Issues
            'price_discrepancy' => 'ದರ ವ್ಯತ್ಯಾಸ (Price Discrepancy)',
            'sell_produce' => 'ಬೆಳೆ ಮಾರಾಟ ಸಹಾಯ (Sell Produce Assistance)',
            'new_market' => 'ಹೊಸ ಮಾರುಕಟ್ಟೆ ಕೋರಿಕೆ (Request New Mandi)',
            'crop_disease' => 'ಬೆಳೆ ರೋಗ / ಕೀಟ (Crop Disease & Pest)',
            'govt_scheme' => 'ಸರ್ಕಾರಿ ಯೋಜನೆ ಸಹಾಯ (Govt Scheme Query)',
            'bug' => 'ಆ್ಯಪ್ ದೋಷ (Technical Bug)',
            'weighing_issue' => 'ತೂಕ ಮತ್ತು ದಲ್ಲಾಳಿ ಸಮಸ್ಯೆ (Mandi Weighing/Commission)',
            'other_issue' => 'ಇತರೆ ಸಮಸ್ಯೆ (Other Issue)',

            // Feedback
            'price_accuracy' => 'ದರ ಮಾಹಿತಿ ಉಪಯುಕ್ತತೆ (Price Accuracy & Quality)',
            'feature_request' => 'ಹೊಸ ವೈಶಿಷ್ಟ್ಯ ಕೋರಿಕೆ (Feature Request)',
            'app_design' => 'ಆ್ಯಪ್ ವಿನ್ಯಾಸ & ಬಳಕೆ (App Usability & Design)',
            'appreciation' => 'ಪ್ರಶಂಸೆ & ಅಭಿನಂದನೆ (Appreciation)',
            'other_feedback' => 'ಇತರೆ ಸಲಹೆ (Other Feedback)',
        ];

        return $categories[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function getWhatsAppDirectUrl(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->farmer_phone);
        if (!Str::startsWith($phone, '91') && strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        $greeting = $this->farmer_name ? "ನಮಸ್ಕಾರ {$this->farmer_name} ಅವರೇ," : "ನಮಸ್ಕಾರ,";
        $typeLabel = $this->type === self::TYPE_ISSUE ? 'ಸಮಸ್ಯೆ ವರದಿ' : 'ಸಲಹೆ/ಅಭಿಪ್ರಾಯ';
        
        $text = "{$greeting}\nಕೃಷಿ ಬಾಂಧವ ತಂಡದಿಂದ ನಿಮ್ಮ ಟಿಕೆಟ್ ಸಂಖ್ಯೆ *{$this->ticket_no}* ({$typeLabel}) ಕುರಿತು ಸಂಪರ್ಕಿಸುತ್ತಿದ್ದೇವೆ.";

        return "https://wa.me/{$phone}?text=" . rawurlencode($text);
    }
}
