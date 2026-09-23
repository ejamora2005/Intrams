<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'school_year',
        'course_id',
        'gender',
        'year_level',
        'section',
        'status',
    ];

    /** @var array<int, string> */
    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function athleteEntries()
    {
        return $this->hasMany(AthleteEntry::class);
    }
}
