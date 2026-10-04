<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['edition_id', 'course_id', 'name', 'code', 'description', 'status'];

    public function getLogoPathAttribute(): ?string
    {
        return self::logoPathFor($this->code) ?? self::logoPathFor($this->name);
    }

    public static function logoPathFor(?string $value): ?string
    {
        $needle = self::logoKey($value);

        if ($needle === '') {
            return null;
        }

        foreach (config('landing.teamLogos', []) as $key => $path) {
            if (self::logoKey((string) $key) === $needle) {
                return is_string($path) && $path !== '' ? $path : null;
            }
        }

        return null;
    }

    public static function logoKey(?string $value): string
    {
        return Str::of($value ?? '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();
    }

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
