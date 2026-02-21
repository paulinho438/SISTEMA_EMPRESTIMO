<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContratoAssinaturaDesafio;
use App\Models\ContratoAssinaturaEvidencia;
use App\Models\ContratoAssinaturaEvento;
use App\Models\ContratoAssinaturaOtp;
use App\Models\SimulacaoEmprestimo;
use App\Services\ContratoAssinaturaPdfService;
use App\Services\WAPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssinaturaContratoController extends Controller
{
    private const STATUS_PENDING_ACCEPTANCE = 'pending_acceptance';
    private const STATUS_EVIDENCE_PENDING = 'evidence_pending';
    private const STATUS_EVIDENCE_SUBMITTED = 'evidence_submitted';
    private const STATUS_OTP_PENDING = 'otp_pending';
    private const STATUS_SIGNED_PENDING_REVIEW = 'signed_pending_review';
    private const STATUS_SIGNED = 'signed';
    private const STATUS_REJECTED = 'rejected';
    private const STATUS_RESUBMIT_REQUIRED = 'resubmit_required';

    private function registrarEvento(SimulacaoEmprestimo $contrato, string $atorTipo, ?int $atorId, string $eventoTipo, Request $request, array $meta = [], ?array $device = null): void
    {
        ContratoAssinaturaEvento::create([
            'contrato_id' => $contrato->id,
            'ator_tipo' => $atorTipo,
            'ator_id' => $atorId,
            'evento_tipo' => $eventoTipo,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_json' => $device,
            'meta_json' => $meta,
        ]);
    }

    private function assertContratoDaCompany(SimulacaoEmprestimo $contrato, Request $request): void
    {
        $companyId = (int) $request->header('company-id');
        if ($companyId <= 0 || (int) $contrato->company_id !== $companyId) {
            abort(Response::HTTP_FORBIDDEN, 'Contrato não pertence à empresa.');
        }
    }

    private function assertContratoDoCliente(SimulacaoEmprestimo $contrato, Client $cliente): void
    {
        if ((int) $contrato->client_id !== (int) $cliente->id) {
            abort(Response::HTTP_FORBIDDEN, 'Contrato não pertence ao cliente.');
        }
    }

    private function telefoneClienteE164(Client $cliente): ?string
    {
        $telefone = preg_replace('/\D/', '', (string) ($cliente->telefone_celular_1 ?? ''));
        if (!$telefone) return null;
        return '55' . $telefone;
    }

    private function enviarWhatsApp(Client $cliente, string $mensagem): bool
    {
        $telefone = $this->telefoneClienteE164($cliente);
        if (!$telefone) return false;

        // 1) W-API (token_api_wtz + instance_id) - já usado no projeto
        if (!empty($cliente->company->token_api_wtz) && !empty($cliente->company->instance_id)) {
            $wapi = new WAPIService();
            return (bool) $wapi->enviarMensagem(
                $cliente->company->token_api_wtz,
                $cliente->company->instance_id,
                ['phone' => $telefone, 'message' => $mensagem]
            );
        }

        // 2) Integração antiga baseada em URL configurada na empresa (company->whatsapp)
        $base = (string) ($cliente->company->whatsapp ?? '');
        if ($base === '') return false;

        try {
            $resp = Http::get(rtrim($base, '/') . '/logar');
            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                $r = $resp->json();
                if (!empty($r['loggedIn'])) {
                    $sendUrl = rtrim($base, '/') . '/enviar-mensagem';
                    $data = ['numero' => $telefone, 'mensagem' => $mensagem];
                    Http::asJson()->post($sendUrl, $data);
                    return true;
                }
            }
        } catch (\Throwable $th) {
            return false;
        }

        return false;
    }

    private function gerarSenhaInicial(): string
    {
        // senha fixa (não temporária) conforme pedido, porém aleatória
        return (string) random_int(10000000, 99999999);
    }

    private function contratoStorageDir(SimulacaoEmprestimo $contrato): string
    {
        $versao = (int) ($contrato->assinatura_versao ?? 0);
        return "private/contratos/assinatura/{$contrato->id}/v{$versao}";
    }

    // =====================
    // ADMIN (PAINEL VUE)
    // =====================

    public function iniciar(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::with(['client.company'])->findOrFail($id);
        $this->assertContratoDaCompany($contrato, $request);

        if (!$contrato->client) {
            return response()->json(['message' => 'Contrato sem cliente vinculado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'pdf_original' => 'required|file|mimes:pdf|max:10240',
        ]);

        $cliente = $contrato->client;
        $cpf = preg_replace('/\D/', '', (string) ($cliente->cpf ?? ''));
        if ($cpf === '') {
            return response()->json(['message' => 'Cliente sem CPF.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Status inicial
        if (empty($contrato->assinatura_status)) {
            $contrato->assinatura_status = self::STATUS_PENDING_ACCEPTANCE;
        }

        // Versão: só incrementa quando iniciar novamente (ex.: reenvio)
        $contrato->assinatura_versao = max(1, ((int) $contrato->assinatura_versao) + 1);

        // Provisionar login do cliente (usuario = CPF). Se já houver senha, não gerar outra.
        $senhaInicial = null;
        $tinhaSenha = !empty($cliente->password);
        if (empty($cliente->usuario)) {
            $cliente->usuario = $cpf;
        }
        if (!$tinhaSenha) {
            $senhaInicial = $this->gerarSenhaInicial();
            $cliente->password = Hash::make($senhaInicial);
        }
        $cliente->save();

        // Salvar PDF original + hash
        $dir = $this->contratoStorageDir($contrato);
        $file = $request->file('pdf_original');
        $originalPath = "{$dir}/contrato_original.pdf";
        Storage::disk('local')->putFileAs($dir, $file, 'contrato_original.pdf');
        $absOriginal = storage_path('app/' . $originalPath);
        $hashOriginal = is_file($absOriginal) ? hash_file('sha256', $absOriginal) : null;

        $contrato->pdf_original_path = $originalPath;
        $contrato->pdf_original_sha256 = $hashOriginal;
        $contrato->pdf_final_path = null;
        $contrato->pdf_final_sha256 = null;
        $contrato->aceite_at = null;
        $contrato->finalizado_at = null;
        $contrato->save();

        $this->registrarEvento($contrato, 'admin', (int) (auth('api')->id() ?? 0), 'SIGN_INITIATED', $request, [
            'pdf_original_path' => $originalPath,
            'pdf_original_sha256' => $hashOriginal,
        ]);

        // Mensagem WhatsApp (enviada automaticamente, com fallback para cópia manual)
        $telefone = preg_replace('/\D/', '', (string) ($cliente->telefone_celular_1 ?? ''));
        $whatsNumero = $telefone ? ('55' . $telefone) : null;
        if ($tinhaSenha) {
            $mensagem = "Você tem um contrato pendente para assinatura no aplicativo.\n\nUsuário (CPF): {$cliente->usuario}\n\nAbra o app, faça login com sua senha já cadastrada e assine o contrato pendente.";
        } else {
            $mensagem = "Acesso ao aplicativo do contrato:\n\nUsuário (CPF): {$cliente->usuario}\nSenha: {$senhaInicial}\n\nAbra o app e faça login para assinar o contrato pendente.";
        }

        $enviado = false;
        if ($whatsNumero) {
            $enviado = $this->enviarWhatsApp($cliente, $mensagem);
        }

        $this->registrarEvento($contrato, 'admin', (int) (auth('api')->id() ?? 0), 'SIGN_WHATSAPP_SENT', $request, [
            'enviado' => (bool) $enviado,
            'whatsapp_numero' => $whatsNumero,
            'tinha_senha' => (bool) $tinhaSenha,
        ]);

        return response()->json([
            'success' => true,
            'contrato_id' => $contrato->id,
            'assinatura_status' => $contrato->assinatura_status,
            'assinatura_versao' => $contrato->assinatura_versao,
            'cliente_usuario' => $cliente->usuario,
            'cliente_senha_inicial' => $senhaInicial,
            'whatsapp_numero' => $whatsNumero,
            'whatsapp_mensagem' => $mensagem,
            'whatsapp_enviado' => (bool) $enviado,
            'cliente_tinha_senha' => (bool) $tinhaSenha,
        ]);
    }

    public function detalhes(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::with([
            'client',
            'assinaturaEventos' => function ($q) { $q->orderBy('created_at', 'asc'); },
            'assinaturaEvidencias' => function ($q) { $q->orderBy('created_at', 'asc'); },
            'assinaturaOtps' => function ($q) { $q->orderBy('created_at', 'desc'); },
            'assinaturaDesafios' => function ($q) { $q->orderBy('created_at', 'desc'); },
        ])->findOrFail($id);
        $this->assertContratoDaCompany($contrato, $request);

        return response()->json([
            'id' => $contrato->id,
            'assinatura_status' => $contrato->assinatura_status,
            'assinatura_versao' => $contrato->assinatura_versao,
            'aceite_at' => $contrato->aceite_at,
            'finalizado_at' => $contrato->finalizado_at,
            'pdf_original_path' => $contrato->pdf_original_path,
            'pdf_original_sha256' => $contrato->pdf_original_sha256,
            'pdf_final_path' => $contrato->pdf_final_path,
            'pdf_final_sha256' => $contrato->pdf_final_sha256,
            'cliente' => $contrato->client,
            'eventos' => $contrato->assinaturaEventos,
            'evidencias' => $contrato->assinaturaEvidencias,
            'otps' => $contrato->assinaturaOtps->take(5),
            'desafios' => $contrato->assinaturaDesafios->take(5),
        ]);
    }

    public function revisar(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::with('client')->findOrFail($id);
        $this->assertContratoDaCompany($contrato, $request);

        $request->validate([
            'acao' => 'required|string|in:aprovar,reprovar,solicitar_reenvio',
            'justificativa' => 'nullable|string|max:1000',
        ]);

        $acao = $request->input('acao');
        $just = $request->input('justificativa');

        if ($acao === 'aprovar') {
            $contrato->assinatura_status = self::STATUS_SIGNED;
            $evento = 'ADMIN_APPROVED';
        } elseif ($acao === 'reprovar') {
            $contrato->assinatura_status = self::STATUS_REJECTED;
            $evento = 'ADMIN_REJECTED';
        } else {
            $contrato->assinatura_status = self::STATUS_RESUBMIT_REQUIRED;
            $evento = 'ADMIN_RESUBMIT_REQUIRED';
        }

        $contrato->save();
        $this->registrarEvento($contrato, 'admin', (int) (auth('api')->id() ?? 0), $evento, $request, [
            'justificativa' => $just,
        ]);

        return response()->json(['success' => true, 'assinatura_status' => $contrato->assinatura_status]);
    }

    public function downloadPdfOriginal(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDaCompany($contrato, $request);
        if (!$contrato->pdf_original_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_original_path);
        if (!is_file($abs)) {
            abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        }
        return response()->download($abs, "contrato-{$contrato->id}-original.pdf");
    }

    public function downloadPdfFinal(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDaCompany($contrato, $request);
        if (!$contrato->pdf_final_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_final_path);
        if (!is_file($abs)) {
            abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        }
        return response()->download($abs, "contrato-{$contrato->id}-assinado.pdf");
    }

    public function downloadEvidencia(Request $request, int $contratoId, int $evidenciaId)
    {
        $contrato = SimulacaoEmprestimo::findOrFail($contratoId);
        $this->assertContratoDaCompany($contrato, $request);

        $ev = ContratoAssinaturaEvidencia::where('contrato_id', $contratoId)->where('id', $evidenciaId)->firstOrFail();
        $abs = storage_path('app/' . $ev->path);
        if (!is_file($abs)) {
            abort(Response::HTTP_NOT_FOUND, 'Evidência não encontrada.');
        }

        $filename = "contrato-{$contratoId}-evidencia-{$ev->tipo}-{$evidenciaId}";
        $ext = pathinfo($abs, PATHINFO_EXTENSION);
        return response()->download($abs, $filename . ($ext ? ".{$ext}" : ''));
    }

    // =====================
    // APP (CLIENTE)
    // =====================

    public function contratosCliente(Request $request)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $companyId = (int) $request->header('company-id');

        $q = SimulacaoEmprestimo::where('client_id', $cliente->id);
        if ($companyId > 0) {
            $q->where('company_id', $companyId);
        }

        $otpVerifiedAtSub = ContratoAssinaturaOtp::select('verified_at')
            ->whereColumn('contrato_id', 'simulacoes_emprestimo.id')
            ->orderByDesc('id')
            ->limit(1);

        $contratos = $q->whereNotNull('assinatura_status')
            ->whereNotIn('assinatura_status', [self::STATUS_SIGNED, self::STATUS_REJECTED])
            ->orderBy('updated_at', 'desc')
            ->select([
                'id',
                'assinatura_status',
                'assinatura_versao',
                'data_assinatura',
                'valor_contrato',
                'aceite_at',
                'finalizado_at',
                'created_at',
                'updated_at',
            ])
            ->addSelect(['otp_verified_at' => $otpVerifiedAtSub])
            ->get();

        return response()->json(['data' => $contratos]);
    }

    public function pdfOriginalLinkCliente(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!$contrato->pdf_original_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        }

        $expiresAt = Carbon::now()->addMinutes(10);
        $url = URL::temporarySignedRoute('assinatura.public.pdf-original', $expiresAt, ['id' => $contrato->id]);

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function pdfFinalLinkCliente(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!$contrato->pdf_final_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        }

        $expiresAt = Carbon::now()->addMinutes(10);
        $url = URL::temporarySignedRoute('assinatura.public.pdf-final', $expiresAt, ['id' => $contrato->id]);

        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function pdfOriginalCliente(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!$contrato->pdf_original_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_original_path);
        if (!is_file($abs)) abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        return response()->download($abs, "contrato-{$contrato->id}-original.pdf");
    }

    public function pdfFinalCliente(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!$contrato->pdf_final_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_final_path);
        if (!is_file($abs)) abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        return response()->download($abs, "contrato-{$contrato->id}-assinado.pdf");
    }

    // =====================
    // PÚBLICO (LINK ASSINADO)
    // =====================

    public function pdfOriginalPublic(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        if (!$contrato->pdf_original_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_original_path);
        if (!is_file($abs)) abort(Response::HTTP_NOT_FOUND, 'PDF original não encontrado.');
        return response()->download($abs, "contrato-{$contrato->id}-original.pdf");
    }

    public function pdfFinalPublic(Request $request, int $id)
    {
        $contrato = SimulacaoEmprestimo::findOrFail($id);
        if (!$contrato->pdf_final_path) {
            abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        }
        $abs = storage_path('app/' . $contrato->pdf_final_path);
        if (!is_file($abs)) abort(Response::HTTP_NOT_FOUND, 'PDF final não encontrado.');
        return response()->download($abs, "contrato-{$contrato->id}-assinado.pdf");
    }

    public function aceite(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with('client')->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        $request->validate([
            'aceite' => 'required|boolean|in:1,true',
            'device' => 'nullable|array',
        ]);

        if ($contrato->assinatura_status !== self::STATUS_PENDING_ACCEPTANCE) {
            return response()->json(['message' => 'Contrato não está pendente de aceite.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $contrato->aceite_at = Carbon::now();
        $contrato->assinatura_status = self::STATUS_EVIDENCE_PENDING;
        $contrato->save();

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'CLIENT_ACCEPTED', $request, [], $request->input('device'));

        return response()->json(['success' => true, 'assinatura_status' => $contrato->assinatura_status, 'aceite_at' => $contrato->aceite_at]);
    }

    public function desafioVideo(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with('client')->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        $texto = 'Diga em voz alta: ' . Str::upper(Str::random(3)) . ' ' . random_int(10, 99) . ' ' . Str::upper(Str::random(3));
        $expires = Carbon::now()->addMinutes(3);

        $desafio = ContratoAssinaturaDesafio::create([
            'contrato_id' => $contrato->id,
            'tipo' => 'video',
            'desafio_texto' => $texto,
            'expires_at' => $expires,
            'meta_json' => null,
        ]);

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'VIDEO_CHALLENGE_ISSUED', $request, [
            'desafio_id' => $desafio->id,
            'expires_at' => $expires->toISOString(),
        ], $request->input('device'));

        return response()->json([
            'success' => true,
            'desafio' => [
                'id' => $desafio->id,
                'texto' => $texto,
                'expires_at' => $expires,
            ],
        ]);
    }

    public function evidencias(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with('client')->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!in_array($contrato->assinatura_status, [self::STATUS_EVIDENCE_PENDING, self::STATUS_RESUBMIT_REQUIRED], true)) {
            return response()->json(['message' => 'Contrato não está aguardando evidências.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'tipo' => 'required|string|in:doc_frente,doc_verso,selfie,video',
            'arquivo' => 'required|file|max:20480',
            'captured_at' => 'nullable|date',
            'device' => 'nullable|array',
            'desafio_id' => 'nullable|integer',
        ]);

        $tipo = $request->input('tipo');
        $file = $request->file('arquivo');

        // Se for vídeo e enviaram desafio, validar expiração/uso
        $meta = [];
        if ($tipo === 'video' && $request->filled('desafio_id')) {
            $desafio = ContratoAssinaturaDesafio::where('contrato_id', $contrato->id)
                ->where('id', (int) $request->input('desafio_id'))
                ->first();
            if ($desafio) {
                if ($desafio->used_at) {
                    return response()->json(['message' => 'Desafio já utilizado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                if (Carbon::now()->greaterThan($desafio->expires_at)) {
                    return response()->json(['message' => 'Desafio expirado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $desafio->used_at = Carbon::now();
                $desafio->save();
                $meta['desafio_id'] = $desafio->id;
            }
        }

        $dir = $this->contratoStorageDir($contrato) . '/evidencias';
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $filename = $tipo . '_' . Carbon::now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $ext;
        $path = "{$dir}/{$filename}";
        Storage::disk('local')->putFileAs($dir, $file, $filename);

        $abs = storage_path('app/' . $path);
        $sha = is_file($abs) ? hash_file('sha256', $abs) : '';

        $ev = ContratoAssinaturaEvidencia::create([
            'contrato_id' => $contrato->id,
            'tipo' => $tipo,
            'path' => $path,
            'sha256' => $sha,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'captured_at' => $request->filled('captured_at') ? Carbon::parse($request->input('captured_at')) : null,
            'uploaded_at' => Carbon::now(),
            'meta_json' => $meta ?: null,
        ]);

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'EVIDENCE_UPLOADED', $request, [
            'evidencia_id' => $ev->id,
            'tipo' => $tipo,
            'sha256' => $sha,
        ], $request->input('device'));

        // Atualizar status quando já houver evidências mínimas
        $tipos = ContratoAssinaturaEvidencia::where('contrato_id', $contrato->id)->pluck('tipo')->unique()->values()->all();
        $temMinimo = in_array('doc_frente', $tipos, true) && in_array('doc_verso', $tipos, true) && in_array('selfie', $tipos, true);
        if ($temMinimo) {
            $contrato->assinatura_status = self::STATUS_EVIDENCE_SUBMITTED;
            $contrato->save();
        }

        return response()->json(['success' => true, 'assinatura_status' => $contrato->assinatura_status, 'evidencia_id' => $ev->id]);
    }

    public function enviarOtp(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with(['client.company'])->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        if (!in_array($contrato->assinatura_status, [self::STATUS_EVIDENCE_SUBMITTED, self::STATUS_OTP_PENDING], true)) {
            return response()->json(['message' => 'Envie as evidências antes de solicitar o código.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $code = (string) random_int(100000, 999999);
        $expires = Carbon::now()->addMinutes(5);
        $otp = ContratoAssinaturaOtp::create([
            'contrato_id' => $contrato->id,
            'canal' => 'whatsapp',
            'code_hash' => Hash::make($code),
            'expires_at' => $expires,
            'attempts' => 0,
            'verified_at' => null,
            'last_sent_at' => Carbon::now(),
            'meta_json' => null,
        ]);

        $contrato->assinatura_status = self::STATUS_OTP_PENDING;
        $contrato->save();

        $msg = "Código para assinatura do contrato {$contrato->id}: {$code}\n\nValidade: 5 minutos.";
        $enviado = $this->enviarWhatsApp($cliente, $msg);

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'OTP_SENT', $request, [
            'otp_id' => $otp->id,
            'canal' => 'whatsapp',
            'expires_at' => $expires->toISOString(),
            'sent' => (bool) $enviado,
        ], $request->input('device'));

        return response()->json([
            'success' => true,
            'assinatura_status' => $contrato->assinatura_status,
            'expires_at' => $expires,
            'sent' => (bool) $enviado,
        ]);
    }

    public function validarOtp(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with('client')->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        $request->validate([
            'codigo' => 'required|string|min:4|max:10',
            'device' => 'nullable|array',
        ]);

        $otp = ContratoAssinaturaOtp::where('contrato_id', $contrato->id)->orderBy('created_at', 'desc')->first();
        if (!$otp) {
            return response()->json(['message' => 'Nenhum código enviado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($otp->verified_at) {
            return response()->json(['success' => true, 'verified_at' => $otp->verified_at]);
        }
        if (Carbon::now()->greaterThan($otp->expires_at)) {
            return response()->json(['message' => 'Código expirado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ((int) $otp->attempts >= 5) {
            return response()->json(['message' => 'Muitas tentativas. Solicite um novo código.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $codigo = (string) $request->input('codigo');
        $ok = Hash::check($codigo, $otp->code_hash);
        $otp->attempts = (int) $otp->attempts + 1;

        if ($ok) {
            $otp->verified_at = Carbon::now();
        }
        $otp->save();

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'OTP_VALIDATION', $request, [
            'otp_id' => $otp->id,
            'ok' => (bool) $ok,
            'attempts' => (int) $otp->attempts,
        ], $request->input('device'));

        if (!$ok) {
            return response()->json(['message' => 'Código inválido.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['success' => true, 'verified_at' => $otp->verified_at]);
    }

    public function finalizar(Request $request, int $id)
    {
        /** @var Client $cliente */
        $cliente = auth('clientes')->user();
        $contrato = SimulacaoEmprestimo::with(['client'])->findOrFail($id);
        $this->assertContratoDoCliente($contrato, $cliente);

        $request->validate([
            'device' => 'nullable|array',
        ]);

        if ($contrato->assinatura_status !== self::STATUS_OTP_PENDING) {
            return response()->json(['message' => 'Contrato não está pronto para finalização.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Evidências mínimas
        $evidencias = ContratoAssinaturaEvidencia::where('contrato_id', $contrato->id)->get(['id', 'tipo', 'sha256']);
        $tipos = $evidencias->pluck('tipo')->unique()->values()->all();
        $temMinimo = in_array('doc_frente', $tipos, true) && in_array('doc_verso', $tipos, true) && in_array('selfie', $tipos, true);
        if (!$temMinimo) {
            return response()->json(['message' => 'Evidências mínimas não foram enviadas (documento frente/verso e selfie).'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // OTP verificado
        $otp = ContratoAssinaturaOtp::where('contrato_id', $contrato->id)->orderBy('created_at', 'desc')->first();
        if (!$otp || !$otp->verified_at) {
            return response()->json(['message' => 'Código 2FA ainda não foi validado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (empty($contrato->pdf_original_path) || empty($contrato->pdf_original_sha256)) {
            return response()->json(['message' => 'Contrato sem PDF original vinculado.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $device = $request->input('device') ?: null;
        $deviceResumo = '';
        if (is_array($device)) {
            $parts = [];
            foreach (['modelo', 'model', 'fabricante', 'os', 'so', 'app_version', 'versao_app'] as $k) {
                if (!empty($device[$k])) $parts[] = "{$k}: {$device[$k]}";
            }
            $deviceResumo = implode(' | ', $parts);
        }

        $registro = [
            'data_hora' => Carbon::now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s T'),
            'metodo' => 'WhatsApp (OTP)',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_resumo' => $deviceResumo,
            'hash_original' => $contrato->pdf_original_sha256,
            // hash_final é calculado e armazenado no backend após a geração do PDF final.
            'hash_final' => '',
            'evidencias' => $evidencias->pluck('id')->values()->all(),
        ];

        $pdfService = new ContratoAssinaturaPdfService();
        $resultadoPdf = $pdfService->gerarPdfFinalComRegistro($contrato, $registro);

        $contrato->pdf_final_path = $resultadoPdf['pdf_final_path'];
        $contrato->pdf_final_sha256 = $resultadoPdf['pdf_final_sha256'];
        $contrato->finalizado_at = Carbon::now();
        $contrato->assinatura_status = self::STATUS_SIGNED_PENDING_REVIEW;
        $contrato->save();

        $this->registrarEvento($contrato, 'cliente', (int) $cliente->id, 'SIGN_FINALIZED', $request, [
            'pdf_final_path' => $contrato->pdf_final_path,
            'pdf_final_sha256' => $contrato->pdf_final_sha256,
            'registro_hash_final' => $resultadoPdf['registro_hash_final'] ?? null,
            'otp_verified_at' => $otp->verified_at ? $otp->verified_at->toISOString() : null,
        ], $device);

        return response()->json([
            'success' => true,
            'assinatura_status' => $contrato->assinatura_status,
            'finalizado_at' => $contrato->finalizado_at,
            'pdf_final_sha256' => $contrato->pdf_final_sha256,
        ]);
    }
}

