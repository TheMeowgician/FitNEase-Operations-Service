<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'report_name',
        'report_type',
        'generated_by',
        'report_parameters',
        'report_data',
        'file_path',
        'file_format',
        'generated_at',
        'expires_at'
    ];

    protected $casts = [
        'report_parameters' => 'array',
        'report_data' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public static function createReport($name, $type, $data, $parameters = null, $file_format = 'json', $expires_days = 90)
    {
        return self::create([
            'report_name' => $name,
            'report_type' => $type,
            'generated_by' => auth()->id(),
            'report_parameters' => $parameters,
            'report_data' => $data,
            'file_format' => $file_format,
            'generated_at' => now(),
            'expires_at' => now()->addDays($expires_days)
        ]);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }
}
