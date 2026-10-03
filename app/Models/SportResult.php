<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportResult extends Model
{
    protected $fillable = [
        'edition_sport_id',
        'submitted_by',
        'placements_json',
        'points_json',
        'submitted_at',
    ];

    protected $casts = [
        'placements_json' => 'array',
        'points_json' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function editionSport()
    {
        return $this->belongsTo(EditionSport::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
