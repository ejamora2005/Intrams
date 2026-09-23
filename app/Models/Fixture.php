<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Fixture extends Model { protected $fillable=['event_id','round_name','sequence','scheduled_at','venue','status','started_at','completed_at']; protected $casts=['scheduled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime']; public function event(){return $this->belongsTo(Event::class);} }
