<?php

namespace App\Http\Requests;

use App\Enums\MotorType;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'location' => ['nullable', 'string', 'max:100'],
            'motor_type' => ['nullable', Rule::enum(MotorType::class)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    /**
     * De profielvelden voor ProfileService: `avatar` alleen bij een upload (vervangen) of bij
     * `remove_avatar` (weghalen), zodat een leeg bestandsveld de huidige avatar laat staan.
     *
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $attributes = $this->safe()->except(['avatar', 'remove_avatar']);

        if ($this->hasFile('avatar')) {
            $attributes['avatar'] = $this->file('avatar');
        } elseif ($this->boolean('remove_avatar')) {
            $attributes['avatar'] = null;
        }

        return $attributes;
    }
}
