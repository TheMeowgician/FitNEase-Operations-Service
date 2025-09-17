<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\APILogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ServiceTestController;
use App\Http\Controllers\ServiceCommunicationTestController;
use App\Http\Controllers\ServiceIntegrationDemoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth.api');

// Health check endpoint for Docker
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'fitnease-operations',
        'timestamp' => now()
    ]);
});

// Service Communication Testing Routes (Protected)
Route::middleware('auth.api')->group(function () {
    Route::get('/test-services', [ServiceTestController::class, 'testServices']);
    Route::get('/test-service/{serviceName}', [ServiceTestController::class, 'testSpecificService']);
    Route::get('/service-test/communications', [ServiceCommunicationTestController::class, 'testIncomingCommunications']);
    Route::get('/service-test/logs', [ServiceCommunicationTestController::class, 'getServiceLogs']);
});

// Service Integration Demo Routes (Public - No Auth Required)
Route::prefix('demo')->group(function () {
    Route::get('/integrations', [ServiceIntegrationDemoController::class, 'integrationsOverview']);
    Route::get('/auth-service', [ServiceIntegrationDemoController::class, 'authServiceDemo']);
    Route::get('/content-service', [ServiceIntegrationDemoController::class, 'contentServiceDemo']);
    Route::get('/ml-service', [ServiceIntegrationDemoController::class, 'mlServiceDemo']);
    Route::get('/business-intelligence', [ServiceIntegrationDemoController::class, 'businessIntelligenceDemo']);
});

Route::prefix('ops')->middleware('auth.api')->group(function () {

    // Audit & Logging Routes
    Route::post('/audit-log', [AuditLogController::class, 'store']);
    Route::get('/audit-logs/{userId}', [AuditLogController::class, 'getUserLogs']);
    Route::get('/audit-logs/service/{serviceName}', [AuditLogController::class, 'getServiceLogs']);
    Route::get('/audit-logs/action/{actionType}', [AuditLogController::class, 'getLogsByAction']);

    Route::post('/api-log', [APILogController::class, 'store']);
    Route::get('/api-performance', [APILogController::class, 'getPerformanceMetrics']);
    Route::get('/api-errors', [APILogController::class, 'getErrorLogs']);
    Route::get('/api-stats/{serviceName}', [APILogController::class, 'getServiceStats']);

    // System Monitoring Routes
    Route::get('/system-health', [HealthCheckController::class, 'checkAllServicesHealth']);
    Route::get('/service-status/{serviceName}', [HealthCheckController::class, 'checkServiceStatus']);
    Route::post('/health-check', [HealthCheckController::class, 'triggerHealthCheck']);
    Route::get('/error-summary', [HealthCheckController::class, 'getErrorSummary']);
    Route::get('/ml-health', [HealthCheckController::class, 'checkMLServiceHealth']);

    // Reporting & Analytics Routes
    Route::get('/reports/{type}', [ReportController::class, 'getReportsByType']);
    Route::post('/generate-report', [ReportController::class, 'generateReport']);
    Route::get('/report/{reportId}', [ReportController::class, 'getReport']);
    Route::delete('/reports/cleanup', [ReportController::class, 'deleteExpiredReports']);

    Route::get('/ml-performance', [AnalyticsController::class, 'getMLPerformance']);
    Route::get('/business-metrics', [AnalyticsController::class, 'getBusinessMetrics']);
    Route::get('/system-performance-report', [AnalyticsController::class, 'generateSystemPerformanceReport']);
    Route::get('/service-metrics/{serviceName}', [AnalyticsController::class, 'getServiceSpecificMetrics']);

    // Configuration Management Routes
    Route::get('/settings', [SystemSettingController::class, 'index']);
    Route::put('/settings/{key}', [SystemSettingController::class, 'updateSetting']);
    Route::get('/settings/{key}', [SystemSettingController::class, 'getSetting']);
    Route::get('/settings/category/{category}', [SystemSettingController::class, 'getSettingsByCategory']);
    Route::get('/settings-public', [SystemSettingController::class, 'getPublicSettings']);
    Route::post('/settings/backup', [SystemSettingController::class, 'backupConfiguration']);

});
