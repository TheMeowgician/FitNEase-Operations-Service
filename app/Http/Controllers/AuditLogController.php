<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuditLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action_type' => 'required|in:create,read,update,delete,login,logout',
            'table_name' => 'required|string|max:50',
            'record_id' => 'nullable|integer',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
            'service_name' => 'nullable|string|max:50',
            'user_id' => 'nullable|integer'
        ]);

        // Get user ID from middleware if not provided
        $userId = $validated['user_id'] ?? $request->attributes->get('user_id');

        $auditLog = AuditLog::create([
            'user_id' => $userId,
            'action_type' => $validated['action_type'],
            'table_name' => $validated['table_name'],
            'record_id' => $validated['record_id'],
            'old_values' => $validated['old_values'],
            'new_values' => $validated['new_values'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'service_name' => $validated['service_name'] ?? 'fitneaseops',
            'timestamp' => now()
        ]);

        // Log the service communication
        Log::info('Audit log created via service communication', [
            'audit_log_id' => $auditLog->log_id,
            'user_id' => $userId,
            'action_type' => $validated['action_type'],
            'service_name' => $validated['service_name'] ?? 'fitneaseops',
            'caller_service' => $request->header('X-Calling-Service', 'unknown')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => $auditLog
        ], 201);
    }

    public function getUserLogs($userId): JsonResponse
    {
        $logs = AuditLog::where('user_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function getServiceLogs($serviceName): JsonResponse
    {
        $logs = AuditLog::where('service_name', $serviceName)
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function getLogsByAction($actionType): JsonResponse
    {
        $logs = AuditLog::where('action_type', $actionType)
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
