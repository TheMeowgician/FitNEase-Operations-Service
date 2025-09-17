<?php

namespace App\Services;

class AuthService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env('AUTH_SERVICE_URL');
        $this->serviceName = 'fitneaseauth';
    }

    public function getUserAnalytics($startDate)
    {
        return $this->get('/auth/user-analytics', ['start_date' => $startDate]);
    }

    public function validateToken($token)
    {
        return $this->post('/auth/validate-token', ['token' => $token]);
    }

    public function getUserActivity($userId, $days = 30)
    {
        return $this->get("/auth/user/{$userId}/activity", ['days' => $days]);
    }

    public function getActiveUsers($period = '24h')
    {
        return $this->get('/auth/active-users', ['period' => $period]);
    }

    public function getRegistrationMetrics($startDate, $endDate)
    {
        return $this->get('/auth/registration-metrics', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}