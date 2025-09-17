<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SystemSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SystemSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->setting_key => [
                'value' => SystemSetting::castValue($setting->setting_value, $setting->setting_type),
                'type' => $setting->setting_type,
                'description' => $setting->description,
                'category' => $setting->category,
                'is_public' => $setting->is_public,
                'requires_restart' => $setting->requires_restart
            ]];
        });

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateSetting(Request $request, $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'requires_restart' => 'nullable|boolean',
            'is_public' => 'nullable|boolean'
        ]);

        $oldSetting = SystemSetting::where('setting_key', $key)->first();
        $oldValue = $oldSetting ? $oldSetting->setting_value : null;

        $setting = SystemSetting::setSetting(
            $key,
            $validated['value'],
            $validated['description'] ?? null,
            $validated['category'] ?? null,
            $validated['requires_restart'] ?? false
        );

        if (isset($validated['is_public'])) {
            $setting->update(['is_public' => $validated['is_public']]);
        }

        AuditLog::logAction(
            'update',
            'system_settings',
            $setting->setting_id,
            [$key => $oldValue],
            [$key => $validated['value']],
            'fitneaseops'
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => $setting,
            'requires_restart' => $setting->requires_restart
        ]);
    }

    public function getSettingsByCategory($category): JsonResponse
    {
        // Log the category settings request for service communication monitoring
        Log::info('Operations Service - Category settings requested', [
            'category' => $category,
            'endpoint' => '/ops/settings/category/' . $category,
            'caller_service' => request()->header('X-Calling-Service', 'unknown'),
            'requested_at' => now()
        ]);

        $settings = SystemSetting::getSettingsByCategory($category);

        return response()->json([
            'success' => true,
            'category' => $category,
            'data' => $settings
        ]);
    }

    public function getPublicSettings(): JsonResponse
    {
        // Log the public settings request for service communication monitoring
        Log::info('Operations Service - Public settings requested', [
            'endpoint' => '/ops/settings-public',
            'caller_service' => request()->header('X-Calling-Service', 'unknown'),
            'requested_at' => now()
        ]);

        $settings = SystemSetting::getPublicSettings();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function backupConfiguration(): JsonResponse
    {
        $settings = SystemSetting::all();

        $backup = [
            'backup_timestamp' => now(),
            'settings_count' => $settings->count(),
            'settings' => $settings->mapWithKeys(function ($setting) {
                return [$setting->setting_key => [
                    'value' => $setting->setting_value,
                    'type' => $setting->setting_type,
                    'description' => $setting->description,
                    'category' => $setting->category,
                    'is_public' => $setting->is_public,
                    'requires_restart' => $setting->requires_restart
                ]];
            })
        ];

        AuditLog::logAction(
            'create',
            'system_settings_backup',
            null,
            null,
            ['backup_count' => $settings->count()],
            'fitneaseops'
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuration backup created',
            'data' => $backup
        ]);
    }

    public function getSetting($key): JsonResponse
    {
        $setting = SystemSetting::where('setting_key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $setting->setting_key,
                'value' => SystemSetting::castValue($setting->setting_value, $setting->setting_type),
                'type' => $setting->setting_type,
                'description' => $setting->description,
                'category' => $setting->category,
                'is_public' => $setting->is_public,
                'requires_restart' => $setting->requires_restart
            ]
        ]);
    }
}
