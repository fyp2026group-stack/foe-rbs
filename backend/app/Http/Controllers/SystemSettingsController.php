<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    // List all system settings
    public function index()
    {
        $rows = SystemSetting::all();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r->key] = $r->value;
        }
        return response()->json($settings);
    }

    // Update system settings
    public function update(Request $request)
    {
        $data = $request->except('logo');

        // Update text settings
        foreach ($data as $k => $v) {
            SystemSetting::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        // Handle logo update and cleanup
        if ($request->hasFile('logo')) {
            $oldSetting = SystemSetting::where('key', 'logo')->first();

            if ($oldSetting && $oldSetting->value) {
                // Delete existing file
                if (Storage::disk('public')->exists($oldSetting->value)) {
                    Storage::disk('public')->delete($oldSetting->value);
                }
            }

            $path = $request->file('logo')->store('logos', 'public');
            SystemSetting::updateOrCreate(['key' => 'logo'], ['value' => $path, 'type' => 'file']);
        }

        return $this->index();
    }

    // Perform quick actions like clearing cache or logs
    public function action(Request $request, $action)
    {
        // quick actions: clear-cache, backup-db, clear-logs
        if ($action === 'clear-cache') {
            \Artisan::call('cache:clear');
            return response()->json(['message' => 'Cache cleared']);
        }
        if ($action === 'clear-logs') {
            // naive clear logs
            foreach (glob(storage_path('logs') . '/*.log') as $f) {
                @unlink($f);
            }
            return response()->json(['message' => 'Logs cleared']);
        }
        // implement backup/db depending on your infra
        return response()->json(['message' => 'Not implemented'], 400);
    }
}