<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;


class ActivityLogger
{
    public static function record(Model $subject, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'description' => $description,
        ]);
    }

    
    public static function recordUpdate(Model $subject, array $rawAttributesBeforeUpdate, string $label): void
    {
        $changes = $subject->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $parts = [];

        foreach ($changes as $field => $newValue) {
            $oldValue = $rawAttributesBeforeUpdate[$field] ?? null;
            $parts[] = "{$field}: ".self::stringify($oldValue).' → '.self::stringify($newValue);
        }

        self::record($subject, 'updated', "Actualizó {$label}: ".implode(', ', $parts));
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'sí' : 'no';
        }

        return (string) $value;
    }
}
