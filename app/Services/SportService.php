<?php

namespace App\Services;

use App\Models\Sport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SportService
{
    public function __construct(private readonly AuditService $audit) {}
    public function create(array $data): Sport { return DB::transaction(function () use ($data) { $sport = Sport::create([...$data, 'code' => 'SPORT-'.Str::upper(Str::random(8))]); $this->audit->record('sport.created', $sport, null, $sport->only($sport->getFillable())); return $sport; }); }
    public function update(Sport $sport, array $data): Sport { return DB::transaction(function () use ($sport, $data) { $before = $sport->only($sport->getFillable()); $sport->update($data); $this->audit->record('sport.updated', $sport, $before, $sport->only($sport->getFillable())); return $sport; }); }
    public function archive(Sport $sport): void { DB::transaction(function () use ($sport) { $before = $sport->only($sport->getFillable()); $sport->delete(); $this->audit->record('sport.archived', $sport, $before); }); }
    public function restore(Sport $sport): void { DB::transaction(function () use ($sport) { $sport->restore(); $this->audit->record('sport.restored', $sport, null, $sport->only($sport->getFillable())); }); }
}
