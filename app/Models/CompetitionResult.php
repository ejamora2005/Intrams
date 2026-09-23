<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionResult extends Model
{
    protected $fillable = ['competition_schedule_id', 'result_data', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'status', 'notes'];
    protected $casts = ['result_data' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    public function schedule() { return $this->belongsTo(CompetitionSchedule::class, 'competition_schedule_id'); }
}
