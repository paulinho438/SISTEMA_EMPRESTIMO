<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BcodexService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.bcodex.io';
    }

    protected function login()
    {
        if (Cache::has('bcodex_access_token')) {
            return Cache::get('bcodex_access_token');
        }

        $response = Http::asForm()->post("{$this->baseUrl}/bcdx-sso/login", [
            'username' => env('BCODEX_USERNAME'),
            'password' => env('BCODEX_PASSWORD'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'];
            Cache::put('bcodex_access_token', $accessToken, $expiresIn - 60);

            return $accessToken;
        }

        throw new \Exception('Falha no login: ' . $response->body());
    }

    public function criarCobranca(float $valor , string $document)
    {

         // Dados da cobrança
         $data = [
            "calendario" => [
                "expiracao" => 86400000
            ],
            "valor" => [
                "original" => number_format($valor, 2, '.', ''),
                "modalidadeAlteracao" => 1
            ],
            "chave" => $document,
            "solicitacaoPagador" => "RJ EMPRESTIMOS",
            "infoAdicionais" => [
                [
                    "nome" => "RJ",
                    "valor" => "RJ EMPRESTIMOS"
                ]
            ]
        ];

        $txId = bin2hex(random_bytes(16));
        $url = "{$this->baseUrl}/cob/{$txId}";
        $accessToken = $this->login();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->put($url, $data);

        return $response;
    }

    public function consultarChavePix(float $valor, string $pix, string $accountId)
    {


        // Dados da consulta
        $data = [
            "amount" => $valor,
            "pixKey" => $pix,
            "description" => "Informação/Descrição",
            "clientReferenceId" => ""
        ];

        $url = "{$this->baseUrl}/bcodex-pix-dex/api/v1/account/{$accountId}/initiate-pix";

        $accessToken = $this->login();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->put($url, $data);

        return $response;
    }

    public function realizarPagamentoPix(float $valor, string $accountId, string $paymentId)
    {
         // Dados da consulta
        $data = [
            "amount" => $valor,
            "paymentId" => $paymentId,
        ];

        $url = "{$this->baseUrl}/bcodex-pix-dex/api/v1/account/$accountId/confirm-pix";

        $accessToken = $this->login();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->put($url, $data);

        return $response;
    }
}
