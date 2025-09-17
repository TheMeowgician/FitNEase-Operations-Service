<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    protected $primaryKey = 'setting_id';
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public',
        'category',
        'requires_restart',
        'updated_by',
        'updated_at'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'requires_restart' => 'boolean',
        'updated_at' => 'datetime'
    ];

    public static function setSetting($key, $value, $description = null, $category = null, $requires_restart = false)
    {
        $setting_type = self::detectSettingType($value);

        return self::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_type' => $setting_type,
                'description' => $description,
                'category' => $category,
                'requires_restart' => $requires_restart,
                'updated_by' => auth()->id(),
                'updated_at' => now()
            ]
        );
    }

    public static function getSetting($key, $default = null)
    {
        $setting = self::where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->setting_value, $setting->setting_type);
    }

    public static function getSettingsByCategory($category)
    {
        return self::where('category', $category)->get()->mapWithKeys(function ($setting) {
            return [$setting->setting_key => self::castValue($setting->setting_value, $setting->setting_type)];
        });
    }

    public static function getPublicSettings()
    {
        return self::where('is_public', true)->get()->mapWithKeys(function ($setting) {
            return [$setting->setting_key => self::castValue($setting->setting_value, $setting->setting_type)];
        });
    }

    private static function detectSettingType($value)
    {
        if (is_bool($value)) return 'boolean';
        if (is_numeric($value)) return 'integer';
        if (is_array($value) || is_object($value)) return 'json';
        return 'string';
    }

    public static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
