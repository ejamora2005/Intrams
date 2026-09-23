<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BracketCompetitor extends Model
{
    protected $fillable = [
        'edition_sport_id',
        'type',
        'identity_key',
        'team_id',
        'label',
        'athlete_entry_ids',
    ];

    protected $casts = [
        'athlete_entry_ids' => 'array',
    ];

    public function editionSport()
    {
        return $this->belongsTo(EditionSport::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
