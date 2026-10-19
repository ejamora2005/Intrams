<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Sport extends Model
{
    use SoftDeletes;

    public const CATALOGUE_GROUPS = [
        'team' => [
            'label' => 'Team Sports',
            'description' => 'Team-based competitions and court games.',
            'icon' => 'team',
        ],
        'individual' => [
            'label' => 'Individual & Pair Sports',
            'description' => 'Singles, doubles, and pair-based competitions.',
            'icon' => 'individual',
        ],
        'track-field' => [
            'label' => 'Track & Field',
            'description' => 'Running, relay, throwing, and jumping events.',
            'icon' => 'track-field',
        ],
        'other' => [
            'label' => 'Other Sports',
            'description' => 'Additional sports configured for this edition.',
            'icon' => 'other',
        ],
    ];

    public const PARTICIPANT_CATEGORIES = [
        'male' => 'Male',
        'female' => 'Female',
        'mixed' => 'Mixed',
        'open' => 'Open',
        'custom' => 'Other / custom',
    ];

    protected $fillable = ['name', 'code', 'description', 'participant_category', 'participant_category_label', 'status', 'is_system'];

    public function getParticipantCategoryLabelAttribute(): string
    {
        if ($this->participant_category === 'custom' && filled($this->attributes['participant_category_label'] ?? null)) {
            return $this->attributes['participant_category_label'];
        }

        return self::PARTICIPANT_CATEGORIES[$this->participant_category ?: 'open'] ?? self::PARTICIPANT_CATEGORIES['open'];
    }

    public static function catalogueGroupKeyFor(?string $code): string
    {
        $code = strtoupper((string) $code);

        return match (true) {
            str_starts_with($code, 'BASKET-'),
            str_starts_with($code, 'BADMINTON-TEAM'),
            $code === 'CHESS' || str_starts_with($code, 'CHESS-'),
            in_array($code, [
                'BASEBALL', 'SOFTBALL', 'RUSSIAN-SOFTBALL',
                'VOLLEYBALL', 'VOLLEYBALL-MEN', 'VOLLEYBALL-WOMEN', 'MODIFIED-VOLLEYBALL', 'MODIFIED-VOLLEYBALL-MEN', 'MODIFIED-VOLLEYBALL-WOMEN', 'MIXED-VOLLEYBALL',
                'BEACH-VOLLEYBALL', 'BEACH-VOLLEYBALL-MEN', 'BEACH-VOLLEYBALL-WOMEN', 'ESPORT-ML', 'ESPORT-ML-MEN', 'ESPORT-ML-WOMEN',
            ], true) => 'team',
            in_array($code, ['BADMINTON', 'TABLE-TENNIS'], true)
            || str_starts_with($code, 'BADMINTON-')
            || str_starts_with($code, 'TABLE-TENNIS-') => 'individual',
            in_array($code, [
                'RUNS', '100M-RUN', '200M-RUN', '400M-RUN', 'RELAY-4X100', 'RELAY-4X400',
                'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW',
                'JUMP', 'LONG-JUMP', 'TRIPLE-JUMP', 'HIGH-JUMP',
            ], true) => 'track-field',
            (str_ends_with($code, '-MEN') || str_ends_with($code, '-WOMEN'))
                && in_array(str_replace(['-MEN', '-WOMEN'], '', $code), [
                    'RUNS', '100M-RUN', '200M-RUN', '400M-RUN', 'RELAY-4X100', 'RELAY-4X400',
                    'THROWS', 'SHOT-PUT', 'DISCUS-THROW', 'JAVELIN-THROW',
                    'JUMP', 'LONG-JUMP', 'TRIPLE-JUMP', 'HIGH-JUMP',
                ], true) => 'track-field',
            default => 'other',
        };
    }

    public function getCatalogueGroupKeyAttribute(): string
    {
        return self::catalogueGroupKeyFor($this->code);
    }

    public function getCatalogueGroupLabelAttribute(): string
    {
        return self::CATALOGUE_GROUPS[$this->catalogue_group_key]['label'];
    }

    /**
     * Track and field entries are recorded as individual results and do not
     * produce head-to-head tournament brackets, even when their catalogue
     * mechanic is stored as `custom`.
     */
    public function isTrackAndField(): bool
    {
        return $this->catalogue_group_key === 'track-field';
    }

    public function acceptsStudentGender(?string $gender): bool
    {
        return ! in_array($this->participant_category, ['male', 'female'], true)
            || strcasecmp((string) $gender, $this->participant_category) === 0;
    }

    /**
     * Keep only the current versions of sports in the supplied catalogue.
     * Callers supply one edition's sports so divisions in another edition do
     * not hide an event here. Mixed events and cultural events remain separate.
     *
     * @param  Collection<int, Sport>  $sports
     * @return Collection<int, Sport>
     */
    public static function withoutSupersededOpenSports(Collection $sports): Collection
    {
        $divisionKeys = $sports
            ->filter(fn (Sport $sport): bool => ! str_starts_with(strtoupper($sport->code), 'CULT-') && $sport->hasGenderDivision())
            ->flatMap(fn (Sport $sport): array => $sport->divisionKeys())
            ->flip();

        return $sports->reject(fn (Sport $sport): bool => ! str_starts_with(strtoupper($sport->code), 'CULT-')
            && in_array($sport->participant_category, [null, '', 'open'], true)
            && ! $sport->hasGenderDivision()
            && collect($sport->divisionKeys())->contains(fn (string $key): bool => $divisionKeys->has($key)))
            ->values();
    }

    private function hasGenderDivision(): bool
    {
        return in_array($this->participant_category, ['male', 'female'], true)
            || preg_match('/-(MEN|WOMEN|MALE|FEMALE)$/i', $this->code) === 1
            || preg_match('/\b(Men|Women|Male|Female)\s*\)?$/i', $this->name) === 1;
    }

    /** @return array<int, string> */
    private function divisionKeys(): array
    {
        $code = preg_replace('/-(MEN|WOMEN|MALE|FEMALE|OPEN)$/i', '', strtoupper(trim($this->code)));
        // These original entries were replaced by specific racket formats.
        $code = match ($code) {
            'BADMINTON' => 'BADMINTON-DOUBLES',
            'TABLE-TENNIS' => 'TABLE-TENNIS-SINGLES',
            default => $code,
        };
        $name = preg_replace('/\s*(?:[-(]\s*)?\b(Men|Women|Male|Female|Open)\s*\)?$/i', '', $this->name);

        return ['code:'.$code, 'name:'.Str::slug($name)];
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function editionSports()
    {
        return $this->hasMany(EditionSport::class);
    }
}
