<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            if (blank($student->student_number)) {
                $student->student_number = static::generateStudentId($student->getAttributes());
            }
        });
    }

    /** @param array<string, mixed> $attributes */
    public static function generateStudentId(array $attributes): string
    {
        $name = collect([
            $attributes['first_name'] ?? null,
            $attributes['middle_name'] ?? null,
            $attributes['last_name'] ?? null,
        ])->filter(fn (mixed $part): bool => filled($part))->implode(' ');
        $slug = Str::upper(Str::slug($name));
        $base = 'STU-'.($slug !== '' ? $slug : Str::upper(Str::random(8)));
        $candidate = $base;
        $suffix = 2;

        while (static::withTrashed()->where('student_number', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

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
