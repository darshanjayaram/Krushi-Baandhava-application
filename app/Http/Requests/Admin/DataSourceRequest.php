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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sync_days' => $this->input('sync_days') ?: 'mon_sat',
            'sync_time' => $this->input('sync_time') ?: '06:00,18:00',
        ]);
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
            'sync_frequency' => ['required', 'string', 'in:hourly,every_2_hours,every_6_hours,every_12_hours,daily,twice_daily,weekly'],
            'sync_time' => ['nullable', 'string', 'max:50'],
            'sync_days' => ['nullable', 'string', 'in:mon_sat,all'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
