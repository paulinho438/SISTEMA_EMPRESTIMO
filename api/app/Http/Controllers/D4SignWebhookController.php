<?php

namespace App\Http\Controllers;

use App\Models\ContratoAssinaturaEvento;
use App\Models\SimulacaoEmprestimo;
use App\Services\ContratoEfetivacaoService;
use App\Services\D4SignService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $pdfInfo = null;
        $precisaAtualizarPdf = empty($contrato->pdf_final_path);

        if ($precisaAtualizarPdf) {
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

            $pdfInfo = [
                'path' => $finalPath,
                'sha256' => $hashFinal,
            ];
        }

        try {
            DB::beginTransaction();

            if ($pdfInfo) {
                $contrato->pdf_final_path = $pdfInfo['path'];
                $contrato->pdf_final_sha256 = $pdfInfo['sha256'];
            }

            $contrato->assinatura_status = 'signed';
            if (!$contrato->finalizado_at) {
                $contrato->finalizado_at = Carbon::now();
            }
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
                    'pdf_atualizado' => (bool) $pdfInfo,
                ],
            ]);

            $companyId = (int) ($contrato->company_id ?: 1);
            $resultadoEfetivacao = (new ContratoEfetivacaoService())
                ->efetivarContrato($contrato, $companyId, null);

            if (($resultadoEfetivacao['created_emprestimo'] ?? false)
                || ($resultadoEfetivacao['generated_parcelas'] ?? false)
                || ($resultadoEfetivacao['created_contaspagar'] ?? false)
                || ($resultadoEfetivacao['situacao_updated'] ?? false)) {
                ContratoAssinaturaEvento::create([
                    'contrato_id' => $contrato->id,
                    'ator_tipo' => 'sistema',
                    'ator_id' => null,
                    'evento_tipo' => 'AUTO_EFFECTIVATED_BY_WEBHOOK',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_json' => null,
                    'meta_json' => [
                        'emprestimo_id' => $resultadoEfetivacao['emprestimo']->id,
                        'created_emprestimo' => (bool) ($resultadoEfetivacao['created_emprestimo'] ?? false),
                        'generated_parcelas' => (bool) ($resultadoEfetivacao['generated_parcelas'] ?? false),
                        'created_contaspagar' => (bool) ($resultadoEfetivacao['created_contaspagar'] ?? false),
                        'situacao_updated' => (bool) ($resultadoEfetivacao['situacao_updated'] ?? false),
                    ],
                ]);
            }

            DB::commit();

            Log::info('D4Sign webhook: contrato assinado e efetivado com sucesso', [
                'contrato_id' => $contrato->id,
                'emprestimo_id' => $resultadoEfetivacao['emprestimo']->id,
            ]);

            return response()->json([
                'message' => 'Processado com sucesso',
                'emprestimo_id' => $resultadoEfetivacao['emprestimo']->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('D4Sign webhook: erro de validação na efetivação', [
                'contrato_id' => $contrato->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('D4Sign webhook: erro ao processar assinatura/efetivação', [
                'contrato_id' => $contrato->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Erro ao processar webhook'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
