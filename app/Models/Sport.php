<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sport extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'status', 'is_system'];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function editionSports()
    {
        return $this->hasMany(EditionSport::class);
    }
}
