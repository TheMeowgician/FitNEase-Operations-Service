<?php

namespace App\Services;

class MLService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env('ML_SERVICE_URL');
        $this->serviceName = 'fitneaseml';
    }

    public function getModelHealth()
    {
        return $this->get('/api/v1/model-health');
    }

    public function getEffectivenessMetrics($startDate)
    {
        return $this->get('/api/v1/effectiveness-metrics', ['start_date' => $startDate]);
    }

    public function getRecommendationStats($period = '24h')
    {
        return $this->get('/api/v1/recommendation-stats', ['period' => $period]);
    }

    public function getModelAccuracy()
    {
        return $this->get('/api/v1/model-accuracy');
    }

    public function getPersonalizationScore($userId)
    {
        return $this->get("/api/v1/personalization-score/{$userId}");
    }

    public function getAlgorithmPerformance()
    {
        return $this->get('/api/v1/algorithm-performance');
    }

    public function getModelTrainingHistory()
    {
        return $this->get('/api/v1/training-history');
    }

    public function getRecommendationAcceptanceRate($days = 30)
    {
        return $this->get('/api/v1/acceptance-rate', ['days' => $days]);
    }
}