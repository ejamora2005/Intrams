<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['edition_id', 'course_id', 'name', 'code', 'description', 'status'];

    public function edition()
    {
        return $this->belongsTo(IntramuralEdition::class, 'edition_id');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }
    public function course() { return $this->belongsTo(Course::class); }

    public function athleteEntries()
    {
        return $this->hasMany(AthleteEntry::class);
    }

    public function gamAccounts()
    {
        return $this->hasMany(User::class, 'managed_team_id');
    }
}
