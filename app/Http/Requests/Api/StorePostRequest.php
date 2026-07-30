<?php

namespace App\Http\Requests\Api;

use App\Models\MaintenanceLog;
use App\Models\Post;
use App\Models\Route;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Post::getPostTypes()))],
            'route_id' => [
                'nullable',
                'integer',
                Rule::exists('routes', 'id'),
                Rule::requiredIf(fn (): bool => $this->input('type') === 'route_share'),
            ],
            'maintenance_log_id' => [
                'nullable',
                'integer',
                Rule::exists('maintenance_logs', 'id'),
                Rule::requiredIf(fn (): bool => $this->input('type') === 'maintenance'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');

            if ($type === 'route_share' && $this->filled('route_id')) {
                $route = Route::query()->find($this->input('route_id'));

                if ($route !== null && ! $route->is_public) {
                    $validator->errors()->add('route_id', 'Alleen publieke routes mogen gedeeld worden.');
                }
            }

            if ($type === 'maintenance' && $this->filled('maintenance_log_id')) {
                $log = MaintenanceLog::query()->find($this->input('maintenance_log_id'));

                if ($log !== null && $log->user_id !== $this->user()?->id) {
                    $validator->errors()->add('maintenance_log_id', 'Je kunt alleen je eigen maintenance log delen.');
                }
            }
        });
    }
}
