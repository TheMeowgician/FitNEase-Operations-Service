<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\APILogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\AnalyticsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('ops')->group(function () {

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
