<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoordinatorDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_hash',
        'device_label',
        'last_seen_at',
        'registered_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'registered_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
