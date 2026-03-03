<?php

namespace App\Http\Controllers;

use App\Models\ContratoAssinaturaEvento;
use App\Models\SimulacaoEmprestimo;
use App\Services\D4SignService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class D4SignWebhookController extends Controller
{
    /**
     * Recebe webhook da D4Sign (FORM-DATA).
     * type_post: "1" = documento finalizado (FINISHED)
     */
    public function receber(Request $request)
    {
        $uuid = $request->input('uuid');
        $typePost = $request->input('type_post');
        $message = $request->input('message');

        Log::info('D4Sign webhook recebido', [
            'uuid' => $uuid,
            'type_post' => $typePost,
            'message' => $message,
        ]);

        if (empty($uuid)) {
            return response()->json(['message' => 'uuid obrigatório'], Response::HTTP_BAD_REQUEST);
        }

        // type_post "1" = documento finalizado (FINISHED)
        if ($typePost !== '1') {
            return response()->json(['message' => 'Evento ignorado']);
        }

        $contrato = SimulacaoEmprestimo::where('d4sign_uuid_document', $uuid)->first();
        if (!$contrato) {
            Log::warning('D4Sign webhook: contrato não encontrado', ['uuid' => $uuid]);
            return response()->json(['message' => 'Contrato não encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($contrato->assinatura_status === 'signed') {
            Log::info('D4Sign webhook: contrato já assinado', ['contrato_id' => $contrato->id]);
            return response()->json(['message' => 'Já processado']);
        }

        $d4sign = new D4SignService();
        $download = $d4sign->downloadDocumento($uuid);
        if (!$download || empty($download['url'])) {
            Log::error('D4Sign webhook: falha ao obter URL de download', ['uuid' => $uuid]);
            return response()->json(['message' => 'Erro ao baixar documento'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $pdfResponse = Http::timeout(30)->get($download['url']);
            if (!$pdfResponse->successful()) {
                throw new \RuntimeException('Falha ao baixar PDF da URL');
            }
            $pdfContent = $pdfResponse->body();
        } catch (\Throwable $e) {
            Log::error('D4Sign webhook: erro ao baixar PDF', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao baixar documento'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $dir = "private/contratos/assinatura/{$contrato->id}/v" . max(1, (int) $contrato->assinatura_versao);
        $finalPath = "{$dir}/contrato_assinado.pdf";

        // Garantir diretório
        $absDir = storage_path('app/' . $dir);
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }
        file_put_contents(storage_path('app/' . $finalPath), $pdfContent);
        $hashFinal = hash_file('sha256', storage_path('app/' . $finalPath)) ?: null;

        $contrato->pdf_final_path = $finalPath;
        $contrato->pdf_final_sha256 = $hashFinal;
        $contrato->assinatura_status = 'signed';
        $contrato->finalizado_at = Carbon::now();
        $contrato->save();

        ContratoAssinaturaEvento::create([
            'contrato_id' => $contrato->id,
            'ator_tipo' => 'd4sign',
            'ator_id' => null,
            'evento_tipo' => 'D4SIGN_SIGNED',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_json' => null,
            'meta_json' => [
                'uuid' => $uuid,
                'type_post' => $typePost,
                'message' => $message,
            ],
        ]);

        Log::info('D4Sign webhook: contrato assinado com sucesso', ['contrato_id' => $contrato->id]);

        return response()->json(['message' => 'Processado com sucesso']);
    }
}
