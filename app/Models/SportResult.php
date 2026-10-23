<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportResult extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $result): void {
            // Keep direct imports and legacy integrations visible until they are
            // explicitly saved as a draft by the tabulator workflow.
            if (! $result->isDirty('published_placements_json') && $result->placements_json !== null) {
                $result->published_placements_json = $result->placements_json;
            }
            if (! $result->isDirty('published_points_json') && $result->points_json !== null) {
                $result->published_points_json = $result->points_json;
            }
            if (! $result->isDirty('score_published_at')) {
                $result->score_published_at = $result->submitted_at;
            }
            if (! $result->isDirty('score_published_by')) {
                $result->score_published_by = $result->submitted_by;
            }
        });
    }

    protected $fillable = [
        'edition_sport_id',
        'submitted_by',
        'placements_json',
        'published_placements_json',
        'points_json',
        'published_points_json',
        'score_group',
        'submitted_at',
        'score_published_at',
        'score_published_by',
    ];

    protected $casts = [
        'placements_json' => 'array',
        'published_placements_json' => 'array',
        'points_json' => 'array',
        'published_points_json' => 'array',
        'submitted_at' => 'datetime',
        'score_published_at' => 'datetime',
    ];

    public function editionSport()
    {
        return $this->belongsTo(EditionSport::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function scorePublisher()
    {
        return $this->belongsTo(User::class, 'score_published_by');
    }
}
