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
            'entry_key' => $this->routeIs('admin.schedules.store') ? ['prohibited'] : ['required', 'string', Rule::in([$this->route('entryKey')])],
            'version' => [$this->routeIs('admin.schedules.store') ? 'sometimes' : 'required', 'integer', 'min:0'],
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
            'games.*' => ['array:home,away,bracket_match_ids,manual_teams,update_bracket_schedule,bracket_starts_at,bracket_ends_at,bracket_venue'],
            'games.*.home' => ['nullable', 'string', Rule::in([...$teams, ...$references])],
            'games.*.away' => ['nullable', 'string', Rule::in([...$teams, ...$references])],
            'games.*.bracket_match_ids' => ['sometimes', 'array', 'max:12'],
            'games.*.bracket_match_ids.*' => ['integer', 'distinct', Rule::exists('bracket_matches', 'id')->whereIn('edition_sport_id', \App\Models\EditionSport::where('edition_id', $this->integer('edition_id'))->pluck('id')->all())],
            'games.*.manual_teams' => ['sometimes', 'boolean'],
            'games.*.update_bracket_schedule' => ['sometimes', 'boolean'],
            'games.*.bracket_starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'games.*.bracket_ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'games.*.bracket_venue' => ['nullable', 'string', 'max:160'],
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
                if (! empty($game['update_bracket_schedule'])) {
                    $edition = IntramuralEdition::findOrFail($this->integer('edition_id'));
                    if (empty($game['bracket_match_ids']) || empty($game['bracket_starts_at']) || empty($game['bracket_ends_at']) || $game['bracket_ends_at'] <= $game['bracket_starts_at']) {
                        $validator->errors()->add('games.'.$index, 'Choose a bracket match and exact start/end times for Game '.($index + 1).'.');
                    }
                    foreach (['bracket_starts_at', 'bracket_ends_at'] as $field) {
                        $date = substr($game[$field] ?? '', 0, 10);
                        if ($date < $edition->starts_on->toDateString() || $date > $edition->ends_on->toDateString()) {
                            $validator->errors()->add('games.'.$index.'.'.$field, 'Choose a match date within the active edition.');
                        }
                    }
                }
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
