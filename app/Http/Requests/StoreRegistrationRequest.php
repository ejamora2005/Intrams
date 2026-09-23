<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('student_ids') && $this->filled('student_id')) {
            $this->merge(['student_ids' => [$this->input('student_id')]]);
        }
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'exists:events,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'distinct', 'exists:students,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
        ];
    }
}
