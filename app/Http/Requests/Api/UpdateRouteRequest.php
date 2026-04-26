<?php

namespace App\Http\Requests\Api;

use App\Models\Route;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('route')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', Rule::in(array_keys(Route::getCommonTags()))],
            'difficulty' => ['sometimes', 'nullable', Rule::in(array_keys(Route::getDifficultyLevels()))],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
