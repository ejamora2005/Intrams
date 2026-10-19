<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ActivityChanges
{
    public function capture(string $event, Model $model): void
    {
        $request = request();
        if (! $request->attributes->get('activity.capture') || $model instanceof AuditLog) {
            return;
        }
        $beforeKey = 'activity.before.'.$model::class.':'.$model->getKey();
        if (in_array($event, ['deleting', 'restoring'], true)) {
            $request->attributes->set($beforeKey, $model->getAttributes());

            return;
        }
        if ($event === 'deleted' && method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
            return;
        }
        $changes = $model->getChanges();
        if ($event === 'updated' && array_diff(array_keys($changes), ['updated_at', 'deleted_at']) === []) {
            return;
        }
        $before = match ($event) {
            'created' => null,
            'updated' => Arr::only($model->getRawOriginal(), array_keys($changes)),
            default => $request->attributes->get($beforeKey, $model->getRawOriginal()),
        };
        $after = match ($event) {
            'forceDeleted' => null,
            'deleted' => method_exists($model, 'trashed') ? $model->getAttributes() : null,
            'updated' => $changes,
            default => $model->getAttributes(),
        };
        $records = $request->attributes->get('activity.changes', []);
        $records[] = app(ActivitySanitizer::class)->sanitize([
            'event' => $event,
            'type' => class_basename($model),
            'id' => $model->getKey(),
            'before' => $before,
            'after' => $after,
        ]);
        $request->attributes->set('activity.changes', $records);
    }
}
