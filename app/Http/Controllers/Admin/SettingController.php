<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('manage_settings');

        return Inertia::render('Admin/Settings/WiseTree', [
            'settings' => Setting::all()->mapWithKeys(function ($s) {
                return [$s->key => [
                    'id' => $s->id,
                    'value' => $s->cast_value,
                    'type' => $s->type,
                    'description' => $s->description,
                ]];
            }),
        ]);
    }

    public function downloadBackup()
    {
        $this->authorize('manage_settings');

        $tables = DB::select('SHOW TABLES');
        $databaseName = config('database.connections.mysql.database');
        $property = "Tables_in_{$databaseName}";

        $siteName = Setting::getValue('site_name', 'Wise Mystical Tree');
        $output = "-- {$siteName} Database Backup\n";
        $output .= '-- Generated: '.now()->toDateTimeString()."\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$property;

            // Create table
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
            $createTableKey = 'Create Table';
            $output .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $output .= $createTable->$createTableKey.";\n\n";

            // Get data
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $columns = array_keys($rowArray);
                $values = array_map(function ($value) {
                    if (is_null($value)) {
                        return 'NULL';
                    }

                    return "'".addslashes($value)."'";
                }, array_values($rowArray));

                $output .= "INSERT INTO `{$tableName}` (`".implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n";
            }
            $output .= "\n";
        }

        $output .= 'SET FOREIGN_KEY_CHECKS=1;';

        $filename = 'backup-'.now()->format('Y-m-d-His').'.sql';

        Setting::logSystemAction("Mengunduh backup database: {$filename}");

        return response($output)
            ->header('Content-Type', 'application/sql')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function update(Request $request)
    {
        $this->authorize('manage_settings');

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'present',
        ]);

        foreach ($validated['settings'] as $s) {
            $setting = Setting::where('key', $s['key'])->first();
            if ($setting) {
                $value = $s['value'];

                // Robust type-aware conversion
                if ($setting->type === 'boolean') {
                    $newValue = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                } elseif ($setting->type === 'integer') {
                    $newValue = (string) (int) $value;
                } else {
                    $newValue = (string) $value;
                }

                $setting->update(['value' => $newValue]);
            }
        }

        return back()->with('success', 'Aturan Wise Mystical Tree berhasil diperbarui.');
    }
}
