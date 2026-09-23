<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSportRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin' && $this->user()?->status === 'active'; }
    protected function prepareForValidation(): void { $this->merge(['name' => Str::title(Str::squish(strip_tags((string) $this->input('name')))), 'code' => Str::upper(Str::squish(strip_tags((string) $this->input('code'))))]); }
    public function rules(): array { return ['name' => ['required', 'string', 'max:255', 'unique:sports,name'], 'description' => ['nullable', 'string', 'max:2000'], 'status' => ['required', 'in:active,inactive']]; }
}
