<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentService
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('CONTENT_SERVICE_URL');
    }

    /**
     * Get exercise details from content service
     */
    public function getExercise($token, $exerciseId)
    {
        try {
            if (!$this->baseUrl) {
                Log::error('CONTENT_SERVICE_URL not configured');
                return null;
            }

            Log::info('Operations Service requesting exercise from Content Service', [
                'exercise_id' => $exerciseId,
                'caller_service' => 'fitnease-operations'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/api/content/exercises/' . $exerciseId);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Exercise data retrieved successfully', [
                    'exercise_id' => $exerciseId,
                    'service' => 'fitnease-operations'
                ]);

                return $data;
            }

            Log::warning('Failed to get exercise from Content Service', [
                'exercise_id' => $exerciseId,
                'status_code' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Content Service communication error', [
                'exercise_id' => $exerciseId,
                'error' => $e->getMessage(),
                'caller_service' => 'fitnease-operations'
            ]);

            return null;
        }
    }

    /**
     * Get workout details from content service
     */
    public function getWorkout($token, $workoutId)
    {
        try {
            if (!$this->baseUrl) {
                Log::error('CONTENT_SERVICE_URL not configured');
                return null;
            }

            Log::info('Operations Service requesting workout from Content Service', [
                'workout_id' => $workoutId,
                'caller_service' => 'fitnease-operations'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/api/content/workouts/' . $workoutId);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Failed to get workout from Content Service', [
                'workout_id' => $workoutId,
                'status_code' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Content Service communication error', [
                'workout_id' => $workoutId,
                'error' => $e->getMessage(),
                'caller_service' => 'fitnease-operations'
            ]);

            return null;
        }
    }

    /**
     * Notify content service about operational events
     */
    public function notifyOperationalEvent($token, $eventType, $data)
    {
        try {
            if (!$this->baseUrl) {
                Log::error('CONTENT_SERVICE_URL not configured');
                return false;
            }

            Log::info('Operations Service notifying Content Service', [
                'event_type' => $eventType,
                'caller_service' => 'fitnease-operations'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/content/operational-events', [
                'event_type' => $eventType,
                'data' => $data,
                'source_service' => 'fitnease-operations',
                'timestamp' => now()
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Content Service notification error', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'caller_service' => 'fitnease-operations'
            ]);

            return false;
        }
    }
}