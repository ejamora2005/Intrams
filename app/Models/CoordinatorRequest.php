<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoordinatorRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'coordinator_id',
        'event_id',
        'source_event_id',
        'request_type',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function sourceEvent()
    {
        return $this->belongsTo(Event::class, 'source_event_id');
    }
}
