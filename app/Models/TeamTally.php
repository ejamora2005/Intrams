<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeamTally extends Model { public $timestamps=false; protected $fillable=['edition_id','team_id','gold_count','silver_count','bronze_count','points','updated_at']; public function team(){return $this->belongsTo(Team::class);} }
