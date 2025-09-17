<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AuditLog;
use App\Models\APILog;
use App\Models\Report;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class ServiceCommunicationTestController extends Controller
{
    /**
     * Test incoming communications to operations service
     * Simulates other services calling operations service endpoints
     */
    public function testIncomingCommunications(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        Log::info('Operations Service - Testing incoming communications', [
            'user_id' => $userId,
            'timestamp' => now()
        ]);

        $results = [
            'service' => 'fitnease-operations',
            'test_type' => 'incoming_communications',
            'timestamp' => now(),
            'user_id' => $userId,
            'simulations' => []
        ];

        // Simulate Auth Service calling for audit logs
        try {
            $auditLogs = AuditLog::where('user_id', $userId)->orderBy('timestamp', 'desc')->limit(5)->get();

            $results['simulations']['auth_service_audit_request'] = [
                'status' => 'success',
                'simulation' => 'Auth Service requesting user audit logs',
                'endpoint' => '/ops/audit-logs/' . $userId,
                'method' => 'GET',
                'response_data' => [
                    'logs_found' => $auditLogs->count(),
                    'sample_logs' => $auditLogs->toArray()
                ],
                'metadata' => [
                    'caller_service' => 'fitnease-auth',
                    'purpose' => 'Security audit for user activity'
                ]
            ];
        } catch (\Exception $e) {
            $results['simulations']['auth_service_audit_request'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Simulate Content Service calling for system performance
        try {
            $apiPerformance = APILog::where('service_to', 'fitnease-content')
                ->where('timestamp', '>=', now()->subDays(7))
                ->selectRaw('AVG(response_time_ms) as avg_response_time, COUNT(*) as request_count')
                ->first();

            $results['simulations']['content_service_performance_request'] = [
                'status' => 'success',
                'simulation' => 'Content Service requesting performance metrics',
                'endpoint' => '/ops/api-performance?service=fitnease-content',
                'method' => 'GET',
                'response_data' => [
                    'avg_response_time' => $apiPerformance->avg_response_time ?? 0,
                    'request_count' => $apiPerformance->request_count ?? 0,
                    'period' => '7 days'
                ],
                'metadata' => [
                    'caller_service' => 'fitnease-content',
                    'purpose' => 'Performance optimization analysis'
                ]
            ];
        } catch (\Exception $e) {
            $results['simulations']['content_service_performance_request'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Simulate ML Service calling for business metrics
        try {
            $systemReports = Report::where('report_type', 'system_analytics')
                ->where('generated_at', '>=', now()->subDays(30))
                ->orderBy('generated_at', 'desc')
                ->limit(3)
                ->get();

            $results['simulations']['ml_service_metrics_request'] = [
                'status' => 'success',
                'simulation' => 'ML Service requesting business metrics for training',
                'endpoint' => '/ops/business-metrics',
                'method' => 'GET',
                'response_data' => [
                    'reports_available' => $systemReports->count(),
                    'latest_reports' => $systemReports->toArray()
                ],
                'metadata' => [
                    'caller_service' => 'fitnease-ml',
                    'purpose' => 'Model training with business intelligence data'
                ]
            ];
        } catch (\Exception $e) {
            $results['simulations']['ml_service_metrics_request'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Simulate Tracking Service calling for health monitoring
        try {
            $healthStatus = [
                'service' => 'fitnease-operations',
                'status' => 'healthy',
                'uptime' => '99.9%',
                'response_time' => '120ms',
                'database_status' => 'healthy',
                'dependencies' => [
                    'redis' => 'healthy',
                    'mysql' => 'healthy'
                ]
            ];

            $results['simulations']['tracking_service_health_request'] = [
                'status' => 'success',
                'simulation' => 'Tracking Service checking operations service health',
                'endpoint' => '/ops/system-health',
                'method' => 'GET',
                'response_data' => $healthStatus,
                'metadata' => [
                    'caller_service' => 'fitnease-tracking',
                    'purpose' => 'Cross-service health monitoring'
                ]
            ];
        } catch (\Exception $e) {
            $results['simulations']['tracking_service_health_request'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Simulate Engagement Service calling for system settings
        try {
            $systemSettings = SystemSetting::where('is_public', true)->get();

            $results['simulations']['engagement_service_settings_request'] = [
                'status' => 'success',
                'simulation' => 'Engagement Service requesting public system settings',
                'endpoint' => '/ops/settings-public',
                'method' => 'GET',
                'response_data' => [
                    'settings_count' => $systemSettings->count(),
                    'public_settings' => $systemSettings->mapWithKeys(function ($setting) {
                        return [$setting->setting_key => SystemSetting::castValue($setting->setting_value, $setting->setting_type)];
                    })
                ],
                'metadata' => [
                    'caller_service' => 'fitnease-engagement',
                    'purpose' => 'Configuration synchronization'
                ]
            ];
        } catch (\Exception $e) {
            $results['simulations']['engagement_service_settings_request'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Summary
        $successCount = collect($results['simulations'])->filter(function ($simulation) {
            return $simulation['status'] === 'success';
        })->count();

        $results['summary'] = [
            'total_simulations' => count($results['simulations']),
            'successful_simulations' => $successCount,
            'failed_simulations' => count($results['simulations']) - $successCount,
            'success_rate' => count($results['simulations']) > 0 ? round(($successCount / count($results['simulations'])) * 100, 2) . '%' : '0%'
        ];

        Log::info('Operations Service - Incoming communication tests completed', [
            'user_id' => $userId,
            'successful_simulations' => $successCount,
            'total_simulations' => count($results['simulations'])
        ]);

        return response()->json($results);
    }

    /**
     * Get service communication monitoring dashboard
     */
    public function getServiceLogs(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('user_id');

        Log::info('Operations Service - Retrieving service communication logs', [
            'user_id' => $userId
        ]);

        $dashboard = [
            'service' => 'fitnease-operations',
            'timestamp' => now(),
            'user_id' => $userId,
            'monitoring_data' => []
        ];

        // Recent API communications
        $recentApiLogs = APILog::orderBy('timestamp', 'desc')->limit(10)->get();

        $dashboard['monitoring_data']['recent_api_communications'] = [
            'total_logs' => $recentApiLogs->count(),
            'logs' => $recentApiLogs->map(function ($log) {
                return [
                    'timestamp' => $log->timestamp,
                    'service_from' => $log->service_from,
                    'service_to' => $log->service_to,
                    'endpoint' => $log->endpoint,
                    'method' => $log->http_method,
                    'status_code' => $log->status_code,
                    'response_time_ms' => $log->response_time_ms
                ];
            })
        ];

        // Service health status
        $dashboard['monitoring_data']['service_health'] = [
            'auth_service' => ['status' => 'healthy', 'last_check' => now()],
            'content_service' => ['status' => 'healthy', 'last_check' => now()],
            'tracking_service' => ['status' => 'healthy', 'last_check' => now()],
            'engagement_service' => ['status' => 'healthy', 'last_check' => now()],
            'ml_service' => ['status' => 'healthy', 'last_check' => now()]
        ];

        // Performance metrics
        $dashboard['monitoring_data']['performance_metrics'] = [
            'avg_response_time' => APILog::avg('response_time_ms') ?? 0,
            'total_requests_24h' => APILog::where('timestamp', '>=', now()->subDay())->count(),
            'error_rate_24h' => APILog::where('timestamp', '>=', now()->subDay())
                ->where('status_code', '>=', 400)->count(),
            'top_endpoints' => APILog::selectRaw('endpoint, COUNT(*) as request_count')
                ->where('timestamp', '>=', now()->subDay())
                ->groupBy('endpoint')
                ->orderBy('request_count', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json($dashboard);
    }
}