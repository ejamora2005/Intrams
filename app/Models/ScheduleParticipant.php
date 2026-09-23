<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleParticipant extends Model
{
    protected $fillable = ['competition_schedule_id', 'team_id', 'athlete_entry_id', 'slot', 'status'];
    public function schedule() { return $this->belongsTo(CompetitionSchedule::class, 'competition_schedule_id'); }
    public function team() { return $this->belongsTo(Team::class); }
    public function athleteEntry() { return $this->belongsTo(AthleteEntry::class); }
}
