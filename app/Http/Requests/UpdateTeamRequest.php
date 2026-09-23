<?php

namespace App\Http\Requests;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', Rule::unique('teams', 'code')->where('edition_id', $this->input('edition_id'))->ignore($team)],
            'course_id' => ['nullable', 'exists:courses,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive,disqualified'],
        ];
    }
}
