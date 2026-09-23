<?php

namespace App\Http\Requests;

use App\Models\Sport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin' && $this->user()?->status === 'active'; }
    protected function prepareForValidation(): void { $this->merge(['name' => Str::title(Str::squish(strip_tags((string) $this->input('name'))))]); }
    public function rules(): array { $sport = $this->route('sport'); return ['name' => ['required', 'string', 'max:255', Rule::unique('sports', 'name')->ignore($sport)], 'description' => ['nullable', 'string', 'max:2000'], 'status' => ['required', 'in:active,inactive']]; }
}
