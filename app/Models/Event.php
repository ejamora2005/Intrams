<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'edition_id',
        'sport_id',
        'name',
        'code',
        'competition_type',
        'division',
        'venue',
        'capacity',
        'starts_at',
        'ends_at',
        'status',
        'result_mode',
    ];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function coordinatorAssignments()
    {
        return $this->hasMany(CoordinatorAssignment::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function edition()
    {
        return $this->belongsTo(IntramuralEdition::class, 'edition_id');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function fixtures()
    {
        return $this->hasMany(Fixture::class);
    }
}
