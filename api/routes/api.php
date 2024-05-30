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

};

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;


Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'id']);

Route::get('/setup-teste', function (Request $request){
    var_dump($request->headers);
});






Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/parcela/{id}/infoemprestimofront', [EmprestimoController::class, 'infoEmprestimoFront']);


Route::middleware('auth:api')->group(function(){
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

    Route::get('/cliente', [ClientController::class, 'all']);
    Route::get('/cliente/{id}', [ClientController::class, 'id']);
    Route::get('/cliente/{id}/delete', [ClientController::class, 'delete']);
    Route::put('/cliente/{id}', [ClientController::class, 'update']);
    Route::post('/cliente', [ClientController::class, 'insert']);

    Route::get('/cobranca/atrasadas', [ClientController::class, 'parcelasAtrasadas']);

    Route::get('/empresa', [CompanyController::class, 'get']);


    Route::get('/contaspagar', [ContaspagarController::class, 'all']);
    Route::get('/contaspagar/{id}', [ContaspagarController::class, 'id']);
    Route::get('/contaspagar/{id}/delete', [ContaspagarController::class, 'delete']);
    Route::post('/contaspagar', [ContaspagarController::class, 'insert']);

    Route::get('/contaspagar/pagamentos/pendentes', [ContaspagarController::class, 'pagamentoPendentes']);
    Route::post('/contaspagar/pagamentos/transferencia/{id}', [EmprestimoController::class, 'pagamentoTransferencia']);
    Route::post('/contaspagar/pagamentos/reprovaremprestimo/{id}', [EmprestimoController::class, 'reprovarEmprestimo']);

    Route::get('/contasreceber', [ContasreceberController::class, 'all']);
    Route::get('/contasreceber/{id}', [ContasreceberController::class, 'id']);
    Route::get('/contasreceber/{id}/delete', [ContasreceberController::class, 'delete']);
    Route::post('/contasreceber', [ContasreceberController::class, 'insert']);

    Route::get('/movimentacaofinanceira', [MovimentacaofinanceiraController::class, 'all']);
    Route::get('/movimentacaofinanceira/{id}', [MovimentacaofinanceiraController::class, 'id']);

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
    Route::post('/parcela/{id}/baixamanual', [EmprestimoController::class, 'baixaManual']);
    Route::post('/parcela/{id}/baixamanualcobrador', [EmprestimoController::class, 'baixaManualCobrador']);
    Route::post('/parcela/{id}/infoemprestimo', [EmprestimoController::class, 'infoEmprestimo']);
    Route::post('/parcela/{id}/cobraramanha', [EmprestimoController::class, 'cobrarAmanha']);
    Route::get('/parcela/{id}/cancelarbaixamanual', [EmprestimoController::class, 'cancelarBaixaManual']);



    Route::post('/emprestimo/baixadesconto/{id}', [EmprestimoController::class, 'baixaDesconto']);
    Route::post('/emprestimo/search/fornecedor', [EmprestimoController::class, 'searchFornecedor']);
    Route::post('/emprestimo/search/cliente', [EmprestimoController::class, 'searchCliente']);
    Route::post('/emprestimo/search/banco', [EmprestimoController::class, 'searchBanco']);
    Route::post('/emprestimo/search/costcenter', [EmprestimoController::class, 'searchCostcenter']);
    Route::post('/emprestimo/search/consultor', [EmprestimoController::class, 'searchConsultor']);
    Route::get('/feriados', [EmprestimoController::class, 'feriados']);
    Route::get('/efibank', [EmprestimoController::class, 'efibank']);
    Route::get('/testebank', [EmprestimoController::class, 'testeBank']);

    Route::get('/recalcularparcelas', [EmprestimoController::class, 'recalcularParcelas']);



});
