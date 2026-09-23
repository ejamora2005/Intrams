<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinatorChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coordinator' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'source_event_id' => ['nullable', 'integer', 'exists:events,id', 'required_if:request_type,reassign'],
            'request_type' => ['required', 'in:add_event,remove_event,reassign'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
