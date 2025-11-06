<?php

namespace Ovesio;

class Client
{
    private $apiKey;
    private $apiUrl;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    /**
     * Make a simple API call (GET request)
     */
    public function get($resource, $params = [])
    {
        $endpoint = ltrim($resource, '/');

        $ch = curl_init();

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $raw_response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw_response, true);

        /**
         * Invalid or error
         */
        if (empty($data)) {
            return [
                'success'     => false,
                'http_status' => $status,
                'error'       => substr($raw_response, 0, 500)
            ];
        }

        return $data;
    }

    /**
     * Make a POST API call with data
     */
    public function post($endpoint, $data = [])
    {
        $endpoint = ltrim($endpoint, '/');

        $ch = curl_init();

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $raw_response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $data = json_decode($raw_response, true);

        curl_close($ch);

        /**
         * Invalid or error
         */
        if (empty($data)) {
            return [
                'success'     => false,
                'http_status' => $status,
                'error'       => substr($raw_response, 0, 500)
            ];
        }

        return $data;
    }

    /**
     * Generic API call method (backwards compatibility)
     */
    public function callApi($resource, $method = 'GET', $params = [])
    {
        if (strtoupper($method) === 'GET') {
            return $this->get($resource, $params);
        } else {
            return $this->post('/' . ltrim($resource, '/'), $params);
        }
    }

    public function generateContent($data)
    {
        $endpoint = 'ai/generate-description';

        if (empty($data['data'])) {
            return;
        }

        // html_entity_decode content
        $data['data'] = $this->decode($data['data']);

        return $this->post($endpoint, $data);
    }

    public function generateSeo($data)
    {
        $endpoint = 'ai/generate-seo';

        if (empty($data['data'])) {
            return;
        }

        // html_entity_decode content
        $data['data'] = $this->decode($data['data']);

        return $this->post($endpoint, $data);
    }

    public function translate($data)
    {
        $endpoint = 'translate/request';

        if (empty($data['data'])) {
            return;
        }

        // html_entity_decode content
        $data['data'] = $this->decode($data['data']);

        return $this->post($endpoint, $data);
    }

    public function languages()
    {
        return $this->get('languages');
    }

    public function workflows()
    {
        return $this->get('workflows');
    }

    public function getGenerateContentStatus($activity_id)
    {
        return $this->get('ai/generate-description/status/' . $activity_id);
    }

    public function getGenerateSeoStatus($activity_id)
    {
        return $this->get('ai/generate-seo/status/' . $activity_id);
    }

    public function getTranslateStatus($activity_id)
    {
        return $this->get('translate/request/status/' . $activity_id);
    }

    /**
     * Decode HTML content
     */
    private function decode($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->decode($value);
            }
        } else {
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        }

        return $data;
    }
}
