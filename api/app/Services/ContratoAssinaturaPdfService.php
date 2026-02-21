<?php

namespace App\Services;

use App\Models\SimulacaoEmprestimo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use setasign\Fpdi\Fpdi;

class ContratoAssinaturaPdfService
{
    /**
     * Gera o PDF final anexando a página de registro ao PDF original.
     *
     * Observação: o hash do PDF final é calculado após a geração do arquivo.
     * Ao inserir o hash dentro do próprio PDF, o arquivo muda e o hash mudaria novamente.
     * Aqui nós garantimos que o registro não fique vazio, exibindo um hash calculado
     * na etapa imediatamente anterior à geração do arquivo final.
     *
     * @return array{pdf_final_path: string, pdf_final_sha256: string, registro_hash_final: string}
     */
    public function gerarPdfFinalComRegistro(SimulacaoEmprestimo $contrato, array $registro): array
    {
        if (empty($contrato->pdf_original_path)) {
            throw new \RuntimeException('PDF original não está vinculado ao contrato.');
        }

        $absOriginal = storage_path('app/' . $contrato->pdf_original_path);
        if (!is_file($absOriginal)) {
            throw new \RuntimeException('Arquivo do PDF original não encontrado.');
        }

        $versao = (int) ($contrato->assinatura_versao ?? 0);
        if ($versao <= 0) {
            throw new \RuntimeException('Versão de assinatura inválida.');
        }

        $baseDir = "private/contratos/assinatura/{$contrato->id}/v{$versao}";
        $registroPath = "{$baseDir}/registro_assinatura.pdf";
        $registroPath2 = "{$baseDir}/registro_assinatura_com_hash.pdf";
        $tempPath = "{$baseDir}/contrato_assinado_preliminar.pdf";
        $finalPath = "{$baseDir}/contrato_assinado.pdf";

        // 1) Renderizar página de registro (sem hash final)
        $absRegistro = storage_path('app/' . $registroPath);
        $this->renderizarRegistroPdf($contrato, $registro, $absRegistro);

        // 2) Gerar um PDF preliminar (original + registro sem hash) para calcular um hash e exibi-lo no registro
        $absTemp = storage_path('app/' . $tempPath);
        $this->mergeOriginalComRegistro($contrato->id, $absOriginal, $absRegistro, $absTemp);
        $hashPreliminar = hash_file('sha256', $absTemp) ?: '';

        // 3) Renderizar o registro novamente já com o hash preenchido
        $registro2 = array_merge($registro, ['hash_final' => $hashPreliminar]);
        $absRegistro2 = storage_path('app/' . $registroPath2);
        $this->renderizarRegistroPdf($contrato, $registro2, $absRegistro2);

        // 4) Gerar o PDF final (original + registro com hash preenchido)
        $absFinal = storage_path('app/' . $finalPath);
        $this->mergeOriginalComRegistro($contrato->id, $absOriginal, $absRegistro2, $absFinal);
        $hashFinal = hash_file('sha256', $absFinal) ?: '';

        return [
            'pdf_final_path' => $finalPath,
            'pdf_final_sha256' => $hashFinal,
            'registro_hash_final' => $hashPreliminar,
            'gerado_em' => Carbon::now()->toISOString(),
        ];
    }

    private function renderizarRegistroPdf(SimulacaoEmprestimo $contrato, array $registro, string $absRegistro): void
    {
        $registroPdf = Pdf::loadView('assinatura.registro', [
            'contrato' => $contrato,
            'cliente' => $contrato->client,
            'registro' => $registro,
        ])->setPaper('a4');

        if (!is_dir(dirname($absRegistro))) {
            mkdir(dirname($absRegistro), 0775, true);
        }
        file_put_contents($absRegistro, $registroPdf->output());
    }

    private function mergeOriginalComRegistro(int $contratoId, string $absOriginal, string $absRegistro, string $absDestino): void
    {
        if (!is_dir(dirname($absDestino))) {
            mkdir(dirname($absDestino), 0775, true);
        }

        $fpdi = new Fpdi();
        $fpdi->setTitle("Contrato {$contratoId} - Assinado");
        $fpdi->setCreator('Sistema de Assinatura Eletrônica');
        $fpdi->setAuthor('Sistema');
        $fpdi->SetCompression(true);

        $this->importarPdfNoDestino($fpdi, $absOriginal);
        $this->importarPdfNoDestino($fpdi, $absRegistro);
        $fpdi->Output($absDestino, 'F');
    }

    private function importarPdfNoDestino(Fpdi $dest, string $sourceFile): void
    {
        $pageCount = $dest->setSourceFile($sourceFile);
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $dest->importPage($pageNo);
            $size = $dest->getTemplateSize($tplId);
            $dest->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $dest->useTemplate($tplId);
        }
    }
}

