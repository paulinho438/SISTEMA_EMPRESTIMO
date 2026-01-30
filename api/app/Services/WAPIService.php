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

    public function enviarMensagem(string $token, string $instance_id, array $data)
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

    public function enviarMensagemAudio(string $token, string $instance_id, array $data)
    {
        $url = "{$this->baseUrl}/message/send-audio?instanceId={$instance_id}";

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

    public function enviarMensagemVideo(string $token, string $instance_id, array $data)
    {
        $url = "{$this->baseUrl}/message/send-video?instanceId={$instance_id}";

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

    public function enviarMensagemImagem(string $token, string $instance_id, array $data)
    {
        $url = "{$this->baseUrl}/message/send-image?instanceId={$instance_id}";

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

    /**
     * Lista a fila de mensagens da instância W-API.
     * GET https://api.w-api.app/v1/quere/quere?instanceId=&perPage=10&page=1
     */
    public function listarFila(string $token, string $instanceId, int $perPage = 10, int $page = 1)
    {
        $url = "{$this->baseUrl}/quere/quere?instanceId=" . urlencode($instanceId) . "&perPage={$perPage}&page={$page}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->get($url);

        return $response;
    }

    /**
     * Remove um item da fila W-API (ou limpa a fila, conforme documentação).
     * DELETE https://api.w-api.app/v1/quere/delete-quere?instanceId=
     * Se a API exigir id do item: delete-quere?instanceId=xxx&id=yyy
     */
    public function deletarFila(string $token, string $instanceId, ?string $id = null)
    {
        $url = "{$this->baseUrl}/quere/delete-quere?instanceId=" . urlencode($instanceId);
        if ($id !== null && $id !== '') {
            $url .= '&id=' . urlencode($id);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->delete($url);

        return $response;
    }
}
