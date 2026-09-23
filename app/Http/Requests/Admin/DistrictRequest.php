<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DistrictRequest extends FormRequest
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
        $districtId = $this->route('district')?->id ?? $this->route('district');

        return [
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:100'],
            'name_kn' => ['nullable', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:20', 'unique:districts,code,' . $districtId],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
