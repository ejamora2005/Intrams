<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicProgramme extends Model
{
    protected $fillable = ['edition_id', 'events', 'source', 'updated_by'];

    protected $casts = ['events' => 'array', 'source' => 'array'];

    public function edition()
    {
        return $this->belongsTo(IntramuralEdition::class);
    }
}
