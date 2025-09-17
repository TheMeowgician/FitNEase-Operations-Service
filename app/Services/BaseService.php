<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\APILog;
use Exception;

abstract class BaseService
{
    protected $client;
    protected $baseUrl;
    protected $serviceName;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'FitnEase-Operations-Service',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    protected function makeRequest($method, $endpoint, $data = null, $log = true)
    {
        $startTime = microtime(true);
        $fullUrl = $this->baseUrl . $endpoint;

        try {
            $options = [];
            if ($data) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $fullUrl, $options);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = json_decode($response->getBody(), true);

            if ($log) {
                APILog::logRequest(
                    $endpoint,
                    $method,
                    $data,
                    $responseData,
                    $response->getStatusCode(),
                    $responseTime,
                    'fitneaseops',
                    $this->serviceName
                );
            }

            return [
                'success' => true,
                'data' => $responseData,
                'status_code' => $response->getStatusCode(),
                'response_time' => $responseTime
            ];

        } catch (Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $errorData = ['error' => $e->getMessage()];

            if ($log) {
                APILog::logRequest(
                    $endpoint,
                    $method,
                    $data,
                    $errorData,
                    $e->getCode() ?: 500,
                    $responseTime,
                    'fitneaseops',
                    $this->serviceName
                );
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->getCode() ?: 500,
                'response_time' => $responseTime
            ];
        }
    }

    public function healthCheck()
    {
        return $this->makeRequest('GET', '/health', null, false);
    }

    protected function get($endpoint, $params = null)
    {
        $url = $endpoint;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        return $this->makeRequest('GET', $url);
    }

    protected function post($endpoint, $data = null)
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    protected function put($endpoint, $data = null)
    {
        return $this->makeRequest('PUT', $endpoint, $data);
    }

    protected function delete($endpoint)
    {
        return $this->makeRequest('DELETE', $endpoint);
    }
}