<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\APILog;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class APILogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:255',
            'http_method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'request_data' => 'nullable|array',
            'response_data' => 'nullable|array',
            'status_code' => 'required|integer',
            'response_time_ms' => 'nullable|integer',
            'service_from' => 'nullable|string|max:50',
            'service_to' => 'nullable|string|max:50',
            'user_id' => 'nullable|integer'
        ]);

        $apiLog = APILog::create([
            'user_id' => $validated['user_id'] ?? auth()->id(),
            'endpoint' => $validated['endpoint'],
            'http_method' => $validated['http_method'],
            'request_data' => $validated['request_data'],
            'response_data' => $validated['response_data'],
            'status_code' => $validated['status_code'],
            'response_time_ms' => $validated['response_time_ms'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'service_from' => $validated['service_from'],
            'service_to' => $validated['service_to'],
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API log created successfully',
            'data' => $apiLog
        ], 201);
    }

    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $service = $request->get('service');

        $query = APILog::where('timestamp', '>=', Carbon::now()->subDays($days));

        if ($service) {
            $query->where('service_to', $service);
        }

        $metrics = $query->selectRaw('
                service_to,
                AVG(response_time_ms) as avg_response_time,
                COUNT(*) as request_count,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count,
                SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as success_count
            ')
            ->groupBy('service_to')
            ->get()
            ->map(function($item) {
                return [
                    'service' => $item->service_to,
                    'avg_response_time' => round($item->avg_response_time, 2),
                    'total_requests' => $item->request_count,
                    'success_rate' => round(($item->success_count / $item->request_count) * 100, 2),
                    'error_rate' => round(($item->error_count / $item->request_count) * 100, 2)
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'period_days' => $days
        ]);
    }

    public function getErrorLogs(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);

        $errorLogs = APILog::where('timestamp', '>=', Carbon::now()->subHours($hours))
            ->where('status_code', '>=', 400)
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $errorLogs
        ]);
    }

    public function getServiceStats($serviceName): JsonResponse
    {
        $stats = APILog::where('service_to', $serviceName)
            ->where('timestamp', '>=', Carbon::now()->subDays(7))
            ->selectRaw('
                DATE(timestamp) as date,
                COUNT(*) as total_requests,
                AVG(response_time_ms) as avg_response_time,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
            ')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'service' => $serviceName,
            'data' => $stats
        ]);
    }
}
