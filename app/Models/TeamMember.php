<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = ['edition_id', 'team_id', 'student_id', 'assigned_by', 'assigned_at'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
