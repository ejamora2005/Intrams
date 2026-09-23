<?php

namespace App\Http\Requests;

use App\Models\IntramuralEdition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        /** @var IntramuralEdition $edition */
        $edition = $this->route('edition');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('intramural_editions', 'name')->where('school_year', $this->input('school_year'))->ignore($edition)],
            'school_year' => ['required', 'string', 'max:20'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', 'in:draft,active,closed,archived'],
        ];
    }
}
