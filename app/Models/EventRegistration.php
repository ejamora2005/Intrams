<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'student_id', 'team_id', 'status', 'registered_by', 'registered_at'];
    protected $casts = ['registered_at' => 'datetime'];
    public function event() { return $this->belongsTo(Event::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function team() { return $this->belongsTo(Team::class); }
}
