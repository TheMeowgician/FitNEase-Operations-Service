<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\APILog;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function getReportsByType($type): JsonResponse
    {
        $reports = Report::byType($type)
            ->notExpired()
            ->orderBy('generated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function generateReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_name' => 'required|string|max:255',
            'report_type' => 'required|in:user_progress,system_analytics,workout_performance,group_activity,ml_performance,service_health',
            'parameters' => 'nullable|array',
            'file_format' => 'nullable|in:pdf,excel,csv,json'
        ]);

        $reportData = $this->generateReportData($validated['report_type'], $validated['parameters'] ?? []);

        $report = Report::createReport(
            $validated['report_name'],
            $validated['report_type'],
            $reportData,
            $validated['parameters'],
            $validated['file_format'] ?? 'json'
        );

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $report
        ], 201);
    }

    public function getReport($reportId): JsonResponse
    {
        $report = Report::find($reportId);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
        }

        if ($report->expires_at && $report->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Report has expired'
            ], 410);
        }

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function deleteExpiredReports(): JsonResponse
    {
        $deletedCount = Report::where('expires_at', '<', now())->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} expired reports"
        ]);
    }

    private function generateReportData($type, $parameters): array
    {
        switch ($type) {
            case 'system_analytics':
                return $this->generateSystemAnalyticsReport($parameters);
            case 'service_health':
                return $this->generateServiceHealthReport($parameters);
            case 'ml_performance':
                return $this->generateMLPerformanceReport($parameters);
            default:
                return [
                    'type' => $type,
                    'generated_at' => now(),
                    'message' => 'Report type not yet implemented'
                ];
        }
    }

    private function generateSystemAnalyticsReport($parameters): array
    {
        $days = $parameters['days'] ?? 30;
        $startDate = Carbon::now()->subDays($days);

        $apiMetrics = APILog::getPerformanceMetrics($days);

        $errorAnalysis = APILog::where('timestamp', '>=', $startDate)
            ->where('status_code', '>=', 400)
            ->selectRaw('
                service_to,
                status_code,
                COUNT(*) as error_count
            ')
            ->groupBy('service_to', 'status_code')
            ->get();

        return [
            'report_type' => 'system_analytics',
            'period_days' => $days,
            'generated_at' => now(),
            'api_performance' => $apiMetrics,
            'error_analysis' => $errorAnalysis,
            'summary' => [
                'total_requests' => APILog::where('timestamp', '>=', $startDate)->count(),
                'total_errors' => APILog::where('timestamp', '>=', $startDate)->where('status_code', '>=', 400)->count(),
                'avg_response_time' => APILog::where('timestamp', '>=', $startDate)->avg('response_time_ms')
            ]
        ];
    }

    private function generateServiceHealthReport($parameters): array
    {
        $services = [
            'fitneaseauth', 'fitneasecontent', 'fitneasetracking',
            'fitneaseplanning', 'fitneasesocial', 'fitneaseml',
            'fitneaseengagement', 'fitneasecomms', 'fitneasemedia'
        ];

        $healthData = [];
        foreach ($services as $service) {
            $healthData[$service] = $this->getServiceHealthStatus($service);
        }

        return [
            'report_type' => 'service_health',
            'generated_at' => now(),
            'services' => $healthData,
            'overall_status' => $this->calculateOverallHealth($healthData)
        ];
    }

    private function generateMLPerformanceReport($parameters): array
    {
        return [
            'report_type' => 'ml_performance',
            'generated_at' => now(),
            'model_metrics' => [
                'content_based_accuracy' => 0.85,
                'collaborative_accuracy' => 0.82,
                'hybrid_accuracy' => 0.88
            ],
            'recommendation_stats' => [
                'total_recommendations' => 15000,
                'acceptance_rate' => 0.65,
                'avg_response_time_ms' => 150
            ]
        ];
    }

    private function getServiceHealthStatus($service): array
    {
        $recentLogs = APILog::where('service_to', $service)
            ->where('timestamp', '>=', Carbon::now()->subHours(1))
            ->get();

        if ($recentLogs->isEmpty()) {
            return [
                'status' => 'unknown',
                'message' => 'No recent activity'
            ];
        }

        $errorRate = $recentLogs->where('status_code', '>=', 400)->count() / $recentLogs->count();
        $avgResponseTime = $recentLogs->avg('response_time_ms');

        if ($errorRate > 0.1 || $avgResponseTime > 5000) {
            return [
                'status' => 'unhealthy',
                'error_rate' => $errorRate,
                'avg_response_time' => $avgResponseTime
            ];
        }

        return [
            'status' => 'healthy',
            'error_rate' => $errorRate,
            'avg_response_time' => $avgResponseTime
        ];
    }

    private function calculateOverallHealth($healthData): string
    {
        $unhealthyCount = collect($healthData)->filter(function ($health) {
            return $health['status'] === 'unhealthy';
        })->count();

        if ($unhealthyCount === 0) {
            return 'healthy';
        } elseif ($unhealthyCount <= 2) {
            return 'degraded';
        } else {
            return 'unhealthy';
        }
    }
}
