<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use Exception;
use Carbon\Carbon;
use App\Models\APILog;

class HealthCheckController extends Controller
{
    private $services = [
        'fitneaseauth' => 'AUTH_SERVICE_URL',
        'fitneasecontent' => 'CONTENT_SERVICE_URL',
        'fitneasetracking' => 'TRACKING_SERVICE_URL',
        'fitneaseplanning' => 'PLANNING_SERVICE_URL',
        'fitneasesocial' => 'SOCIAL_SERVICE_URL',
        'fitneaseml' => 'ML_SERVICE_URL',
        'fitneaseengagement' => 'ENGAGEMENT_SERVICE_URL',
        'fitneasecomms' => 'COMMS_SERVICE_URL',
        'fitneasemedia' => 'MEDIA_SERVICE_URL'
    ];

    public function checkAllServicesHealth(): JsonResponse
    {
        // Log the health check request for monitoring
        Log::info('Operations Service - Health check requested', [
            'requested_at' => now(),
            'service' => 'fitnease-operations'
        ]);

        $healthReport = [];

        foreach ($this->services as $serviceName => $envKey) {
            $serviceUrl = env($envKey);
            if ($serviceUrl) {
                $healthReport[$serviceName] = $this->checkServiceHealth($serviceName, $serviceUrl);
            } else {
                $healthReport[$serviceName] = [
                    'status' => 'unconfigured',
                    'error' => 'Service URL not configured',
                    'last_checked' => now()
                ];
            }
        }

        $overallStatus = $this->calculateOverallHealth($healthReport);
        $unhealthyServices = $this->getUnhealthyServices($healthReport);

        // Log health check results
        Log::info('Operations Service - Health check completed', [
            'overall_status' => $overallStatus,
            'total_services' => count($healthReport),
            'unhealthy_count' => count($unhealthyServices),
            'unhealthy_services' => $unhealthyServices
        ]);

        return response()->json([
            'success' => true,
            'overall_status' => $overallStatus,
            'services' => $healthReport,
            'timestamp' => now(),
            'unhealthy_services' => $unhealthyServices
        ]);
    }

    public function checkServiceStatus($serviceName): JsonResponse
    {
        if (!isset($this->services[$serviceName])) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        $serviceUrl = env($this->services[$serviceName]);
        if (!$serviceUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Service URL not configured'
            ], 500);
        }

        $healthStatus = $this->checkServiceHealth($serviceName, $serviceUrl);

        return response()->json([
            'success' => true,
            'service' => $serviceName,
            'data' => $healthStatus
        ]);
    }

    public function triggerHealthCheck(): JsonResponse
    {
        $results = $this->checkAllServicesHealth()->getData();

        APILog::logRequest(
            '/ops/health-check',
            'POST',
            [],
            $results,
            200,
            null,
            'fitneaseops',
            'all_services'
        );

        return response()->json($results);
    }

    public function getErrorSummary(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $startTime = Carbon::now()->subHours($hours);

        $errorSummary = [
            'period_hours' => $hours,
            'summary' => $this->getErrorSummaryData($startTime),
            'by_service' => $this->getErrorsByService($startTime),
            'by_status_code' => $this->getErrorsByStatusCode($startTime),
            'critical_errors' => $this->getCriticalErrors($startTime)
        ];

        return response()->json([
            'success' => true,
            'data' => $errorSummary
        ]);
    }

    public function checkMLServiceHealth(): JsonResponse
    {
        try {
            $client = new Client();
            $mlServiceUrl = env('ML_SERVICE_URL');

            if (!$mlServiceUrl) {
                throw new Exception('ML service URL not configured');
            }

            $response = $client->get($mlServiceUrl . '/api/v1/model-health', [
                'timeout' => 10,
                'headers' => ['User-Agent' => 'FitnEase-Ops-Monitor']
            ]);

            $healthData = json_decode($response->getBody(), true);

            return response()->json([
                'success' => true,
                'service' => 'fitneaseml',
                'status' => 'healthy',
                'response_time' => $response->getHeader('X-Response-Time')[0] ?? null,
                'model_metrics' => [
                    'content_based_accuracy' => $healthData['content_based_accuracy'] ?? null,
                    'collaborative_accuracy' => $healthData['collaborative_accuracy'] ?? null,
                    'random_forest_accuracy' => $healthData['random_forest_accuracy'] ?? null,
                    'last_training' => $healthData['last_training'] ?? null,
                    'recommendation_count_24h' => $healthData['recommendation_count_24h'] ?? null
                ],
                'system_metrics' => [
                    'cpu_usage' => $healthData['cpu_usage'] ?? null,
                    'memory_usage' => $healthData['memory_usage'] ?? null,
                    'model_load_time' => $healthData['model_load_time'] ?? null
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'service' => 'fitneaseml',
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    private function checkServiceHealth($serviceName, $serviceUrl): array
    {
        try {
            $client = new Client();
            $startTime = microtime(true);

            $response = $client->get($serviceUrl . '/health', [
                'timeout' => 5,
                'headers' => ['User-Agent' => 'FitnEase-Ops-Monitor']
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = json_decode($response->getBody(), true);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'http_status' => $response->getStatusCode(),
                'service_data' => $responseData,
                'last_checked' => now()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'last_checked' => now()
            ];
        }
    }

    private function calculateOverallHealth($healthReport): string
    {
        $unhealthyCount = collect($healthReport)->filter(function ($health) {
            return in_array($health['status'], ['unhealthy', 'unconfigured']);
        })->count();

        if ($unhealthyCount === 0) {
            return 'healthy';
        } elseif ($unhealthyCount <= 2) {
            return 'degraded';
        } else {
            return 'unhealthy';
        }
    }

    private function getUnhealthyServices($healthReport): array
    {
        return collect($healthReport)->filter(function ($health) {
            return in_array($health['status'], ['unhealthy', 'unconfigured']);
        })->keys()->toArray();
    }

    private function getErrorSummaryData($startTime): array
    {
        $logs = APILog::where('timestamp', '>=', $startTime);
        $total = $logs->count();
        $errors = $logs->where('status_code', '>=', 400)->count();

        return [
            'total_requests' => $total,
            'error_count' => $errors,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
            'avg_response_time' => round($logs->avg('response_time_ms'), 2)
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
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'timestamp' => $log->timestamp,
                    'service' => $log->service_to,
                    'endpoint' => $log->endpoint,
                    'status_code' => $log->status_code,
                    'error_details' => is_array($log->response_data) ? ($log->response_data['error'] ?? 'Unknown error') : 'Unknown error'
                ];
            })
            ->toArray();
    }
}
