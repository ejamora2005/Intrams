<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeamFlag extends Model { protected $fillable=['event_id','team_id','registration_id','reported_by','reason','notes','status','resolved_by','resolved_at','resolution_notes']; protected $casts=['resolved_at'=>'datetime']; public function event(){return $this->belongsTo(Event::class);} public function team(){return $this->belongsTo(Team::class);} }
