<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        return ['student_ids' => ['required', 'array', 'min:1'], 'student_ids.*' => ['integer', 'distinct', 'exists:students,id']];
    }
}
