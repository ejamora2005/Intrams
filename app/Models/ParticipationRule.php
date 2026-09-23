<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipationRule extends Model
{
    protected $fillable = ['edition_id', 'name', 'max_sports', 'max_events', 'is_active', 'description'];
    protected $casts = ['is_active' => 'boolean'];
    public function edition() { return $this->belongsTo(IntramuralEdition::class, 'edition_id'); }
}
