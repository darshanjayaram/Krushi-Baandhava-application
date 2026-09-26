<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CropRequest extends FormRequest
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
        $cropId = $this->route('crop')?->id ?? $this->route('crop');

        return [
            'category_id' => ['required', 'exists:crop_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'name_kn' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:crops,slug,' . $cropId],
            'scientific_name' => ['nullable', 'string', 'max:150'],
            'standard_unit' => ['required', 'string', 'max:50'],
            'price_source_type' => ['nullable', 'string', 'in:apmc,coffee_board,coconut_board'],
            'market_radius_km' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'default_market_sort' => ['nullable', 'string', 'in:nearest_first,highest_price_first'],
            'allow_user_sort_toggle' => ['sometimes', 'boolean'],
            'enable_smart_badges' => ['sometimes', 'boolean'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'preset_image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_major' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
