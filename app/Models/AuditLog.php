<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'service_name',
        'timestamp'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'timestamp' => 'datetime'
    ];

    public static function logAction($action_type, $table_name, $record_id = null, $old_values = null, $new_values = null, $service_name = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'action_type' => $action_type,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'service_name' => $service_name ?? 'fitneaseops',
            'timestamp' => now()
        ]);
    }
}
