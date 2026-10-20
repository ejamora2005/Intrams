<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AthleteEntry;
use App\Models\IntramuralEdition;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\EligibilityService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MedicalApprovalController extends Controller
{
    public const STATUSES = ['pending' => 'Pending', 'verified' => 'Approved', 'rejected' => 'Rejected'];

    public function __construct(
        private readonly StandingsService $standings,
        private readonly EligibilityService $eligibility,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'team_id' => ['nullable', 'integer'],
        ]);
        $edition = $this->standings->activeEdition();
        $profiles = $edition ? $this->profiles($this->entries($edition)) : collect();
        $summary = [
            'Players' => $profiles->count(),
            'Pending review' => $profiles->filter(fn ($profile) => $profile['statuses']->contains('pending'))->count(),
            'Fully approved' => $profiles->where('status', 'verified')->count(),
            'With rejected events' => $profiles->filter(fn ($profile) => $profile['statuses']->contains('rejected'))->count(),
        ];
        $filtered = $profiles
            ->when($request->filled('search'), fn ($rows) => $rows->filter(fn ($profile) => $this->matchesSearch($profile, $filters['search'])))
            ->when($request->filled('status'), fn ($rows) => $rows->filter(fn ($profile) => $profile['statuses']->contains($filters['status'])))
            ->when($request->filled('team_id'), fn ($rows) => $rows->filter(fn ($profile) => (int) $profile['team']?->id === $request->integer('team_id')))
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $players = new LengthAwarePaginator($filtered->forPage($page, 100)->values(), $filtered->count(), 100, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return view('admin.medical-approvals.index', [
            'edition' => $edition, 'players' => $players, 'summary' => $summary, 'filters' => $filters,
            'teams' => $edition?->teams()->where('status', 'active')->orderBy('name')->get() ?? collect(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        $edition = $this->activeEdition();
        $data = $request->validate(['student_id' => ['nullable', 'integer'], 'search' => ['nullable', 'string', 'max:150']]);
        $profiles = $this->profiles($this->entries($edition));
        $profile = $request->filled('student_id') ? $profiles->firstWhere('student.id', $request->integer('student_id')) : null;
        abort_if($request->filled('student_id') && ! $profile, 404);
        $choices = $profiles->filter(fn ($row) => $row['entries']->contains(fn ($entry) => ! $this->hasReview($entry)))
            ->when($request->filled('search'), fn ($rows) => $rows->filter(fn ($row) => $this->matchesSearch($row, $data['search'])));

        return view('admin.medical-approvals.form', [
            'edition' => $edition, 'profile' => $profile, 'choices' => $choices, 'search' => $data['search'] ?? '',
            'creating' => true, 'statuses' => self::STATUSES,
            'entries' => $profile ? $profile['entries']->reject(fn ($entry) => $this->hasReview($entry)) : collect(),
            'version' => $profile ? $this->version($profile['entries']) : '',
        ]);
    }

    public function show(Student $student): View
    {
        $edition = $this->activeEdition();
        $entries = $this->entries($edition, $student);
        abort_if($entries->isEmpty(), 404);

        return view('admin.medical-approvals.show', [
            'edition' => $edition, 'profile' => $this->profiles($entries)->first(), 'statuses' => self::STATUSES,
            'version' => $this->version($entries), 'reviewedEntries' => $entries->filter(fn ($entry) => $this->hasReview($entry)),
        ]);
    }

    public function edit(Student $student): View
    {
        $edition = $this->activeEdition();
        $entries = $this->entries($edition, $student);
        abort_if($entries->isEmpty(), 404);

        return view('admin.medical-approvals.form', [
            'edition' => $edition, 'profile' => $this->profiles($entries)->first(), 'entries' => $entries,
            'creating' => false, 'statuses' => self::STATUSES, 'version' => $this->version($entries),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['student_id' => ['required', 'integer']]);

        return $this->save($request, Student::query()->findOrFail($request->integer('student_id')), true);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        return $this->save($request, $student, false);
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        return $this->save($request, $student, false, true);
    }

    private function save(Request $request, Student $student, bool $creating, bool $removing = false): RedirectResponse
    {
        $edition = $this->activeEdition();
        $data = $request->validate([
            'edition_id' => ['required', 'integer', Rule::in([$edition->id])],
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['required', 'integer', 'distinct'],
            'version' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'medical_certificate_status' => [$removing ? 'nullable' : 'required', Rule::in(array_keys(self::STATUSES))],
            'medical_certificate_notes' => ['nullable', 'string', 'max:1000'],
            'confirmation' => [$removing ? 'required' : 'nullable', 'in:REMOVE'],
        ], [
            'entry_ids.required' => 'Choose at least one sport to review.',
            'entry_ids.min' => 'Choose at least one sport to review.',
            'version.required' => 'Reload this page to get the current medical reviews.',
            'version.regex' => 'Reload this page to get the current medical reviews.',
            'edition_id.in' => 'The active edition has changed. Reload this page before saving.',
            'medical_certificate_status.required' => 'Choose a medical decision.',
            'confirmation.required' => 'Confirm that the selected reviews should be removed.',
            'confirmation.in' => 'Confirm that the selected reviews should be removed.',
        ], ['medical_certificate_notes' => 'review notes', 'medical_certificate_status' => 'medical decision']);
        DB::transaction(function () use ($student, $edition, $data, $creating, $removing, $request): void {
            $currentEdition = IntramuralEdition::query()->whereKey($edition->id)->lockForUpdate()->firstOrFail();
            abort_unless($currentEdition->status === 'active', 404);
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $entries = $this->entries($edition, $student, true);
            abort_if($entries->isEmpty(), 404);
            if (! hash_equals($this->version($entries), $data['version'])) {
                throw ValidationException::withMessages(['review' => 'These medical reviews or registrations changed. Reload the page before saving.']);
            }
            $selected = $entries->whereIn('id', $data['entry_ids']);
            if ($selected->count() !== count($data['entry_ids'])) {
                throw ValidationException::withMessages(['entry_ids' => 'Choose only this player’s current sports that require a medical certificate.']);
            }
            if ($creating && $selected->contains(fn ($entry) => $this->hasReview($entry))) {
                throw ValidationException::withMessages(['entry_ids' => 'A selected event already has a review. Use Edit review to change it.']);
            }
            if ($removing && $selected->contains(fn ($entry) => ! $this->hasReview($entry))) {
                throw ValidationException::withMessages(['entry_ids' => 'Choose only events with an existing review to remove.']);
            }
            $before = $selected->map(fn ($entry) => $entry->only($this->reviewFields()))->all();
            $status = $removing ? 'pending' : $data['medical_certificate_status'];
            foreach ($selected as $entry) {
                $entry->update([
                    'medical_certificate_status' => $status,
                    'medical_certificate_notes' => $removing ? null : ($data['medical_certificate_notes'] ?? null),
                    'medical_certificate_reviewed_by' => $removing ? null : $request->user()->id,
                    'medical_certificate_reviewed_at' => $removing ? null : now(),
                ]);
            }
            $this->audit->record('medical_approval.'.($removing ? 'deleted' : ($creating ? 'created' : 'updated')), $student,
                ['reviews' => $before], ['reviews' => $selected->map(fn ($entry) => $entry->only($this->reviewFields()))->all()]);
        });

        return redirect()->route('admin.medical-approvals.show', $student)
            ->with('success', $removing ? 'Selected reviews removed. Those events are now pending review.' : 'Medical review saved for '.$student->full_name.'.');
    }

    private function activeEdition(): IntramuralEdition
    {
        $edition = $this->standings->activeEdition();
        abort_unless($edition, 404, 'No active intramurals edition is available.');

        return $edition;
    }

    private function entries(IntramuralEdition $edition, ?Student $student = null, bool $lock = false): Collection
    {
        return AthleteEntry::query()->with([
            'editionSport.sport', 'student.course', 'medicalCertificateReviewer:id,name', 'team',
            'student.teamMembers' => fn ($query) => $query->where('edition_id', $edition->id)->with('team'),
        ])->where('status', 'active')->whereHas('student')
            ->whereHas('editionSport', fn ($query) => $query->where('edition_id', $edition->id)
                ->where('status', '!=', 'archived')->whereHas('sport', fn ($sport) => $sport->where('status', 'active')))
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
            ->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get()
            ->filter(fn ($entry) => $this->eligibility->requiresMedicalCertificate($entry->editionSport))->values();
    }

    private function profiles(Collection $entries): Collection
    {
        return $entries->groupBy('student_id')->map(function ($items): array {
            $student = $items->first()->student;
            $statuses = $items->map(fn ($entry) => $this->eligibility->medicalCertificateStatusFor($entry))->unique()->values();

            return [
                'student' => $student, 'entries' => $items, 'statuses' => $statuses,
                'status' => $statuses->count() === 1 ? $statuses->first() : 'mixed',
                'team' => $student->teamMembers->first()?->team ?? $items->first()->team,
                'reviewed' => $items->filter(fn ($entry) => $this->hasReview($entry))->count(),
            ];
        })->sortBy(fn ($profile) => Str::lower($profile['student']->last_name.' '.$profile['student']->first_name))->values();
    }

    private function matchesSearch(array $profile, string $search): bool
    {
        $student = $profile['student'];
        $text = implode(' ', [$student->full_name, $student->student_number, $student->course?->code,
            $profile['team']?->name, $profile['entries']->map(fn ($entry) => $entry->editionSport->sport->name)->implode(' ')]);

        return Str::contains(Str::lower($text), Str::lower(trim($search)));
    }

    private function hasReview(AthleteEntry $entry): bool
    {
        return in_array($entry->medical_certificate_status, ['verified', 'rejected'], true)
            || filled($entry->medical_certificate_notes) || $entry->medical_certificate_reviewed_by !== null
            || $entry->medical_certificate_reviewed_at !== null;
    }

    private function version(Collection $entries): string
    {
        return hash('sha256', $entries->map(fn ($entry) => [
            ...$entry->only($this->reviewFields()), 'updated_at' => $entry->getRawOriginal('updated_at'),
        ])->toJson());
    }

    private function reviewFields(): array
    {
        return ['id', 'medical_certificate_status', 'medical_certificate_notes',
            'medical_certificate_reviewed_by', 'medical_certificate_reviewed_at'];
    }
}
