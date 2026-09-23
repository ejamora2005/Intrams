<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'admin' && $this->user()?->status === 'active'; }
    public function rules(): array { return ['edition_id' => ['required', 'exists:intramural_editions,id'], 'sport_id' => ['required', 'exists:sports,id'], 'competition_type' => ['required', 'in:team,individual,dual'], 'capacity' => ['nullable', 'integer', 'min:1'], 'result_mode' => ['required', 'in:score,placement,win_loss,custom']]; }
}
