<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BracketMatch extends Model
{
    protected $fillable = [
        'edition_sport_id',
        'bracket',
        'round_number',
        'match_number',
        'competitor_one_id',
        'competitor_two_id',
        'winner_competitor_id',
        'loser_competitor_id',
        'team_one_id',
        'team_two_id',
        'winner_team_id',
        'loser_team_id',
        'status',
    ];

    public function editionSport() { return $this->belongsTo(EditionSport::class); }
    public function teamOne() { return $this->belongsTo(Team::class, 'team_one_id'); }
    public function teamTwo() { return $this->belongsTo(Team::class, 'team_two_id'); }
    public function winner() { return $this->belongsTo(Team::class, 'winner_team_id'); }
    public function loser() { return $this->belongsTo(Team::class, 'loser_team_id'); }
    public function competitorOne() { return $this->belongsTo(BracketCompetitor::class, 'competitor_one_id'); }
    public function competitorTwo() { return $this->belongsTo(BracketCompetitor::class, 'competitor_two_id'); }
    public function winnerCompetitor() { return $this->belongsTo(BracketCompetitor::class, 'winner_competitor_id'); }
    public function loserCompetitor() { return $this->belongsTo(BracketCompetitor::class, 'loser_competitor_id'); }
    public function schedule() { return $this->hasOne(CompetitionSchedule::class); }
}
