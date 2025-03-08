<?php

use App\Http\Controllers\{
    AuthController,
    CompanyController,
    UserController,
    PermgroupController,
    PermitemController,
    CategoryController,
    CostcenterController,
    BancoController,
    ClientController,
    EmprestimoController,
    JurosController,
    FornecedorController,
    ContaspagarController,
    ContasreceberController,
    MovimentacaofinanceiraController,
    UsuarioController,
    FeriadoController,
    AddressController,
    BotaoCobrancaController,
    DashboardController,
    GestaoController,
    LogController,
    PlanosController,
    LocacaoController
};
use App\Models\BotaoCobranca;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

use App\Mail\ExampleEmail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Http;


Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::get('/users', [UserController::class, 'index']);

Route::get('/users/{id}', [UserController::class, 'id']);
Route::get('/testarAutomacaoRenovacao', [CompanyController::class, 'testarAutomacaoRenovacao']);

Route::post('/informar_localizacao_app', [UsuarioController::class, 'informarLocalizacaoApp']);




Route::get('/setup-teste', function (Request $request) {
    $details = [
        'title' => 'Relatório de Emprestimos',
        'body' => 'This is a test email using MailerSend in Laravel.'
    ];

    Mail::to('paulo_henrique500@hotmail.com')->send(new ExampleEmail($details, []));

    return 'Email sent successfully!';
});

Route::post('/informar_localizacao', [UsuarioController::class, 'informarLocalizacao']);


Route::post('/webhook/retorno_cobranca', [EmprestimoController::class, 'webhookRetornoCobranca']);
Route::post('/webhook/retorno_pagamento', [EmprestimoController::class, 'webhookPagamento']);
Route::post('/manutencao/corrigir_pix', [EmprestimoController::class, 'corrigirPix']);
Route::post('/manutencao/aplicar_multa_parcela/{id}', [EmprestimoController::class, 'aplicarMultaParcela']);


Route::post('/rotina/locacao_data_corte/{id}', [LocacaoController::class, 'dataCorte']);





Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/parcela/{id}/infoemprestimofront', [EmprestimoController::class, 'infoEmprestimoFront']);
Route::post('/parcela/{id}/personalizarpagamento', [EmprestimoController::class, 'personalizarPagamento']);


Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard/info-conta', [DashboardController::class, 'infoConta']);



    Route::post('/auth/validate', [AuthController::class, 'validateToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/permission_groups', [PermgroupController::class, 'index']);
    Route::get('/permission_groups/{id}', [PermgroupController::class, 'id']);
    Route::get('/permission_groups/{id}/delete', [PermgroupController::class, 'delete']);
    Route::get('/permission_groups/items/{id}', [PermgroupController::class, 'getItemsForGroup']);
    Route::get('/permission_groups/items/user/{id}', [PermgroupController::class, 'getItemsForGroupUser']);
    Route::put('/permission_groups/{id}', [PermgroupController::class, 'update']);
    Route::post('/permission_groups', [PermgroupController::class, 'insert']);

    Route::get('/permission_items', [PermitemController::class, 'index']);
    Route::get('/permission_items/{id}', [PermitemController::class, 'id']);

    Route::get('/categories', [CategoryController::class, 'all']);
    Route::get('/categories/{id}', [CategoryController::class, 'id']);
    Route::get('/categories/{id}/delete', [CategoryController::class, 'delete']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::post('/categories', [CategoryController::class, 'insert']);

    Route::get('/costcenter', [CostcenterController::class, 'all']);
    Route::get('/costcenter/{id}', [CostcenterController::class, 'id']);
    Route::get('/costcenter/{id}/delete', [CostcenterController::class, 'delete']);
    Route::put('/costcenter/{id}', [CostcenterController::class, 'update']);
    Route::post('/costcenter', [CostcenterController::class, 'insert']);

    Route::get('/bancos', [BancoController::class, 'all']);
    Route::get('/bancos/{id}', [BancoController::class, 'id']);
    Route::get('/bancos/{id}/delete', [BancoController::class, 'delete']);
    Route::post('/bancos/{id}', [BancoController::class, 'update']);
    Route::post('/bancos', [BancoController::class, 'insert']);
    Route::post('/alterarcaixa/{id}', [BancoController::class, 'alterarCaixa']);
    Route::post('/sacar/{id}', [BancoController::class, 'sacar']);
    Route::post('/depositar/{id}', [BancoController::class, 'depositar']);
    Route::post('/saqueconsulta/{id}', [BancoController::class, 'saqueConsulta']);
    Route::post('/efetuarsaque/{id}', [BancoController::class, 'efetuarSaque']);
    Route::post('/fechamentocaixa/{id}', [BancoController::class, 'fechamentoCaixa']);


    Route::get('/clientesdisponiveis', [ClientController::class, 'clientesDisponiveis']);
    Route::post('/enviarmensagemmassa', [ClientController::class, 'enviarMensagemMassa']);

    Route::get('/cliente', [ClientController::class, 'all']);
    Route::get('/cliente/{id}', [ClientController::class, 'id']);
    Route::get('/cliente/{id}/delete', [ClientController::class, 'delete']);
    Route::put('/cliente/{id}', [ClientController::class, 'update']);
    Route::post('/cliente', [ClientController::class, 'insert']);

    Route::get('/gestao/usuariosempresa/{id}', [GestaoController::class, 'getAllUsuariosEmpresa']);

    Route::get('/usuariocompanies', [UsuarioController::class, 'allCompany']);
    Route::get('/usuario', [UsuarioController::class, 'all']);
    Route::get('/usuario/{id}', [UsuarioController::class, 'id']);
    Route::get('/usuario/{id}/delete', [UsuarioController::class, 'delete']);
    Route::put('/usuario/{id}', [UsuarioController::class, 'update']);
    Route::post('/usuario', [UsuarioController::class, 'insert']);
    Route::get('/cobranca/atrasadas', [ClientController::class, 'parcelasAtrasadas']);
    Route::get('/mapa/clientes', [ClientController::class, 'mapaClientes']);
    Route::get('/mapa/consultor', [ClientController::class, 'mapaConsultor']);
    Route::post('/mapa/rotaconsultor', [ClientController::class, 'mapaRotaConsultor']);


    Route::get('/cobranca/buttonpressed', [BotaoCobrancaController::class, 'pressed']);
    Route::get('/cobranca/getbuttonpressed', [BotaoCobrancaController::class, 'getButtonPressed']);


    Route::get('/empresas/{id}', [CompanyController::class, 'get']);
    Route::get('/empresa', [CompanyController::class, 'get']);

    Route::get('/empresas', [CompanyController::class, 'getAll']);
    Route::post('/empresas', [CompanyController::class, 'insert']);
    Route::put('/empresas/{id}', [CompanyController::class, 'update']);

    Route::get('/getenvioautomaticorenovacao', [CompanyController::class, 'getEnvioAutomaticoRenovacao']);

    Route::post('/empresas/alterenvioautomaticorenovacao', [CompanyController::class, 'alterEnvioAutomaticoRenovacao']);


    Route::get('/planos/{id}', [PlanosController::class, 'get']);
    Route::get('/planos', [PlanosController::class, 'getAll']);
    Route::post('/planos', [PlanosController::class, 'insert']);
    Route::put('/planos/{id}', [PlanosController::class, 'update']);


    Route::get('/contaspagar', [ContaspagarController::class, 'all']);
    Route::get('/contaspagar/{id}', [ContaspagarController::class, 'id']);
    Route::get('/contaspagar/{id}/delete', [ContaspagarController::class, 'delete']);
    Route::post('/contaspagar', [ContaspagarController::class, 'insert']);

    Route::get('/contaspagar/pagamentos/pendentes', [ContaspagarController::class, 'pagamentoPendentes']);
    Route::get('/contaspagar/pagamentos/pendentesaplicativo', [ContaspagarController::class, 'pagamentoPendentesAplicativo']);

    Route::post('/contaspagar/pagamentos/transferenciaconsultar/{id}', [EmprestimoController::class, 'pagamentoTransferenciaConsultar']);
    Route::post('/contaspagar/pagamentos/transferencia/{id}', [EmprestimoController::class, 'pagamentoTransferencia']);

    Route::post('/contaspagar/pagamentos/transferenciatituloconsultar/{id}', [EmprestimoController::class, 'pagamentoTransferenciaTituloAPagarConsultar']);
    Route::post('/contaspagar/pagamentos/transferenciatitulo/{id}', [EmprestimoController::class, 'pagamentoTransferenciaTituloAPagar']);

    Route::post('/contaspagar/pagamentos/reprovaremprestimo/{id}', [EmprestimoController::class, 'reprovarEmprestimo']);
    Route::post('/contaspagar/pagamentos/reprovarcontasapagar/{id}', [EmprestimoController::class, 'reprovarContasAPagar']);

    Route::get('/contasreceber', [ContasreceberController::class, 'all']);
    Route::get('/contasreceber/{id}', [ContasreceberController::class, 'id']);
    Route::get('/contasreceber/{id}/delete', [ContasreceberController::class, 'delete']);
    Route::post('/contasreceber', [ContasreceberController::class, 'insert']);

    Route::get('/movimentacaofinanceira', [MovimentacaofinanceiraController::class, 'all']);
    Route::get('/movimentacaofinanceira/{id}', [MovimentacaofinanceiraController::class, 'id']);

    Route::get('/log', [LogController::class, 'all']);


    Route::get('/fornecedor', [FornecedorController::class, 'all']);
    Route::get('/fornecedor/{id}', [FornecedorController::class, 'id']);
    Route::get('/fornecedor/{id}/delete', [FornecedorController::class, 'delete']);
    Route::put('/fornecedor/{id}', [FornecedorController::class, 'update']);
    Route::post('/fornecedor', [FornecedorController::class, 'insert']);

    Route::get('/endereco/{id}', [AddressController::class, 'id']);
    Route::get('/endereco/{id}/cliente', [AddressController::class, 'all']);
    Route::get('/endereco/{id}/delete', [AddressController::class, 'delete']);
    Route::get('/endereco/cancelarcadastro', [AddressController::class, 'cancelarCadastro']);
    Route::put('/endereco/{id}', [AddressController::class, 'update']);
    Route::post('/endereco', [AddressController::class, 'insert']);

    Route::get('/juros', [JurosController::class, 'get']);
    Route::put('/juros/update', [JurosController::class, 'update']);


    Route::get('/cobrancaautomatica', [EmprestimoController::class, 'cobrancaAutomatica']);


    Route::get('/emprestimo', [EmprestimoController::class, 'all']);
    Route::get('/emprestimo/{id}', [EmprestimoController::class, 'id']);
    Route::get('/emprestimo/{id}/delete', [EmprestimoController::class, 'delete']);
    Route::put('/emprestimo/{id}', [EmprestimoController::class, 'update']);
    Route::post('/emprestimo', [EmprestimoController::class, 'insert']);
    Route::post('/emprestimorefinanciamento', [EmprestimoController::class, 'insertRefinanciamento']);
    Route::post('/parcela/{id}/baixamanual', [EmprestimoController::class, 'baixaManual']);
    Route::post('/parcela/{id}/baixamanualcobrador', [EmprestimoController::class, 'baixaManualCobrador']);
    Route::post('/parcela/{id}/infoemprestimo', [EmprestimoController::class, 'infoEmprestimo']);
    Route::post('/parcela/{id}/cobraramanha', [EmprestimoController::class, 'cobrarAmanha']);
    Route::get('/parcela/{id}/cancelarbaixamanual', [EmprestimoController::class, 'cancelarBaixaManual']);

    Route::get('/baixa/pendentesparahoje', [EmprestimoController::class, 'parcelasPendentesParaHoje']);
    Route::get('/baixa/parcelasparaextorno', [EmprestimoController::class, 'parcelasParaExtorno']);


    Route::get('/cobrancateste', [EmprestimoController::class, 'gerarCobranca']);



    Route::post('/emprestimo/baixadesconto/{id}', [EmprestimoController::class, 'baixaDesconto']);
    Route::post('/emprestimo/refinanciamento/{id}', [EmprestimoController::class, 'refinanciamento']);
    Route::post('/emprestimo/search/fornecedor', [EmprestimoController::class, 'searchFornecedor']);
    Route::post('/emprestimo/search/cliente', [EmprestimoController::class, 'searchCliente']);
    Route::post('/emprestimo/search/banco', [EmprestimoController::class, 'searchBanco']);
    Route::post('/emprestimo/search/bancofechamento', [EmprestimoController::class, 'searchBancoFechamento']);
    Route::post('/emprestimo/search/costcenter', [EmprestimoController::class, 'searchCostcenter']);
    Route::post('/emprestimo/search/consultor', [EmprestimoController::class, 'searchConsultor']);
    Route::get('/feriados', [EmprestimoController::class, 'feriados']);
    Route::get('/wallet', [EmprestimoController::class, 'wallet']);
    Route::get('/testebank', [EmprestimoController::class, 'testeBank']);

    Route::get('/recalcularparcelas', [EmprestimoController::class, 'recalcularParcelas']);

    Route::get('/feriado', [FeriadoController::class, 'all']);
    Route::get('/feriado/{id}', [FeriadoController::class, 'id']);
    Route::get('/feriado/{id}/delete', [FeriadoController::class, 'delete']);
    Route::put('/feriado/{id}', [FeriadoController::class, 'update']);
    Route::post('/feriado', [FeriadoController::class, 'insert']);

    Route::post('/gerar-comprovante', function (Request $request) {
        // $dados = [
        //     'valor' => 100,
        //     'tipo_transferencia' => 'PIX',
        //     'descricao' => 'Transferência realizada com sucesso',
        //     'destino_nome' => 'Ray JR',
        //     'destino_cpf' => '055.463.561-54',
        //     'destino_chave_pix' => '055.463.561-54',
        //     'origem_nome' => 'BCODEX TECNOLOGIA E SERVICOS LTDA',
        //     'origem_cnpj' => '52.196.079/0001-71',
        //     'origem_instituicao' => 'BANCO BTG PACTUAL S.A.',
        //     'data_hora' => date('d/m/Y H:i:s'),
        //     'id_transacao' => '1234567890',
        // ];

        $dados = $request->all();

        $html = view('comprovante-template', $dados)->render();

        // Salvar o HTML em um arquivo temporário
        $htmlFilePath = storage_path('app/public/comprovante.html');
        file_put_contents($htmlFilePath, $html);

        // Caminho para o arquivo PNG de saída
        $pngPath = storage_path('app/public/comprovante.png');

        // Configurações de tamanho, qualidade e zoom
        $width = 800;    // Largura em pixels
        $height = 1600;  // Altura em pixels
        $quality = 100;  // Qualidade máxima
        $zoom = 1.8;     // Zoom de 2x

        // Executar o comando wkhtmltoimage com ajustes
        $command = "xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
        shell_exec($command);

        // Verificar se o PNG foi gerado
        if (file_exists($pngPath)) {
            try {
                // Enviar o PNG gerado para o endpoint
                $response = Http::attach(
                    'arquivo', // Nome do campo no formulário
                    file_get_contents($pngPath), // Conteúdo do arquivo
                    'comprovante.png' // Nome do arquivo enviado
                )->post('http://node.agecontrole.com.br/enviar-pdf', [
                    'numero' => '556193305267',
                ]);

                // Verificar a resposta do endpoint
                if ($response->successful()) {
                    return response()->json(['message' => 'Imagem enviada com sucessos!'], 200);
                } else {
                    return response()->json([
                        'error' => 'Falha ao enviar imagem',
                        'details' => $response->body(),
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Erro ao enviar imagem',
                    'details' => $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json(['error' => 'Falha ao gerar a imagem'], 500);
        }
    });

    // Route::post('/gerar-comprovante', function (Request $request) {
    //     // Recebe os dados para o comprovante
    //     $dados = [
    //         'valor' => 100,
    //         'tipo_transferencia' => 'PIX',
    //         'descricao' => 'Transferência realizada com sucesso',
    //         'destino_nome' => 'Ray JR',
    //         'destino_cpf' => '055.463.561-54',
    //         'destino_chave_pix' => '055.463.561-54',
    //         'origem_nome' => 'BCODEX TECNOLOGIA E SERVICOS LTDA',
    //         'origem_cnpj' => '52.196.079/0001-71',
    //         'origem_instituicao' => 'BANCO BTG PACTUAL S.A.',
    //         'data_hora' => date('d/m/Y H:i:s'),
    //         'id_transacao' => '1234567890',
    //     ];

    //     // Gerar o PDF usando o template e os dados
    //     $pdf = Pdf::loadView('comprovante-template', $dados);

    //     // Salvar o PDF em um arquivo temporário
    //     $pdfPath = storage_path('app/public/comprovante.pdf');
    //     $pdf->save($pdfPath);

    //     // Enviar o PDF gerado para o endpoint externo
    //     $response = Http::attach(
    //         'arquivo', // Nome do campo no formulário
    //         file_get_contents($pdfPath), // Conteúdo do arquivo
    //         'comprovante.pdf' // Nome do arquivo enviado
    //     )->post('http://node2.agecontrole.com.br/enviar-pdf', [
    //         'numero' => '556193305267',
    //     ]);

    //     // Verificar a resposta do endpoint
    //     if ($response->successful()) {
    //         return response()->json(['message' => 'PDF enviado com sucesso!'], 200);
    //     } else {
    //         return response()->json(['error' => 'Falha ao enviar PDF', 'details' => $response->body()], 500);
    //     }
    // });
});
