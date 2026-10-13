<?php

namespace App\Http\Requests;

use App\Models\Sport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        $participantCategory = $this->input('participant_category', 'open');

        $this->merge(['name' => Str::title(Str::squish(strip_tags((string) $this->input('name')))), 'participant_category' => $participantCategory, 'participant_category_label' => $participantCategory === 'custom' ? Str::squish(strip_tags((string) $this->input('participant_category_label'))) : null]);
    }

    public function rules(): array
    {
        $sport = $this->route('sport');

        return ['name' => ['required', 'string', 'max:255', Rule::unique('sports', 'name')->ignore($sport)], 'description' => ['nullable', 'string', 'max:2000'], 'participant_category' => ['required', Rule::in(array_keys(Sport::PARTICIPANT_CATEGORIES))], 'participant_category_label' => ['nullable', 'string', 'max:100', 'required_if:participant_category,custom'], 'status' => ['required', 'in:active,inactive']];
    }
}
