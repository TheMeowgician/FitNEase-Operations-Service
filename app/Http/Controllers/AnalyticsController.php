<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\APILog;
use App\Models\Report;
use Exception;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function getBusinessMetrics(Request $request): JsonResponse
    {
        $dateRange = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($dateRange);

        // Log the business metrics request
        Log::info('Operations Service - Business metrics requested', [
            'period_days' => $dateRange,
            'start_date' => $startDate->toDateString(),
            'caller_service' => $request->header('X-Calling-Service', 'unknown'),
            'requested_at' => now()
        ]);

        $metrics = [
            'user_metrics' => $this->getUserMetrics($startDate),
            'workout_metrics' => $this->getWorkoutMetrics($startDate),
            'engagement_metrics' => $this->getEngagementMetrics($startDate),
            'technical_metrics' => $this->getTechnicalMetrics($startDate),
            'ml_effectiveness' => $this->getMLEffectivenessMetrics($startDate)
        ];

        return response()->json([
            'success' => true,
            'period_days' => $dateRange,
            'data' => $metrics
        ]);
    }

    public function getMLPerformance(Request $request): JsonResponse
    {
        $dateRange = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($dateRange);

        try {
            $mlClient = new Client();
            $mlServiceUrl = env('ML_SERVICE_URL');

            if (!$mlServiceUrl) {
                throw new Exception('ML service URL not configured');
            }

            $response = $mlClient->get($mlServiceUrl . '/api/v1/effectiveness-metrics', [
                'query' => ['start_date' => $startDate->toDateString()],
                'timeout' => 10
            ]);

            $mlData = json_decode($response->getBody(), true);

            $performance = [
                'recommendation_acceptance_rate' => $mlData['acceptance_rate'] ?? 0,
                'model_accuracy_trend' => $mlData['accuracy_trend'] ?? [],
                'personalization_effectiveness' => $mlData['personalization_score'] ?? 0,
                'algorithm_performance' => [
                    'content_based' => $mlData['content_based_performance'] ?? 0,
                    'collaborative' => $mlData['collaborative_performance'] ?? 0,
                    'hybrid' => $mlData['hybrid_performance'] ?? 0
                ],
                'response_time_metrics' => $this->getMLResponseTimeMetrics($startDate)
            ];

            return response()->json([
                'success' => true,
                'period_days' => $dateRange,
                'data' => $performance
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch ML performance metrics',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    public function generateSystemPerformanceReport(): JsonResponse
    {
        // Log the system performance report generation
        Log::info('Operations Service - System performance report generation requested', [
            'report_type' => 'system_performance',
            'period' => '30_days',
            'generated_at' => now()
        ]);
        $report = [
            'report_info' => [
                'type' => 'system_performance',
                'generated_at' => now(),
                'period' => '30_days'
            ],
            'service_performance' => $this->getServicePerformanceMetrics(),
            'api_performance' => $this->getAPIPerformanceMetrics(),
            'error_analysis' => $this->getErrorAnalysis(),
            'resource_utilization' => $this->getResourceUtilization(),
            'recommendations' => $this->generatePerformanceRecommendations()
        ];

        $reportRecord = Report::createReport(
            'System Performance Report - ' . now()->format('Y-m-d'),
            'system_analytics',
            $report,
            ['period_days' => 30],
            'json',
            90
        );

        return response()->json([
            'success' => true,
            'message' => 'System performance report generated',
            'report_id' => $reportRecord->report_id,
            'data' => $report
        ]);
    }

    public function getServiceSpecificMetrics($serviceName): JsonResponse
    {
        $metrics = $this->getServiceMetrics($serviceName);

        return response()->json([
            'success' => true,
            'service' => $serviceName,
            'data' => $metrics
        ]);
    }

    private function getUserMetrics($startDate): array
    {
        try {
            $authClient = new Client();
            $authServiceUrl = env('AUTH_SERVICE_URL');

            if (!$authServiceUrl) {
                return ['error' => 'Auth service URL not configured'];
            }

            $response = $authClient->get($authServiceUrl . '/auth/user-analytics', [
                'query' => ['start_date' => $startDate->toDateString()],
                'timeout' => 10
            ]);

            $userData = json_decode($response->getBody(), true);

            return [
                'new_registrations' => $userData['new_users'] ?? 0,
                'active_users' => $userData['active_users'] ?? 0,
                'retention_rate' => $userData['retention_rate'] ?? 0,
                'user_growth_rate' => $this->calculateGrowthRate($userData, 'users')
            ];
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch user metrics: ' . $e->getMessage()];
        }
    }

    private function getWorkoutMetrics($startDate): array
    {
        try {
            $trackingClient = new Client();
            $trackingServiceUrl = env('TRACKING_SERVICE_URL');

            if (!$trackingServiceUrl) {
                return ['error' => 'Tracking service URL not configured'];
            }

            $response = $trackingClient->get($trackingServiceUrl . '/tracking/analytics', [
                'query' => ['start_date' => $startDate->toDateString()],
                'timeout' => 10
            ]);

            $workoutData = json_decode($response->getBody(), true);

            return [
                'total_workouts_completed' => $workoutData['total_completed'] ?? 0,
                'average_workout_duration' => $workoutData['avg_duration'] ?? 0,
                'completion_rate' => $workoutData['completion_rate'] ?? 0,
                'most_popular_exercises' => $workoutData['popular_exercises'] ?? []
            ];
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch workout metrics: ' . $e->getMessage()];
        }
    }

    private function getEngagementMetrics($startDate): array
    {
        try {
            $engagementClient = new Client();
            $engagementServiceUrl = env('ENGAGEMENT_SERVICE_URL');

            if (!$engagementServiceUrl) {
                return ['error' => 'Engagement service URL not configured'];
            }

            $response = $engagementClient->get($engagementServiceUrl . '/engagement/analytics', [
                'query' => ['start_date' => $startDate->toDateString()],
                'timeout' => 10
            ]);

            $engagementData = json_decode($response->getBody(), true);

            return [
                'daily_active_users' => $engagementData['daily_active_users'] ?? 0,
                'session_duration_avg' => $engagementData['avg_session_duration'] ?? 0,
                'achievements_earned' => $engagementData['achievements_earned'] ?? 0,
                'social_interactions' => $engagementData['social_interactions'] ?? 0
            ];
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch engagement metrics: ' . $e->getMessage()];
        }
    }

    private function getTechnicalMetrics($startDate): array
    {
        $apiMetrics = APILog::getPerformanceMetrics(30);

        return [
            'total_api_requests' => APILog::where('timestamp', '>=', $startDate)->count(),
            'average_response_time' => APILog::where('timestamp', '>=', $startDate)->avg('response_time_ms'),
            'error_rate' => $this->calculateErrorRate($startDate),
            'service_availability' => $this->calculateServiceAvailability($startDate),
            'performance_by_service' => $apiMetrics
        ];
    }

    private function getMLEffectivenessMetrics($startDate): array
    {
        try {
            $mlClient = new Client();
            $mlServiceUrl = env('ML_SERVICE_URL');

            if (!$mlServiceUrl) {
                return ['error' => 'ML service URL not configured'];
            }

            $response = $mlClient->get($mlServiceUrl . '/api/v1/effectiveness-metrics', [
                'query' => ['start_date' => $startDate->toDateString()],
                'timeout' => 10
            ]);

            $mlData = json_decode($response->getBody(), true);

            return [
                'recommendation_acceptance_rate' => $mlData['acceptance_rate'] ?? 0,
                'model_accuracy_trend' => $mlData['accuracy_trend'] ?? [],
                'personalization_effectiveness' => $mlData['personalization_score'] ?? 0,
                'algorithm_performance' => [
                    'content_based' => $mlData['content_based_performance'] ?? 0,
                    'collaborative' => $mlData['collaborative_performance'] ?? 0,
                    'hybrid' => $mlData['hybrid_performance'] ?? 0
                ]
            ];
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch ML effectiveness metrics: ' . $e->getMessage()];
        }
    }

    private function getServicePerformanceMetrics(): array
    {
        $services = ['fitneaseauth', 'fitneasecontent', 'fitneasetracking', 'fitneaseplanning', 'fitneasesocial', 'fitneaseml', 'fitneaseengagement', 'fitneasecomms', 'fitneasemedia'];
        $performance = [];

        foreach ($services as $service) {
            $performance[$service] = $this->getServiceMetrics($service);
        }

        return $performance;
    }

    private function getServiceMetrics($serviceName): array
    {
        $recentLogs = APILog::where('service_to', $serviceName)
            ->where('timestamp', '>=', Carbon::now()->subDays(7))
            ->get();

        if ($recentLogs->isEmpty()) {
            return [
                'status' => 'no_data',
                'message' => 'No recent activity'
            ];
        }

        return [
            'total_requests' => $recentLogs->count(),
            'avg_response_time' => round($recentLogs->avg('response_time_ms'), 2),
            'error_rate' => round(($recentLogs->where('status_code', '>=', 400)->count() / $recentLogs->count()) * 100, 2),
            'success_rate' => round(($recentLogs->where('status_code', '<', 400)->count() / $recentLogs->count()) * 100, 2),
            'last_activity' => $recentLogs->max('timestamp')
        ];
    }

    private function getAPIPerformanceMetrics(): array
    {
        return APILog::where('timestamp', '>=', Carbon::now()->subDays(30))
            ->selectRaw('
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
            })->toArray();
    }

    private function getErrorAnalysis(): array
    {
        $startTime = Carbon::now()->subDays(30);

        return [
            'total_errors' => APILog::where('timestamp', '>=', $startTime)->where('status_code', '>=', 400)->count(),
            'by_service' => $this->getErrorsByService($startTime),
            'by_status_code' => $this->getErrorsByStatusCode($startTime),
            'critical_errors' => $this->getCriticalErrors($startTime)
        ];
    }

    private function getResourceUtilization(): array
    {
        return [
            'database_connections' => 'monitoring_not_implemented',
            'memory_usage' => 'monitoring_not_implemented',
            'cpu_usage' => 'monitoring_not_implemented',
            'storage_usage' => 'monitoring_not_implemented'
        ];
    }

    private function generatePerformanceRecommendations(): array
    {
        $recommendations = [];
        $apiMetrics = $this->getAPIPerformanceMetrics();

        foreach ($apiMetrics as $service) {
            if ($service['avg_response_time'] > 2000) {
                $recommendations[] = [
                    'type' => 'performance',
                    'service' => $service['service'],
                    'issue' => 'High response time',
                    'recommendation' => 'Optimize database queries or add caching'
                ];
            }

            if ($service['error_rate'] > 5) {
                $recommendations[] = [
                    'type' => 'reliability',
                    'service' => $service['service'],
                    'issue' => 'High error rate',
                    'recommendation' => 'Investigate error logs and improve error handling'
                ];
            }
        }

        return $recommendations;
    }

    private function calculateGrowthRate($data, $metric): float
    {
        return $data['growth_rate'] ?? 0;
    }

    private function calculateErrorRate($startDate): float
    {
        $total = APILog::where('timestamp', '>=', $startDate)->count();
        $errors = APILog::where('timestamp', '>=', $startDate)->where('status_code', '>=', 400)->count();

        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }

    private function calculateServiceAvailability($startDate): float
    {
        $total = APILog::where('timestamp', '>=', $startDate)->count();
        $successful = APILog::where('timestamp', '>=', $startDate)->where('status_code', '<', 500)->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    private function getMLResponseTimeMetrics($startDate): array
    {
        $mlLogs = APILog::where('service_to', 'fitneaseml')
            ->where('timestamp', '>=', $startDate)
            ->get();

        if ($mlLogs->isEmpty()) {
            return ['no_data' => true];
        }

        return [
            'avg_response_time' => round($mlLogs->avg('response_time_ms'), 2),
            'max_response_time' => $mlLogs->max('response_time_ms'),
            'min_response_time' => $mlLogs->min('response_time_ms'),
            'request_count' => $mlLogs->count()
        ];
    }

    private function getErrorsByService($startTime): array
    {
        return APILog::where('timestamp', '>=', $startTime)
            ->where('status_code', '>=', 400)
            ->selectRaw('service_to, COUNT(*) as error_count')
            ->groupBy('service_to')
            ->get()
            ->pluck('error_count', 'service_to')
            ->toArray();
    }

    private function getErrorsByStatusCode($startTime): array
    {
        return APILog::where('timestamp', '>=', $startTime)
            ->where('status_code', '>=', 400)
            ->selectRaw('status_code, COUNT(*) as count')
            ->groupBy('status_code')
            ->get()
            ->pluck('count', 'status_code')
            ->toArray();
    }

    private function getCriticalErrors($startTime): array
    {
        return APILog::where('timestamp', '>=', $startTime)
            ->where('status_code', '>=', 500)
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get()
            ->map(function($log) {
                return [
                    'timestamp' => $log->timestamp,
                    'service' => $log->service_to,
                    'endpoint' => $log->endpoint,
                    'status_code' => $log->status_code
                ];
            })
            ->toArray();
    }
}
