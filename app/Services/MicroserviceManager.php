<?php

namespace App\Services;

use App\Services\AuthService;
use App\Services\TrackingService;
use App\Services\MLService;
use App\Services\EngagementService;

class MicroserviceManager
{
    protected $services = [];

    public function __construct()
    {
        $this->initializeServices();
    }

    private function initializeServices()
    {
        $this->services = [
            'auth' => new AuthService(),
            'tracking' => new TrackingService(),
            'ml' => new MLService(),
            'engagement' => new EngagementService()
        ];
    }

    public function getService($serviceName)
    {
        return $this->services[$serviceName] ?? null;
    }

    public function checkAllServicesHealth()
    {
        $healthReport = [];

        foreach ($this->services as $name => $service) {
            $healthReport[$name] = $service->healthCheck();
        }

        return $healthReport;
    }

    public function getAllServices()
    {
        return $this->services;
    }

    public function getServiceNames()
    {
        return array_keys($this->services);
    }
}