<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CropVarietyRequest extends FormRequest
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
        $varietyId = $this->route('variety')?->id ?? $this->route('variety');
        $cropId = $this->input('crop_id') ?? $this->route('crop')?->id;

        return [
            'crop_id' => ['required', 'exists:crops,id'],
            'name' => ['required', 'string', 'max:150'],
            'name_kn' => ['nullable', 'string', 'max:200'],
            'slug' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('crop_varieties')->where(function ($query) use ($cropId) {
                    return $query->where('crop_id', $cropId);
                })->ignore($varietyId),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
