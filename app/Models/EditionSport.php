<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EditionSport extends Model { use SoftDeletes; protected $fillable=['edition_id','sport_id','participant_type','game_mechanic','match_rules','scoring_rules','rules','status']; protected $casts=['match_rules'=>'array','scoring_rules'=>'array']; public function edition(){return $this->belongsTo(IntramuralEdition::class);} public function sport(){return $this->belongsTo(Sport::class);} public function athleteEntries(){return $this->hasMany(AthleteEntry::class);} public function bracketCompetitors(){return $this->hasMany(BracketCompetitor::class);} public function schedules(){return $this->hasMany(CompetitionSchedule::class);} }
