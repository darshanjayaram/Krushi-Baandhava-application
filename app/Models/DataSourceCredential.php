<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSourceCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source_id',
        'api_key',
        'client_id',
        'client_secret',
        'additional_headers',
    ];

    /**
     * Cast attributes to native types and handle automatic AES-256 encryption at rest.
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'client_id' => 'encrypted',
            'client_secret' => 'encrypted',
            'additional_headers' => 'encrypted',
        ];
    }

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSource::class);
    }

    /**
     * Mask the API key for safe UI display (e.g. data_gov_****1234).
     */
    public function getMaskedApiKeyAttribute(): ?string
    {
        $key = $this->api_key;
        if (empty($key)) {
            return null;
        }

        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', max(4, $length - 8)) . substr($key, -4);
    }
}
