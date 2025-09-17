<?php

namespace App\Services;

class EngagementService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = env('ENGAGEMENT_SERVICE_URL');
        $this->serviceName = 'fitneaseengagement';
    }

    public function getEngagementAnalytics($startDate)
    {
        return $this->get('/engagement/analytics', ['start_date' => $startDate]);
    }

    public function getDailyActiveUsers($date = null)
    {
        $params = $date ? ['date' => $date] : [];
        return $this->get('/engagement/daily-active-users', $params);
    }

    public function getSessionMetrics($period = '7d')
    {
        return $this->get('/engagement/session-metrics', ['period' => $period]);
    }

    public function getAchievementStats($userId = null)
    {
        $endpoint = $userId ? "/engagement/achievements/{$userId}" : '/engagement/achievements';
        return $this->get($endpoint);
    }

    public function getSocialInteractions($period = '24h')
    {
        return $this->get('/engagement/social-interactions', ['period' => $period]);
    }

    public function getRetentionRates($cohort = 'monthly')
    {
        return $this->get('/engagement/retention-rates', ['cohort' => $cohort]);
    }

    public function getGamificationMetrics()
    {
        return $this->get('/engagement/gamification-metrics');
    }
}