<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionSchedule extends Model
{
    protected $fillable = ['edition_sport_id', 'starts_at', 'ends_at', 'venue', 'status', 'coordinator_id'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function editionSport() { return $this->belongsTo(EditionSport::class); }
    public function participants() { return $this->hasMany(ScheduleParticipant::class); }
    public function results() { return $this->hasMany(CompetitionResult::class); }
}
