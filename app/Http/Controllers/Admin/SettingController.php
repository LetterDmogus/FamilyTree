<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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
