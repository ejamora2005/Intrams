<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoordinatorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'coordinator_id',
        'event_id',
        'assigned_by',
        'status',
        'approved_at',
        'revoked_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
