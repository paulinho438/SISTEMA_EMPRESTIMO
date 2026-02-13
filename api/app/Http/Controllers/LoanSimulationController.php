<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulateLoanRequest;
use App\Services\LoanSimulationService;
use Illuminate\Http\JsonResponse;

class LoanSimulationController extends Controller
{
    protected $simulationService;

    public function __construct(LoanSimulationService $simulationService)
    {
        $this->simulationService = $simulationService;
    }

    /**
     * Simula um empréstimo e retorna cronograma e totais
     *
     * @param SimulateLoanRequest $request
     * @return JsonResponse
     */
    public function simulate(SimulateLoanRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();
            
            // Converter taxa de percentual para decimal se necessário
            // Se vier como 20 (20%), converter para 0.20
            if (isset($inputs['taxa_juros_mensal'])) {
                $taxa = $inputs['taxa_juros_mensal'];
                // Se taxa > 1, assumir que está em percentual e converter
                if ($taxa > 1) {
                    $inputs['taxa_juros_mensal'] = $taxa / 100;
                }
            }

            $result = $this->simulationService->simulate($inputs);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao simular empréstimo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
