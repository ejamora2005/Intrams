<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePublicScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        foreach (['event', 'venue'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim(strip_tags($this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'entry_key' => ['required', 'string', Rule::in([$this->route('entryKey')])],
            'version' => ['required', 'integer', 'min:0'],
            'event' => ['required', 'string', 'max:160'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.config('public_programme.starts_on'), 'before_or_equal:'.config('public_programme.ends_on')],
            'session' => ['required', Rule::in(['AM', 'PM', 'Evening'])],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'category' => ['required', Rule::in(['sports', 'cultural', 'activities'])],
            'venue' => ['nullable', 'string', 'max:160'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');
            if ($start && $end && $end <= $start) {
                $validator->errors()->add('ends_at', 'The end or cutoff time must be later than the start time.');
            }
            if ($start) {
                $hour = (int) substr($start, 0, 2);
                $session = $hour < 12 ? 'AM' : ($hour < 18 ? 'PM' : 'Evening');
                if ($session !== $this->input('session')) {
                    $validator->errors()->add('session', 'Choose the session that matches the start time: '.$session.'.');
                }
            }
        });
    }
}
