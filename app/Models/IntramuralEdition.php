<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntramuralEdition extends Model
{
    protected $fillable = ['name', 'school_year', 'starts_on', 'ends_on', 'status'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function teams()
    {
        return $this->hasMany(Team::class, 'edition_id');
    }

    public function editionSports()
    {
        return $this->hasMany(EditionSport::class, 'edition_id');
    }

    public function competitionSchedules()
    {
        return $this->hasManyThrough(CompetitionSchedule::class, EditionSport::class, 'edition_id', 'edition_sport_id');
    }
}
