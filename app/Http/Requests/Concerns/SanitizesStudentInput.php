<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

trait SanitizesStudentInput
{
    protected function sanitizeStudentInput(): void
    {
        $sanitizeText = static function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', strip_tags($value)) ?? '';
            $value = Str::squish($value);

            return $value === '' ? null : $value;
        };

        $capitalizeName = static function (mixed $value) use ($sanitizeText): ?string {
            $value = $sanitizeText($value);

            return $value === null ? null : Str::title(Str::lower($value));
        };

        $attributes = [
            'first_name' => $capitalizeName($this->input('first_name')),
            'middle_name' => $capitalizeName($this->input('middle_name')),
            'last_name' => $capitalizeName($this->input('last_name')),
            'school_year' => $sanitizeText($this->input('school_year')),
            'year_level' => $sanitizeText($this->input('year_level')),
            'section' => Str::upper($sanitizeText($this->input('section')) ?? ''),
            'gender' => $sanitizeText($this->input('gender')),
        ];
        // The edit form displays this identifier without submitting it. Do not
        // turn an absent field into an empty unique identifier during updates.
        if ($this->exists('student_number')) {
            $attributes['student_number'] = Str::upper($sanitizeText($this->input('student_number')) ?? '');
        }

        $this->merge($attributes);
    }
}
