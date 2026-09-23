<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\SanitizesStudentInput;

class StoreStudentRequest extends FormRequest
{
    use SanitizesStudentInput;

    protected function prepareForValidation(): void
    {
        $this->sanitizeStudentInput();
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:255', 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'gender' => ['required', 'in:Male,Female,LGBTQ+'],
            'year_level' => ['required', 'in:1st,2nd,3rd,4th'],
            'section' => ['required', 'in:A,B,C'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
