<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DataSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dataSourceId = $this->route('datasource')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('data_sources', 'code')->ignore($dataSourceId)],
            'provider_class' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:market_prices,weather,news,videos,schemes'],
            'base_url' => ['required', 'url', 'max:255'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'auth_type' => ['required', 'string', 'in:api_key,bearer_token,none'],
            'sync_frequency' => ['required', 'string', 'in:hourly,daily,twice_daily,weekly'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
