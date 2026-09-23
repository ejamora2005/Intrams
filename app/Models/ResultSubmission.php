<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResultSubmission extends Model { protected $fillable=['fixture_id','revision','status','submitted_by','submitted_at','reviewed_by','reviewed_at','review_notes','payload_json']; protected $casts=['submitted_at'=>'datetime','reviewed_at'=>'datetime','payload_json'=>'array']; public function fixture(){return $this->belongsTo(Fixture::class);} }
