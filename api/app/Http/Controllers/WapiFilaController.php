<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Services\WAPIService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fila W-API: listar e deletar itens da fila.
 * Acesso restrito ao usuário MASTERGERAL (login === 'MASTERGERAL').
 */
class WapiFilaController extends Controller
{
    public function __construct(protected WAPIService $wapiService)
    {
    }

    /**
     * Lista empresas que possuem token_api_wtz e instance_id (para dropdown na tela da fila).
     * Apenas MASTERGERAL.
     */
    public function companies(Request $request)
    {
        if ($request->user()->login !== 'MASTERGERAL') {
            return response()->json(['message' => 'Acesso negado. Apenas MASTERGERAL.'], Response::HTTP_FORBIDDEN);
        }

        $companies = Company::query()
            ->whereNotNull('token_api_wtz')
            ->whereNotNull('instance_id')
            ->where('token_api_wtz', '!=', '')
            ->where('instance_id', '!=', '')
            ->select('id', 'company', 'instance_id')
            ->orderBy('company')
            ->get();

        return response()->json(['data' => $companies]);
    }

    /**
     * Lista a fila da W-API para uma instância (empresa).
     * GET /wapi/fila?company_id=1&per_page=10&page=1
     * Apenas MASTERGERAL.
     */
    public function index(Request $request)
    {
        if ($request->user()->login !== 'MASTERGERAL') {
            return response()->json(['message' => 'Acesso negado. Apenas MASTERGERAL.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $company = Company::find($request->company_id);
        if (empty($company->token_api_wtz) || empty($company->instance_id)) {
            return response()->json([
                'message' => 'Empresa sem token_api_wtz ou instance_id configurado.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $perPage = (int) ($request->per_page ?? 10);
        $page = (int) ($request->page ?? 1);

        $response = $this->wapiService->listarFila(
            $company->token_api_wtz,
            $company->instance_id,
            $perPage,
            $page
        );

        $body = $response->json();
        $status = $response->status();

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => $body['message'] ?? $body['error'] ?? 'Erro ao listar fila W-API',
                'response' => $body,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'data' => $body['data'] ?? $body,
            'response' => $body,
        ]);
    }

    /**
     * Remove um item da fila W-API.
     * DELETE /wapi/fila/{id}?company_id=1
     * Apenas MASTERGERAL.
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->login !== 'MASTERGERAL') {
            return response()->json(['message' => 'Acesso negado. Apenas MASTERGERAL.'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::find($request->company_id);
        if (empty($company->token_api_wtz) || empty($company->instance_id)) {
            return response()->json([
                'message' => 'Empresa sem token_api_wtz ou instance_id configurado.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $response = $this->wapiService->deletarFila(
            $company->token_api_wtz,
            $company->instance_id,
            (string) $id
        );

        $body = $response->json();
        $status = $response->status();

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => $body['message'] ?? $body['error'] ?? 'Erro ao deletar item da fila.',
                'response' => $body,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removido da fila.',
            'response' => $body,
        ]);
    }
}
