<?php

namespace App\Http\Requests;

use App\Models\IntramuralEdition;
use App\Services\PublicProgrammeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePublicScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        foreach (['event', 'venue'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim(strip_tags($this->input($field)))]);
            }
        }
        if (is_array($this->input('games'))) {
            $this->merge(['games' => array_values($this->input('games'))]);
        }
    }

    public function rules(): array
    {
        $teams = array_keys(app(PublicProgrammeService::class)->teamOptions(IntramuralEdition::find($this->integer('edition_id'))));
        $references = collect(range(1, 12))->flatMap(fn ($number) => ['winner:'.$number, 'loser:'.$number])->all();

        return [
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'entry_key' => ['required', 'string', Rule::in([$this->route('entryKey')])],
            'version' => ['required', 'integer', 'min:0'],
            'event' => ['required', 'string', 'max:160'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.config('public_programme.starts_on'), 'before_or_equal:'.config('public_programme.ends_on')],
            'session' => ['required', Rule::in(['AM', 'PM', 'Evening'])],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'category' => ['required', Rule::in(['sports', 'cultural', 'activities'])],
            'venue' => ['nullable', 'string', 'max:160'],
            'is_published' => ['required', 'boolean'],
            'games_present' => ['sometimes', 'boolean'],
            'games' => ['sometimes', 'array', 'max:12'],
            'games.*' => ['array:home,away'],
            'games.*.home' => ['nullable', 'string', Rule::in([...$teams, ...$references])],
            'games.*.away' => ['nullable', 'string', Rule::in([...$teams, ...$references])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');
            if ($start && $end && $end <= $start) {
                $validator->errors()->add('ends_at', 'The end or cutoff time must be later than the start time.');
            }
            if ($start) {
                $hour = (int) substr($start, 0, 2);
                $session = $hour < 12 ? 'AM' : ($hour < 18 ? 'PM' : 'Evening');
                if ($session !== $this->input('session')) {
                    $validator->errors()->add('session', 'Choose the session that matches the start time: '.$session.'.');
                }
            }
            foreach ($this->input('games', []) as $index => $game) {
                foreach (['home', 'away'] as $side) {
                    if (preg_match('/^(?:winner|loser):(\d+)$/', $game[$side] ?? '', $match) && (int) $match[1] >= $index + 1) {
                        $validator->errors()->add('games.'.$index.'.'.$side, 'Game '.($index + 1).' can only use a winner or loser from an earlier game.');
                    }
                }
                if (! empty($game['home']) && $game['home'] === ($game['away'] ?? null)) {
                    $validator->errors()->add('games.'.$index.'.away', 'Choose two different opponents for Game '.($index + 1).'.');
                }
            }
        });
    }
}
