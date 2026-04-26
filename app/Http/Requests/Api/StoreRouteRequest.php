<?php

namespace App\Http\Requests\Api;

use App\Models\Route;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Route::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gpx_file' => ['required', 'file', 'mimes:gpx,xml', 'max:10240'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(array_keys(Route::getCommonTags()))],
            'difficulty' => ['nullable', Rule::in(array_keys(Route::getDifficultyLevels()))],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
