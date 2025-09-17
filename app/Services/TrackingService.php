<?php

namespace App\Services;

class TrackingService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env('TRACKING_SERVICE_URL');
        $this->serviceName = 'fitneasetracking';
    }

    public function getWorkoutAnalytics($startDate)
    {
        return $this->get('/tracking/analytics', ['start_date' => $startDate]);
    }

    public function getUserWorkoutStats($userId, $days = 30)
    {
        return $this->get("/tracking/user/{$userId}/stats", ['days' => $days]);
    }

    public function getCompletionRates($period = '30d')
    {
        return $this->get('/tracking/completion-rates', ['period' => $period]);
    }

    public function getPopularExercises($limit = 10)
    {
        return $this->get('/tracking/popular-exercises', ['limit' => $limit]);
    }

    public function getWorkoutTrends($startDate, $endDate)
    {
        return $this->get('/tracking/trends', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function getUserProgressData($userId)
    {
        return $this->get("/tracking/user/{$userId}/progress");
    }
}