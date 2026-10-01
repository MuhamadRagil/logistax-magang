<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, ?Model $subject, string $subjectLabel, array $metadata = []): ActivityLog
    {
        $actor = Auth::guard('web')->user();

        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Sistem',
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : 'Intern',
            'subject_id' => $subject?->id,
            'subject_label' => $subjectLabel,
            'metadata' => $metadata ?: null,
        ]);
    }
}
