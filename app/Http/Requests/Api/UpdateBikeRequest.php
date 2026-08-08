<?php

namespace App\Http\Requests\Api;

use App\Models\Bike;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bike = $this->route('bike');

        return $bike instanceof Bike && $this->user()?->can('update', $bike) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'required', 'string', 'max:255'],
            'model' => ['sometimes', 'required', 'string', 'max:255'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'km_current' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
