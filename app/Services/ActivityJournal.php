<?php

namespace App\Services;

use App\Models\AthleteEntry;
use App\Models\AuditLog;
use App\Models\BracketCompetitor;
use App\Models\BracketMatch;
use App\Models\CompetitionSchedule;
use App\Models\Course;
use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Models\SportResult;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActivityJournal
{
    public const TYPES = ['results' => 'Winner declarations / results', 'medical' => 'Medical reviews', 'schedule' => 'Schedules', 'bracket' => 'Brackets', 'create' => 'Records created', 'update' => 'Records updated', 'delete' => 'Records removed'];

    private const DOMAIN_ENDINGS = ['created', 'updated', 'deleted', 'removed', 'archived', 'restored', 'assigned', 'transferred', 'revoked', 'withdrawn', 'approved', 'rejected', 'submitted', 'reviewed', 'winners', 'completed', 'reset', 'reordered', 'scheduled', 'scoring_rules_updated', 'three_place_scoring_applied', 'basketball_3x3_points_corrected', 'points_recalculated', 'team_transferred', 'bracket_link_updated'];

    private const MODEL_LABELS = ['Student' => 'Student', 'User' => 'Staff account', 'Course' => 'Course', 'Team' => 'Faction', 'Sport' => 'Sport / cultural event', 'EditionSport' => 'Event configuration', 'IntramuralEdition' => 'Intramurals edition', 'AthleteEntry' => 'Sport registration', 'TeamMember' => 'Faction membership', 'BracketMatch' => 'Match', 'BracketCompetitor' => 'Bracket competitor', 'CompetitionSchedule' => 'Schedule', 'PublicProgramme' => 'Public schedule', 'SportResult' => 'Event result', 'ParticipationRule' => 'Participation rule', 'Event' => 'Event', 'Registration' => 'Event registration', 'CoordinatorRequest' => 'Coordinator request', 'CoordinatorAssignment' => 'Coordinator assignment', 'ResultSubmission' => 'Result submission'];

    private array $models = [];

    public function query(): Builder
    {
        return AuditLog::query()->where(function ($query): void {
            $query->where(function ($requests): void {
                $this->requestScope($requests, 'audit_logs');
                $requests->where(function ($changed): void {
                    $changed->whereJsonLength('new_values->changes', '>', 0)
                        ->orWhereExists(function ($domain): void {
                            $domain->selectRaw('1')->from('audit_logs as journal_domain')
                                ->whereColumn('journal_domain.request_id', 'audit_logs.request_id');
                            $this->domainScope($domain, 'journal_domain');
                        });
                });
            })->orWhere(function ($domain): void {
                $this->domainScope($domain, 'audit_logs');
                $domain->where(function ($standalone): void {
                    $standalone->whereNull('request_id')->orWhereNotExists(function ($request): void {
                        $request->selectRaw('1')->from('audit_logs as journal_request')
                            ->whereColumn('journal_request.request_id', 'audit_logs.request_id');
                        $this->requestScope($request, 'journal_request');
                    });
                });
            });
        });
    }

    private function requestScope($query, string $table): void
    {
        $query->whereIn($table.'.new_values->request->method', ['POST', 'PUT', 'PATCH', 'DELETE'])
            ->where(function ($workspace) use ($table): void {
                foreach (['admin', 'gam', 'tabulator', 'coordinator'] as $role) {
                    $workspace->orWhere($table.'.action', 'like', 'request.'.$role.'.%');
                }
            })->where($table.'.action', 'not like', '%.download')
            ->where($table.'.action', 'not like', '%.export')->where($table.'.action', 'not like', '%.preview');
    }

    private function domainScope($query, string $table): void
    {
        $query->where($table.'.action', 'not like', 'request.%')->where($table.'.action', 'not like', 'auth.%')
            ->where(function ($actions) use ($table): void {
                foreach (self::DOMAIN_ENDINGS as $ending) {
                    $actions->orWhere($table.'.action', 'like', '%.'.$ending);
                }
            });
    }

    public function filter(Builder $query, string $type): void
    {
        $patterns = match ($type) {
            'results' => ['%sport-results.%', '%matches.result', '%bracket.result', 'result.%', 'sport_result.%', 'sport.winners', 'bracket.match.completed'],
            'medical' => ['%medical%'],
            'schedule' => ['%schedule%', 'public_schedule.%'],
            'bracket' => ['%bracket%', '%matches.result'],
            'create' => ['%.store', '%.created', '%.assign', '%.assigned'],
            'delete' => ['%.destroy', '%.deleted', '%.removed', '%.archived', '%.withdraw', '%.withdrawn', '%.revoke', '%.revoked'],
            default => [],
        };
        if ($type === 'update') {
            $query->where(function ($updates): void {
                foreach (['%.store', '%.created', '%.assign', '%.assigned', '%.destroy', '%.deleted', '%.removed', '%.archived', '%.withdraw', '%.withdrawn', '%.revoke', '%.revoked'] as $pattern) {
                    $updates->where('action', 'not like', $pattern);
                }
            });
        } elseif ($patterns !== []) {
            $query->where(function ($actions) use ($patterns): void {
                foreach ($patterns as $pattern) {
                    $actions->orWhere('action', 'like', $pattern);
                }
            });
        }
        if ($type === 'results') {
            $query->where('action', 'not like', '%.points_recalculated');
        }
    }

    public function presentations(Collection $logs): Collection
    {
        $this->loadContext($logs->filter(fn ($log) => ($log->new_values['activity']['format'] ?? null) !== 1));

        return $logs->map(function (AuditLog $log): array {
            $saved = $log->new_values['activity'] ?? null;

            return is_array($saved) && ($saved['format'] ?? null) === 1 ? $saved : $this->describe($log);
        });
    }

    /** Save a readable snapshot while the affected records and winner names still exist. */
    public function snapshot(string $action, $subject, array $details): ?array
    {
        if (! in_array($details['request']['method'] ?? '', ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || ! preg_match('/^request\.(admin|gam|tabulator|coordinator)\./', $action)
            || empty($details['changes']) || Str::endsWith($action, ['.download', '.export', '.preview'])) {
            return null;
        }
        $log = new AuditLog(['action' => $action, 'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(), 'new_values' => $details]);

        return $this->presentations(collect([$log]))->first();
    }

    private function describe(AuditLog $log): array
    {
        $data = is_array($log->new_values) ? $log->new_values : [];
        $input = $data['request']['input'] ?? [];
        $parameters = $data['request']['parameters'] ?? [];
        $changes = collect($data['changes'] ?? []);
        $action = Str::after($log->action, 'request.');
        $first = $changes->first(fn ($change) => ! in_array($change['type'] ?? '', ['TeamTally', 'AuditLog'], true));
        $type = $first['type'] ?? class_basename($log->auditable_type ?? '');
        if (! isset(self::MODEL_LABELS[$type])) {
            foreach (['student.' => 'Student', 'course.' => 'Course', 'team.' => 'Team', 'sport.' => 'Sport', 'edition.' => 'IntramuralEdition', 'athlete_entry.' => 'AthleteEntry'] as $prefix => $modelType) {
                if (Str::startsWith($action, $prefix)) {
                    $type = $modelType;
                    break;
                }
            }
        }
        $attributes = array_merge((array) ($first['before'] ?? $log->old_values ?? []), (array) ($first['after'] ?? $data));
        $subject = $this->subject($type, $first['id'] ?? $log->auditable_id, $attributes, $parameters);
        $verb = Str::endsWith($action, ['.store', '.created', '.assigned', '.assign']) ? 'created'
            : (Str::endsWith($action, ['.destroy', '.deleted', '.removed', '.archived', '.withdraw', '.withdrawn', '.revoked', '.revoke']) ? 'removed' : 'updated');
        $title = (self::MODEL_LABELS[$type] ?? 'Record').' '.$verb;
        $details = [];
        $result = $changes->firstWhere('type', 'SportResult');
        $match = $changes->firstWhere('type', 'BracketMatch');
        $pointsUpdate = Str::contains($action, ['sports-points', 'scoring_rules', 'points_recalculated', 'scoring_applied', 'points_corrected']);
        if (! $pointsUpdate && ($result || Str::contains($action, ['sport-results', 'sport_result.', 'sport.winners']))) {
            $values = $result['after'] ?? $data;
            $eventId = $values['edition_sport_id'] ?? $input['edition_sport_id'] ?? ($result['before']['edition_sport_id'] ?? null);
            $subject = $this->label('EditionSport', $eventId, $subject);
            $title = $result && ($result['event'] ?? '') === 'updated' ? 'Event results revised' : 'Event winners declared';
            foreach (($values['placements_json'] ?? $input['placements'] ?? []) as $place => $teamId) {
                if (! is_numeric($teamId)) {
                    continue;
                }
                $points = $values['points_json'][$place] ?? [];
                $placement = is_array($points) ? ($points['label'] ?? null) : null;
                $placement ??= match ((int) $place) {
                    1 => 'Champion', 2 => 'First runner-up', 3 => 'Second runner-up', default => 'Place '.$place
                };
                $details[] = $placement.': '.$this->label('Team', $teamId).(is_array($points) && isset($points['points']) ? ' · '.$points['points'].' points' : '');
            }
        } elseif (Str::startsWith($action, 'result.')) {
            $title = 'Result submission '.Str::afterLast($action, '.');
        } elseif (Str::contains($action, ['matches.result', 'bracket.result', 'bracket.match.completed'])) {
            $values = array_merge((array) ($match['before'] ?? []), (array) ($match['after'] ?? $data));
            $matchId = $match['id'] ?? $parameters['match']['id'] ?? $log->auditable_id;
            $subject = $this->label('BracketMatch', $matchId, $subject);
            $winner = $values['winner_competitor_id'] ?? $input['winner_competitor_id'] ?? null;
            $title = 'Match winner declared';
            if ($winner) {
                $details[] = 'Winner: '.$this->label('BracketCompetitor', $winner);
            } elseif (! empty($values['winner_team_id'])) {
                $details[] = 'Winner: '.$this->label('Team', $values['winner_team_id']);
            }
        } elseif (Str::contains($action, 'medical')) {
            $status = $input['medical_certificate_status'] ?? $data['reviews'][0]['medical_certificate_status'] ?? null;
            $title = $verb === 'removed' ? 'Medical review removed' : match ($status) {
                'verified' => 'Medical clearance approved', 'rejected' => 'Medical clearance rejected', default => 'Medical review updated',
            };
            $subject = $parameters['student']['label'] ?? $this->label('Student', $input['student_id'] ?? $attributes['student_id'] ?? null, $subject);
            if ($changes->isNotEmpty()) {
                $details = $changes->where('type', 'AthleteEntry')->map(fn ($change) => $this->label('AthleteEntry', $change['id']))->unique()->values()->all();
            }
        } elseif (Str::contains($action, ['schedule', 'public_schedule'])) {
            $title = 'Schedule '.$verb;
            $subject = $input['event'] ?? $input['title'] ?? $attributes['event'] ?? $attributes['title'] ?? $subject;
        } elseif ($pointsUpdate) {
            $title = 'Points configuration updated';
        } elseif (Str::contains($action, ['players.sports.update', 'students.sports'])) {
            $title = 'Player sports updated';
            $subject = $parameters['student']['label'] ?? $subject;
        } elseif (Str::contains($action, ['players.assign', 'team.member.assigned'])) {
            $title = 'Player assigned to faction';
            $subject = $this->label('Student', $input['student_id'] ?? $attributes['student_id'] ?? null, $subject);
            $details[] = 'Faction: '.$this->label('Team', $parameters['team']['id'] ?? $attributes['team_id'] ?? null);
        } elseif (Str::contains($action, ['bracket', 'matches.result'])) {
            $title = Str::endsWith($action, '.reset') ? 'Bracket cleared' : 'Bracket lineup updated';
        }
        if ($details === []) {
            $details[] = match ($verb) {
                'created' => 'A new record was added.', 'removed' => 'The selected record was removed or archived.', default => 'The record was updated.'
            };
        }

        return ['format' => 1, 'title' => $title, 'subject' => Str::limit((string) $subject, 240),
            'details' => array_slice($details, 0, 20), 'changes' => $this->fieldChanges($changes, $log)];
    }

    private function fieldChanges(Collection $changes, AuditLog $log): array
    {
        if ($changes->isEmpty()) {
            $changes = collect([['type' => class_basename($log->auditable_type ?? ''), 'id' => $log->auditable_id, 'before' => $log->old_values ?? [], 'after' => $log->new_values ?? []]]);
        }
        $fields = ['name' => 'Name', 'first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name', 'student_number' => 'Student ID', 'status' => 'Status', 'gender' => 'Gender', 'school_year' => 'School year', 'year_level' => 'Year level', 'section' => 'Section', 'role' => 'Role', 'team_id' => 'Faction', 'course_id' => 'Course', 'title' => 'Event title', 'date' => 'Date', 'session' => 'Session', 'starts_at' => 'Start time', 'ends_at' => 'End time', 'venue' => 'Venue', 'is_published' => 'Public visibility', 'medical_certificate_status' => 'Medical decision', 'medical_certificate_notes' => 'Review notes', 'participant_type' => 'Participants', 'game_mechanic' => 'Competition format'];
        $rows = [];
        foreach ($changes as $change) {
            if (in_array($change['type'] ?? '', ['TeamTally', 'AuditLog'], true)) {
                continue;
            }
            $before = (array) ($change['before'] ?? []);
            $after = (array) ($change['after'] ?? []);
            foreach ($fields as $key => $label) {
                if ((! array_key_exists($key, $before) && ! array_key_exists($key, $after)) || ($before[$key] ?? null) === ($after[$key] ?? null)) {
                    continue;
                }
                $rows[] = ['record' => $this->subject($change['type'] ?? '', $change['id'] ?? null, array_merge($before, $after), []), 'field' => $label,
                    'before' => $this->value($key, $before[$key] ?? null), 'after' => $this->value($key, $after[$key] ?? null)];
                if (count($rows) >= 30) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    private function value(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return count($value).' items';
        }
        if ($key === 'team_id') {
            return $this->label('Team', $value);
        }
        if ($key === 'course_id') {
            return $this->label('Course', $value);
        }
        if ($key === 'medical_certificate_status') {
            return ['verified' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected', 'not_required' => 'Not required'][$value] ?? Str::headline($value);
        }
        if (in_array($key, ['starts_at', 'ends_at'], true)) {
            try {
                return \Carbon\CarbonImmutable::parse($value, 'Asia/Manila')->timezone('Asia/Manila')->format('M j, Y g:i A');
            } catch (\Throwable) {
            }
        }
        if (in_array($key, ['status', 'role', 'participant_type', 'game_mechanic'], true)) {
            return Str::headline((string) $value);
        }

        return Str::limit((string) $value, 300);
    }

    private function subject(string $type, mixed $id, array $values, array $parameters): string
    {
        foreach ($parameters as $parameter) {
            if (is_array($parameter) && ($parameter['type'] ?? '') === $type && filled($parameter['label'] ?? null)) {
                return (string) $parameter['label'];
            }
        }
        if ($type === 'Student' && filled($values['first_name'] ?? null)) {
            return collect(['first_name', 'middle_name', 'last_name'])->map(fn ($key) => $values[$key] ?? null)->filter()->implode(' ');
        }
        foreach (['name', 'title', 'label', 'player', 'student', 'event'] as $key) {
            if (is_string($values[$key] ?? null) && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return $this->label($type, $id);
    }

    private function label(string $type, mixed $id, ?string $fallback = null): string
    {
        $model = $this->models[$type][(int) $id] ?? null;
        if ($model) {
            return match ($type) {
                'Student' => $model->full_name,
                'EditionSport' => $model->sport?->name ?? 'Event #'.$id,
                'SportResult' => $model->editionSport?->sport?->name ?? 'Event result #'.$id,
                'AthleteEntry' => ($model->student?->full_name ?? 'Player').' · '.($model->editionSport?->sport?->name ?? 'Sport registration'),
                'BracketMatch' => ($model->editionSport?->sport?->name ?? 'Match').' · Round '.$model->round_number.' · Game '.$model->match_number,
                'BracketCompetitor' => $model->label ?: 'Bracket competitor #'.$id,
                'CompetitionSchedule' => $model->title ?: 'Schedule #'.$id,
                default => $model->name ?? (self::MODEL_LABELS[$type] ?? 'Record').' #'.$id,
            };
        }

        return $fallback ?: (self::MODEL_LABELS[$type] ?? 'Record').($id ? ' #'.$id : '');
    }

    private function loadContext(Collection $logs): void
    {
        $this->models = [];
        $ids = [];
        $add = function ($type, $id) use (&$ids): void {
            if (is_string($type) && is_numeric($id) && (int) $id > 0) {
                $ids[$type][(int) $id] = (int) $id;
            }
        };
        $scan = function ($values, $key = '') use (&$scan, $add): void {
            if (! is_array($values)) {
                return;
            }
            if (isset($values['type'], $values['id'])) {
                $add($values['type'], $values['id']);
            }
            foreach ($values as $field => $value) {
                $type = ['edition_sport_id' => 'EditionSport', 'sport_id' => 'Sport', 'student_id' => 'Student', 'team_id' => 'Team', 'winner_team_id' => 'Team', 'team_one_id' => 'Team', 'team_two_id' => 'Team', 'course_id' => 'Course', 'winner_competitor_id' => 'BracketCompetitor'][$field] ?? null;
                if ($type) {
                    $add($type, $value);
                }
                if (in_array($field, ['placements', 'placements_json'], true) && is_array($value)) {
                    foreach ($value as $teamId) {
                        $add('Team', $teamId);
                    }
                }
                if (is_array($value)) {
                    $scan($value, (string) $field);
                }
            }
        };
        foreach ($logs as $log) {
            $add(class_basename($log->auditable_type ?? ''), $log->auditable_id);
            $scan($log->old_values ?? []);
            $scan($log->new_values ?? []);
            foreach (($log->new_values['changes'] ?? []) as $change) {
                $add($change['type'] ?? '', $change['id'] ?? null);
            }
        }
        $classes = ['Student' => Student::class, 'User' => User::class, 'Course' => Course::class, 'Team' => Team::class, 'Sport' => Sport::class, 'IntramuralEdition' => IntramuralEdition::class, 'EditionSport' => EditionSport::class, 'AthleteEntry' => AthleteEntry::class, 'BracketMatch' => BracketMatch::class, 'BracketCompetitor' => BracketCompetitor::class, 'CompetitionSchedule' => CompetitionSchedule::class, 'SportResult' => SportResult::class];
        foreach ($classes as $type => $class) {
            if (empty($ids[$type])) {
                continue;
            }
            $query = $class::query()->whereIn('id', array_values($ids[$type]));
            if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                $query->withTrashed();
            }
            if (in_array($type, ['EditionSport', 'AthleteEntry', 'BracketMatch', 'SportResult'], true)) {
                $relation = $type === 'EditionSport' ? 'sport' : 'editionSport.sport';
                $query->with([$relation => fn ($sport) => $sport->withTrashed()]);
            }
            if ($type === 'AthleteEntry') {
                $query->with(['student' => fn ($student) => $student->withTrashed()]);
            }
            $this->models[$type] = $query->get($type === 'User' ? ['id', 'name'] : ['*'])->keyBy('id')->all();
        }
    }
}
