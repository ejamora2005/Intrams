<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AthleteEntry extends Model
{
    protected $fillable = [
        'edition_sport_id',
        'student_id',
        'team_id',
        'pair_key',
        'status',
        'medical_certificate_status',
        'medical_certificate_reviewed_by',
        'medical_certificate_reviewed_at',
        'medical_certificate_notes',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'medical_certificate_reviewed_at' => 'datetime',
    ];

    public function editionSport() { return $this->belongsTo(EditionSport::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function medicalCertificateReviewer() { return $this->belongsTo(User::class, 'medical_certificate_reviewed_by'); }
}
