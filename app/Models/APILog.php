<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class APILog extends Model
{
    protected $table = 'api_logs';
    protected $primaryKey = 'api_log_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'endpoint',
        'http_method',
        'request_data',
        'response_data',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'service_from',
        'service_to',
        'timestamp'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'timestamp' => 'datetime'
    ];

    public static function logRequest($endpoint, $method, $request_data = null, $response_data = null, $status_code = null, $response_time = null, $service_from = null, $service_to = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'endpoint' => $endpoint,
            'http_method' => $method,
            'request_data' => $request_data,
            'response_data' => $response_data,
            'status_code' => $status_code,
            'response_time_ms' => $response_time,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'service_from' => $service_from,
            'service_to' => $service_to,
            'timestamp' => now()
        ]);
    }

    public static function getPerformanceMetrics($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return self::where('timestamp', '>=', $startDate)
            ->selectRaw('
                service_to,
                AVG(response_time_ms) as avg_response_time,
                COUNT(*) as request_count,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count,
                SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as success_count
            ')
            ->groupBy('service_to')
            ->get();
    }
}
