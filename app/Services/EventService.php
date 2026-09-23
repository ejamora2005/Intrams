<?php

namespace App\Services;

use App\Models\Event;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    public function __construct(private readonly AuditService $audit) {}
    public function create(array $data): Event { return DB::transaction(function () use ($data) { $this->ensureReferences($data); $event = Event::create([...$data, 'capacity' => $data['competition_type'] === 'individual' ? ($data['capacity'] ?? null) : null, 'venue' => null, 'starts_at' => null, 'ends_at' => null, 'name' => $this->eventName($data), 'code' => 'EVT-'.Str::upper(Str::random(10)), 'status' => 'draft']); $this->audit->record('event.created', $event, null, $event->only($event->getFillable())); return $event; }); }
    public function update(Event $event, array $data): Event { return DB::transaction(function () use ($event, $data) { $this->ensureReferences($data); $this->ensureTransition($event->status, $data['status']); $before = $event->only($event->getFillable()); $event->update([...$data, 'capacity' => $data['competition_type'] === 'individual' ? ($data['capacity'] ?? null) : null, 'venue' => null, 'starts_at' => null, 'ends_at' => null, 'name' => $this->eventName($data)]); $this->audit->record('event.updated', $event, $before, $event->only($event->getFillable())); return $event; }); }
    private function ensureReferences(array $data): void { abort_unless(Sport::query()->whereKey($data['sport_id'])->where('status', 'active')->exists(), 422, 'Choose an active sport.'); abort_unless(IntramuralEdition::query()->whereKey($data['edition_id'])->whereIn('status', ['draft', 'active'])->exists(), 422, 'Choose a draft or active edition.'); }
    private function ensureTransition(string $from, string $to): void { $allowed = ['draft' => ['draft', 'scheduled', 'cancelled'], 'scheduled' => ['scheduled', 'live', 'cancelled'], 'live' => ['live', 'completed', 'cancelled'], 'completed' => ['completed'], 'cancelled' => ['cancelled']]; abort_unless(in_array($to, $allowed[$from] ?? [], true), 422, "The {$from} event cannot change to {$to}."); }
    private function eventName(array $data): string { return IntramuralEdition::findOrFail($data['edition_id'])->name.' - '.Sport::findOrFail($data['sport_id'])->name.' - '.Str::headline($data['competition_type']); }
}
