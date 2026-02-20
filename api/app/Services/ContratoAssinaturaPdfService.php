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
     * @return array{pdf_final_path: string, pdf_final_sha256: string}
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
        $finalPath = "{$baseDir}/contrato_assinado.pdf";

        // 1) Renderizar página de registro
        $registroPdf = Pdf::loadView('assinatura.registro', [
            'contrato' => $contrato,
            'cliente' => $contrato->client,
            'registro' => $registro,
        ])->setPaper('a4');

        $absRegistro = storage_path('app/' . $registroPath);
        if (!is_dir(dirname($absRegistro))) {
            mkdir(dirname($absRegistro), 0775, true);
        }
        file_put_contents($absRegistro, $registroPdf->output());

        // 2) Merge (original + registro)
        $absFinal = storage_path('app/' . $finalPath);
        if (!is_dir(dirname($absFinal))) {
            mkdir(dirname($absFinal), 0775, true);
        }

        $fpdi = new Fpdi();
        $fpdi->setTitle("Contrato {$contrato->id} - Assinado");
        $fpdi->setCreator('Sistema de Assinatura Eletrônica');
        $fpdi->setAuthor('Sistema');
        $fpdi->SetCompression(true);

        $this->importarPdfNoDestino($fpdi, $absOriginal);
        $this->importarPdfNoDestino($fpdi, $absRegistro);

        $fpdi->Output($absFinal, 'F');

        $hashFinal = hash_file('sha256', $absFinal) ?: '';

        return [
            'pdf_final_path' => $finalPath,
            'pdf_final_sha256' => $hashFinal,
            'gerado_em' => Carbon::now()->toISOString(),
        ];
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

