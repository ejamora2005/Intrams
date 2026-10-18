<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            in_array($code, [
                'BASEBALL', 'SOFTBALL', 'RUSSIAN-SOFTBALL',
                'VOLLEYBALL', 'VOLLEYBALL-MEN', 'VOLLEYBALL-WOMEN', 'MODIFIED-VOLLEYBALL', 'MODIFIED-VOLLEYBALL-MEN', 'MODIFIED-VOLLEYBALL-WOMEN', 'MIXED-VOLLEYBALL',
                'BEACH-VOLLEYBALL', 'BEACH-VOLLEYBALL-MEN', 'BEACH-VOLLEYBALL-WOMEN', 'ESPORT-ML', 'ESPORT-ML-MEN', 'ESPORT-ML-WOMEN',
            ], true) => 'team',
            in_array($code, ['BADMINTON', 'TABLE-TENNIS', 'CHESS'], true)
            || str_starts_with($code, 'BADMINTON-')
            || str_starts_with($code, 'TABLE-TENNIS-')
            || str_starts_with($code, 'CHESS-') => 'individual',
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

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function editionSports()
    {
        return $this->hasMany(EditionSport::class);
    }
}
