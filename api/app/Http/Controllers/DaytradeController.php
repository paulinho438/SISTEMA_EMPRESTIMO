<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DaytradeMeta;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class DaytradeController extends Controller
{
    /**
     * Obter dados do daytrade da empresa.
     */
    public function get(Request $request)
    {
        $companyId = $request->header('company-id');
        if (!$companyId) {
            return response()->json(['error' => 'Company ID não informado'], Response::HTTP_BAD_REQUEST);
        }

        $daytrade = DaytradeMeta::where('company_id', $companyId)->first();

        if (!$daytrade) {
            return response()->json(null);
        }

        return response()->json([
            'capitalInicial' => (float) $daytrade->capital_inicial,
            'metaDiariaPct' => (float) $daytrade->meta_diaria_pct,
            'dias' => (int) $daytrade->dias,
            'modoLancamento' => $daytrade->modo_lancamento,
            'regraDia' => $daytrade->regra_dia,
            'diaAtual' => (int) $daytrade->dia_atual,
            'lancamentos' => $daytrade->lancamentos ?? [],
        ]);
    }

    /**
     * Salvar dados do daytrade.
     */
    public function save(Request $request)
    {
        $companyId = $request->header('company-id');
        if (!$companyId) {
            return response()->json(['error' => 'Company ID não informado'], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'capitalInicial' => 'required|numeric|min:0',
            'metaDiariaPct' => 'required|numeric',
            'dias' => 'required|integer|min:1|max:3650',
            'modoLancamento' => 'required|in:brl,pct',
            'regraDia' => 'required|in:sobre_saldo,sobre_inicial',
            'diaAtual' => 'required|integer|min:0',
            'lancamentos' => 'nullable|array',
            'lancamentos.*' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'error' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $daytrade = DaytradeMeta::updateOrCreate(
            ['company_id' => $companyId],
            [
                'capital_inicial' => $request->capitalInicial,
                'meta_diaria_pct' => $request->metaDiariaPct,
                'dias' => $request->dias,
                'modo_lancamento' => $request->modoLancamento,
                'regra_dia' => $request->regraDia,
                'dia_atual' => $request->diaAtual,
                'lancamentos' => $request->lancamentos ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados salvos com sucesso!',
        ]);
    }
}
