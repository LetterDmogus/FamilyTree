<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            self::logAction($model, 'CREATE', 'Menambahkan data '.self::getModelName($model));
        });

        static::updated(function (Model $model) {
            // Only log if something actually changed
            if ($model->wasChanged()) {
                self::logAction($model, 'UPDATE', 'Memperbarui data '.self::getModelName($model), [
                    'old' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                    'new' => $model->getChanges(),
                ]);
            }
        });

        static::deleted(function (Model $model) {
            self::logAction($model, 'DELETE', 'Menghapus data '.self::getModelName($model));
        });
    }

    protected static function logAction(Model $model, string $action, string $description, array $properties = [])
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Helper for manual system logging (e.g. Backups)
     */
    public static function logSystemAction(string $description, array $properties = [])
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'SYSTEM',
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected static function getModelName(Model $model): string
    {
        $class = class_basename($model);

        return match ($class) {
            'User' => 'Anggota Keluarga',
            'Relation' => 'Hubungan Silsilah',
            'Setting' => 'Pengaturan Website',
            default => $class
        };
    }
}
