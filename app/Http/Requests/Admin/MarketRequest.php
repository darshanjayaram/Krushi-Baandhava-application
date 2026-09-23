<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $marketId = $this->route('market')?->id ?? $this->route('market');

        return [
            'district_id' => ['required', 'exists:districts,id'],
            'taluk_id' => ['nullable', 'exists:taluks,id'],
            'name' => ['required', 'string', 'max:150'],
            'name_kn' => ['nullable', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50', 'unique:markets,code,' . $marketId],
            'market_type' => ['required', 'string', 'in:APMC,Sub-market,Private'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
