<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ContentService;
use App\Services\EngagementService;
use App\Services\AuthService;
use App\Services\TrackingService;
use App\Services\MLService;
use Illuminate\Support\Facades\Log;

class ServiceTestController extends Controller
{
    /**
     * Test basic service communications from operations service
     */
    public function testServices(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('user_id');
        $token = $request->bearerToken();

        Log::info('Operations Service - Testing service communications', [
            'user_id' => $userId,
            'timestamp' => now()
        ]);

        $results = [
            'service' => 'fitnease-operations',
            'timestamp' => now(),
            'user_id' => $userId,
            'tests' => []
        ];

        // Test Content Service
        try {
            $contentService = new ContentService();
            $exercise = $contentService->getExercise($token, 1);

            $results['tests']['content_service'] = [
                'status' => $exercise ? 'success' : 'failed',
                'test' => 'getExercise(1)',
                'response' => $exercise ? 'Exercise data retrieved' : 'No data returned',
                'data' => $exercise
            ];
        } catch (\Exception $e) {
            $results['tests']['content_service'] = [
                'status' => 'error',
                'test' => 'getExercise(1)',
                'error' => $e->getMessage()
            ];
        }

        // Test Engagement Service
        try {
            $engagementService = new EngagementService();
            $analytics = $engagementService->getEngagementAnalytics(now()->subDays(7)->toDateString());

            $results['tests']['engagement_service'] = [
                'status' => $analytics ? 'success' : 'failed',
                'test' => 'getEngagementAnalytics()',
                'response' => $analytics ? 'Analytics data retrieved' : 'No data returned',
                'data' => $analytics
            ];
        } catch (\Exception $e) {
            $results['tests']['engagement_service'] = [
                'status' => 'error',
                'test' => 'getEngagementAnalytics()',
                'error' => $e->getMessage()
            ];
        }

        // Test Auth Service
        try {
            $authService = new AuthService();
            $userAnalytics = $authService->getUserAnalytics(now()->subDays(30)->toDateString());

            $results['tests']['auth_service'] = [
                'status' => $userAnalytics ? 'success' : 'failed',
                'test' => 'getUserAnalytics()',
                'response' => $userAnalytics ? 'User analytics retrieved' : 'No data returned',
                'data' => $userAnalytics
            ];
        } catch (\Exception $e) {
            $results['tests']['auth_service'] = [
                'status' => 'error',
                'test' => 'getUserAnalytics()',
                'error' => $e->getMessage()
            ];
        }

        // Test Tracking Service
        try {
            $trackingService = new TrackingService();
            $workoutAnalytics = $trackingService->getWorkoutAnalytics(now()->subDays(30)->toDateString());

            $results['tests']['tracking_service'] = [
                'status' => $workoutAnalytics ? 'success' : 'failed',
                'test' => 'getWorkoutAnalytics()',
                'response' => $workoutAnalytics ? 'Workout analytics retrieved' : 'No data returned',
                'data' => $workoutAnalytics
            ];
        } catch (\Exception $e) {
            $results['tests']['tracking_service'] = [
                'status' => 'error',
                'test' => 'getWorkoutAnalytics()',
                'error' => $e->getMessage()
            ];
        }

        // Test ML Service
        try {
            $mlService = new MLService();
            $modelHealth = $mlService->getModelHealth();

            $results['tests']['ml_service'] = [
                'status' => $modelHealth ? 'success' : 'failed',
                'test' => 'getModelHealth()',
                'response' => $modelHealth ? 'ML model health retrieved' : 'No data returned',
                'data' => $modelHealth
            ];
        } catch (\Exception $e) {
            $results['tests']['ml_service'] = [
                'status' => 'error',
                'test' => 'getModelHealth()',
                'error' => $e->getMessage()
            ];
        }

        // Summary
        $successCount = collect($results['tests'])->filter(function ($test) {
            return $test['status'] === 'success';
        })->count();

        $results['summary'] = [
            'total_tests' => count($results['tests']),
            'successful_tests' => $successCount,
            'failed_tests' => count($results['tests']) - $successCount,
            'success_rate' => count($results['tests']) > 0 ? round(($successCount / count($results['tests'])) * 100, 2) . '%' : '0%'
        ];

        Log::info('Operations Service - Service communication tests completed', [
            'user_id' => $userId,
            'successful_tests' => $successCount,
            'total_tests' => count($results['tests'])
        ]);

        return response()->json($results);
    }

    /**
     * Test specific service communication
     */
    public function testSpecificService(Request $request, $serviceName): JsonResponse
    {
        $userId = $request->attributes->get('user_id');
        $token = $request->bearerToken();

        Log::info("Operations Service - Testing specific service: {$serviceName}", [
            'user_id' => $userId,
            'service' => $serviceName
        ]);

        $result = [
            'service' => 'fitnease-operations',
            'target_service' => $serviceName,
            'timestamp' => now(),
            'user_id' => $userId
        ];

        try {
            switch ($serviceName) {
                case 'content':
                    $contentService = new ContentService();
                    $data = $contentService->getExercise($token, 1);
                    $result['status'] = $data ? 'success' : 'failed';
                    $result['data'] = $data;
                    break;

                case 'engagement':
                    $engagementService = new EngagementService();
                    $data = $engagementService->getDailyActiveUsers();
                    $result['status'] = $data ? 'success' : 'failed';
                    $result['data'] = $data;
                    break;

                case 'auth':
                    $authService = new AuthService();
                    $data = $authService->getActiveUsers();
                    $result['status'] = $data ? 'success' : 'failed';
                    $result['data'] = $data;
                    break;

                case 'tracking':
                    $trackingService = new TrackingService();
                    $data = $trackingService->getCompletionRates();
                    $result['status'] = $data ? 'success' : 'failed';
                    $result['data'] = $data;
                    break;

                case 'ml':
                    $mlService = new MLService();
                    $data = $mlService->getModelAccuracy();
                    $result['status'] = $data ? 'success' : 'failed';
                    $result['data'] = $data;
                    break;

                default:
                    $result['status'] = 'error';
                    $result['error'] = 'Unknown service: ' . $serviceName;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['error'] = $e->getMessage();

            Log::error("Operations Service - Error testing service: {$serviceName}", [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }

        return response()->json($result);
    }
}