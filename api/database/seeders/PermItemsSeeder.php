<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("permitems")->insert(
            [
                "name"             => "Criar Empresas",
                "slug"             => "criar_empresas",
                "group"            => "empresa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Usuarios",
                "slug"             => "criar_usuarios",
                "group"            => "usuario"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Dashboard",
                "slug"             => "view_dashboard",
                "group"            => "dashboard"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Permissões",
                "slug"             => "view_permissions",
                "group"            => "permissoes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Permissões",
                "slug"             => "view_permissions_create",
                "group"            => "permissoes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Permissões",
                "slug"             => "view_permissions_edit",
                "group"            => "permissoes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Permissões",
                "slug"             => "view_permissions_delete",
                "group"            => "permissoes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Menu Cadastro",
                "slug"             => "view_menu_cadastro",
                "group"            => "cadastro"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Categorias",
                "slug"             => "view_categories",
                "group"            => "categorias"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Categoria",
                "slug"             => "view_categories_create",
                "group"            => "categorias"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Categoria",
                "slug"             => "view_categories_edit",
                "group"            => "categorias"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Categoria",
                "slug"             => "view_categories_delete",
                "group"            => "categorias"
            ]
        );


        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Centro de Custo",
                "slug"             => "view_costcenter",
                "group"            => "centrodecusto"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Centro de Custo",
                "slug"             => "view_costcenter_create",
                "group"            => "centrodecusto"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Centro de Custo",
                "slug"             => "view_costcenter_edit",
                "group"            => "centrodecusto"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Centro de Custo",
                "slug"             => "view_costcenter_delete",
                "group"            => "centrodecusto"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Bancos",
                "slug"             => "view_bancos",
                "group"            => "bancos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Bancos",
                "slug"             => "view_bancos_create",
                "group"            => "bancos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Bancos",
                "slug"             => "view_bancos_edit",
                "group"            => "bancos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Bancos",
                "slug"             => "view_bancos_delete",
                "group"            => "bancos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Clientes",
                "slug"             => "view_clientes",
                "group"            => "clientes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Dados Sensiveis Cliente",
                "slug"             => "view_clientes_sensitive",
                "group"            => "clientes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Cliente",
                "slug"             => "view_clientes_create",
                "group"            => "clientes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Cliente",
                "slug"             => "view_clientes_edit",
                "group"            => "clientes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Cliente",
                "slug"             => "view_clientes_delete",
                "group"            => "clientes"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Emprestimos",
                "slug"             => "view_emprestimos",
                "group"            => "emprestimos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Emprestimos",
                "slug"             => "view_emprestimos_create",
                "group"            => "emprestimos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Emprestimos",
                "slug"             => "view_emprestimos_edit",
                "group"            => "emprestimos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Emprestimos",
                "slug"             => "view_emprestimos_delete",
                "group"            => "emprestimos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Autorizar Pagamentos Empréstimos",
                "slug"             => "view_emprestimos_autorizar_pagamentos",
                "group"            => "emprestimos"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Fornecedores",
                "slug"             => "view_fornecedores",
                "group"            => "fornecedores"
            ]
        );



        DB::table("permitems")->insert(
            [
                "name"             => "Criar Fornecedor",
                "slug"             => "view_fornecedores_create",
                "group"            => "fornecedores"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar Fornecedor",
                "slug"             => "view_fornecedores_edit",
                "group"            => "fornecedores"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Fornecedor",
                "slug"             => "view_fornecedores_delete",
                "group"            => "fornecedores"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Contas a Pagar",
                "slug"             => "view_contaspagar",
                "group"            => "contaspagar"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Contas a Pagar",
                "slug"             => "view_contaspagar_create",
                "group"            => "contaspagar"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Baixa Contas a Pagar",
                "slug"             => "view_contaspagar_baixa",
                "group"            => "contaspagar"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Contas a Pagar",
                "slug"             => "view_contaspagar_delete",
                "group"            => "contaspagar"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Contas a Receber",
                "slug"             => "view_contasreceber",
                "group"            => "contasreceber"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Criar Contas a Receber",
                "slug"             => "view_contasreceber_create",
                "group"            => "contasreceber"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Baixa Contas a Receber",
                "slug"             => "view_contasreceber_baixa",
                "group"            => "contasreceber"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Excluir Contas a Receber",
                "slug"             => "view_contasreceber_delete",
                "group"            => "contasreceber"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Movimentacao Financeira",
                "slug"             => "view_movimentacaofinanceira",
                "group"            => "movimentacaofinanceira"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Alteração de Parâmetros da Empresa",
                "slug"             => "edit_empresa",
                "group"            => "alteracaoempresa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Fechamento de caixa",
                "slug"             => "view_fechamentocaixa",
                "group"            => "fechamentocaixa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Efetuar Saque no Fechamento de Caixa",
                "slug"             => "view_sacarfechamentocaixa",
                "group"            => "fechamentocaixa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Gerar Deposito Fechamento de Caixa",
                "slug"             => "view_depositarfechamentocaixa",
                "group"            => "fechamentocaixa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Encerrar Fechamento de caixa",
                "slug"             => "view_encerrarfechamentocaixa",
                "group"            => "fechamentocaixa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Alterar Fechamento de caixa",
                "slug"             => "view_alterarfechamentocaixa",
                "group"            => "fechamentocaixa"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Tela de Baixas pelo Aplicativo",
                "slug"             => "aplicativo_baixas",
                "group"            => "aplicativo"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Criação de empresas",
                "slug"             => "view_criacao_empresas",
                "group"            => "companies"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Editar empresas",
                "slug"             => "view_editar_empresas",
                "group"            => "companies"
            ]
        );

        DB::table("permitems")->insert(
            [
                "name"             => "Visualizar Permissões MASTERGERAL",
                "slug"             => "view_mastergeral",
                "group"            => "geral"
            ]
        );

    }
}
