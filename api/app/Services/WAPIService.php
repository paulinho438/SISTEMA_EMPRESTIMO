<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\ControleBcodex;
use Illuminate\Support\Facades\Log;

class WAPIService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.w-api.app/v1';
    }

    public function enviarMensagem(string $token, string $instance_id, string $data)
    {
        $url = "{$this->baseUrl}/message/send-text?instanceId={$instance_id}";

        $accessToken = $token;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post($url, $data);

        if (!$response->successful()) {
            return false;
        }

        return true;
    }
}
