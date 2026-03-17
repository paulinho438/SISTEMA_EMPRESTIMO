<script>
import {ref} from 'vue';
import {useRouter} from 'vue-router';
import {FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator} from 'primevue/api';
import ClientService from '@/service/ClientService';
import PermissionsService from '@/service/PermissionsService';
import {useToast} from 'primevue/usetoast';

export default {
    name: 'EmprestimosFinalizadosList',
    setup() {
        return {
            clientService: new ClientService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            Clientes: ref([]),
            ClientesParaContagem: ref([]),
            loading: ref(false),
            selectedRiskCategory: ref(null),
            filters: ref({
                global: {value: null, matchMode: FilterMatchMode.CONTAINS},
                name: {value: null, matchMode: FilterMatchMode.STARTS_WITH},
                'country.name': {value: null, matchMode: FilterMatchMode.STARTS_WITH},
                representative: {value: null, matchMode: FilterMatchMode.IN},
                status: {value: null, matchMode: FilterMatchMode.EQUALS},
                verified: {value: null, matchMode: FilterMatchMode.EQUALS}
            }),
            display: ref(false),
            form: ref({}),
            toggleValue: ref(false),
            mensagemAudioValue: ref(false),
            filtroComCnpj: ref(false),
            _getClientesSeq: 0,
            _getClientesTimer: null
        };
    },
    methods: {
        getRiskCategoryFromLateParcels(lateParcels) {
            const totalLateParcels = Number(lateParcels || 0);
            if (totalLateParcels <= 2) return 'verde';
            if (totalLateParcels <= 5) return 'azul';
            if (totalLateParcels <= 8) return 'amarelo';
            return 'vermelho';
        },
        getRiskCategoryLabel(category) {
            const labels = {
                verde: 'Bom pagador',
                azul: 'Pagador mediano',
                amarelo: 'Pagador ruim',
                vermelho: 'Pessimo pagador'
            };
            return labels[category] || 'Sem classificacao';
        },
        getRiskCategoryHelpText(category) {
            const descriptions = {
                verde: 'Historico com poucos atrasos e boa recorrencia de pagamento.',
                azul: 'Tem atrasos pontuais, mas ainda mantem um comportamento de pagamento regular.',
                amarelo: 'Frequencia de atraso elevada, exigindo maior atencao na renovacao.',
                vermelho: 'Inadimplencia critica e alto risco para nova concessao de credito.'
            };
            return descriptions[category] || '';
        },
        getRiskButtonSeverity(category) {
            const severityMap = {
                verde: 'success',
                azul: 'info',
                amarelo: 'warning',
                vermelho: 'danger'
            };
            return severityMap[category] || 'secondary';
        },
        getRiskCategoryCount(category) {
            return this.ClientesParaContagem.filter((client) => this.getRiskCategoryFromLateParcels(client.emprestimos?.count_late_parcels) === category).length;
        },
        getTotalClientesClassificados() {
            return this.ClientesParaContagem.length;
        },
        filterByRiskCategory(category) {
            this.selectedRiskCategory = this.selectedRiskCategory === category ? null : category;
            this.getClientes();
        },
        scheduleGetClientes() {
            if (this._getClientesTimer) clearTimeout(this._getClientesTimer);
            this._getClientesTimer = setTimeout(() => {
                this.getClientes();
            }, 300);
        },
        getApiFilterValue(filterKey) {
            const filter = this.filters[filterKey];
            if (!filter) return null;
            const constraint = filter?.constraints?.[0];
            const value = constraint?.value ?? filter?.value;
            if (value === null || value === undefined || value === '') return null;
            if (value instanceof Date) return value.toISOString().split('T')[0];
            return value;
        },
        buildClientesDisponiveisParams() {
            const params = {
                tem_cnpj: this.filtroComCnpj ? 1 : 0
            };

            if (this.selectedRiskCategory) {
                params.risco_pagador = this.selectedRiskCategory;
            }

            const filterKeys = ['global', 'nome_completo', 'cpf', 'rg', 'cnpj', 'telefone_celular_1', 'telefone_celular_2', 'data_nascimento', 'created_at', 'data_quitacao'];
            filterKeys.forEach((key) => {
                const value = this.getApiFilterValue(key);
                if (value !== null) {
                    params[key] = value;
                }
            });

            return params;
        },
        async exportarExcel() {
            try {
                this.loading = true;
                const params = this.buildClientesDisponiveisParams();
                const response = await this.clientService.exportarClientesDisponiveisExcel(params);

                const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `clientes_finalizados_renovacao_${new Date().toISOString().slice(0, 10)}.xlsx`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Excel exportado com sucesso',
                    life: 3000
                });
            } catch (error) {
                this.toast.add({
                    severity: ToastSeverity.ERROR,
                    detail: 'Erro ao exportar Excel',
                    life: 3000
                });
            } finally {
                this.loading = false;
            }
        },
        async close() {
            try {
                await this.clientService.mensagemEmMassa(this.form);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Mensagem enviada com sucesso!',
                    life: 3000
                });

                setTimeout(() => {
                    this.router.push({name: 'emprestimosfinalizadosList'});
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        open() {
            this.display.value = true;
        },
        goToWhatsApp(telefone, data) {
            let mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo.`;

            if (data.emprestimos?.count_late_parcels <= 2) {
                mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$${data.emprestimos.valor + 100}. Gostaria de contratar?`;
            }

            if (data.emprestimos?.count_late_parcels >= 3 && data.emprestimos?.count_late_parcels <= 5) {
                mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$${data.emprestimos.valor}. Gostaria de contratar?`;
            }

            if (data.emprestimos?.count_late_parcels >= 6 && data.emprestimos?.count_late_parcels <= 8) {
                mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$${data.emprestimos.valor - 100}. Gostaria de contratar?`;
            }
            // Remove caracteres não numéricos do telefone
            const telefoneFormatado = telefone.replace(/\D/g, '');
            // Codifica a mensagem para ser usada na URL
            const mensagemCodificada = encodeURIComponent(mensagem);
            // Constrói a URL do WhatsApp com a mensagem
            const whatsappUrl = `https://wa.me/${telefoneFormatado}?text=${mensagemCodificada}`;
            window.open(whatsappUrl, '_blank');
        },
        getStatusClass(status) {
            // Adicione classes de botões com base no status
            switch (status) {
                case 0:
                    return 'p-button-rounded p-button-success mr-2 mb-2';
                case 1:
                    return 'p-button-rounded p-button-success mr-2 mb-2';
                case 2:
                    return 'p-button-rounded p-button-success mr-2 mb-2';
                case 3:
                    return 'p-button-rounded p-button-info mr-2 mb-2';
                case 4:
                    return 'p-button-rounded p-button-info mr-2 mb-2';
                case 5:
                    return 'p-button-rounded p-button-info mr-2 mb-2';
                case 6:
                    return 'p-button-rounded p-button-warning mr-2 mb-2';
                case 7:
                    return 'p-button-rounded p-button-warning mr-2 mb-2';
                case 8:
                    return 'p-button-rounded p-button-warning mr-2 mb-2';
                case 9:
                    return 'p-button-rounded p-button-danger mr-2 mb-2';
                default:
                    return 'p-button-rounded p-button-danger mr-2 mb-2'; // Padrão
            }
        },
        getStatusLabelByLateParcels(lateParcels) {
            const category = this.getRiskCategoryFromLateParcels(lateParcels);
            return this.getRiskCategoryLabel(category);
        },
        getStatusClassByLateParcels(lateParcels) {
            const category = this.getRiskCategoryFromLateParcels(lateParcels);
            const classMap = {
                verde: 'p-button-rounded p-button-success mr-2 mb-2',
                azul: 'p-button-rounded p-button-info mr-2 mb-2',
                amarelo: 'p-button-rounded p-button-warning mr-2 mb-2',
                vermelho: 'p-button-rounded p-button-danger mr-2 mb-2'
            };
            return classMap[category] || 'p-button-rounded p-button-secondary mr-2 mb-2';
        },
        async handleToggleChange() {
            this.clientService
                .alterEnvioAutomaticoRenovacao()
                .then((response) => {
                    this.toggleValue = response.data.envio_automatico_renovacao;
                })
                .catch(() => {});
        },
        async mensagemAudioChange() {
            this.clientService
                .alterMensagemAudioAutomatico()
                .then((response) => {
                    this.mensagemAudioValue = response.data.mensagem_audio;
                })
                .catch(() => {});
        },
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_clientes_sensitive') ? dado : '*********';
        },
        formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },
        formatCnpj(cnpj) {
            if (!cnpj || String(cnpj).trim() === '') return '—';
            const digits = String(cnpj).replace(/\D/g, '');
            if (digits.length !== 14) return cnpj;
            return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        },
        normalizarClientes(raw) {
            return (raw || []).map((Clientes) => {
                if (Clientes.created_at) {
                    Clientes.created_at = new Date(Clientes.created_at);
                }

                if (Clientes.data_nascimento) {
                    Clientes.data_nascimento = new Date(`${Clientes.data_nascimento}T00:00:00`);
                }

                if (Clientes.emprestimos?.data_quitacao) {
                    Clientes.data_quitacao = new Date(Clientes.emprestimos.data_quitacao);
                }

                return Clientes;
            });
        },
        getClientes() {
            this.loading = true;
            const seq = ++this._getClientesSeq;

            const baseParams = {
                ...this.buildClientesDisponiveisParams(),
                temCnpj: this.filtroComCnpj
            };

            const paramsLista = {
                ...baseParams,
                riscoPagador: this.selectedRiskCategory
            };

            const paramsContagem = {
                ...baseParams,
                riscoPagador: null
            };

            Promise.all([
                this.clientService.getClientesDisponiveis(paramsContagem),
                this.clientService.getClientesDisponiveis(paramsLista)
            ])
                .then(([respContagem, respLista]) => {
                    if (seq !== this._getClientesSeq) return;
                    this.ClientesParaContagem = this.normalizarClientes(respContagem.data);
                    this.Clientes = this.normalizarClientes(respLista.data);
                })
                .catch((error) => {
                    if (seq !== this._getClientesSeq) return;
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: error.message,
                        life: 3000
                    });
                })
                .finally(() => {
                    if (seq !== this._getClientesSeq) return;
                    this.loading = false;
                });
        },
        carregarPreferenciasAutomacoes() {
            this.clientService
                .getEnvioAutomaticoRenovacao()
                .then((response) => {
                    this.toggleValue = response.data.envio_automatico_renovacao == 1 ? true : false;
                })
                .catch(() => {});

            this.clientService
                .getMensagemAudioAutomatico()
                .then((response) => {
                    this.mensagemAudioValue = response.data.mensagem_audio == 1 ? true : false;
                })
                .catch(() => {});
        },
        editCategory(id) {
            if (undefined === id) this.router.push('/clientes/add');
            else this.router.push(`/clientes/${id}/edit`);
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.clientService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getClientes();
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: error?.data?.message,
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        initFilters() {
            this.filters = {
                global: {value: null, matchMode: FilterMatchMode.CONTAINS},

                nome_completo: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                data_nascimento: {
                    operator: 'and',
                    constraints: [{value: null, matchMode: 'dateIs'}]
                },

                created_at: {
                    operator: 'and',
                    constraints: [{value: null, matchMode: 'dateIs'}]
                },

                data_quitacao: {
                    operator: 'and',
                    constraints: [{value: null, matchMode: 'dateIs'}]
                },

                telefone_celular_1: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                telefone_celular_2: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                rg: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                cpf: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                cnpj: {
                    operator: FilterOperator.AND,
                    constraints: [{value: null, matchMode: FilterMatchMode.STARTS_WITH}]
                },

                // status: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                // id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },


                // nome_consultor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                // valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                // saldoareceber: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                // saldo_total_parcelas_pagas: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                // dt_lancamento: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // },

                // porcentagem: { value: [0, 100], matchMode: FilterMatchMode.BETWEEN }

                // cpf: {
                //     operator: FilterOperator.AND,
                //     constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }]
                // },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // saldo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                // created_at: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // },
                // data_nascimento: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // }
            };
        },
        clearFilter() {
            this.initFilters();
            this.selectedRiskCategory = null;
            this.getClientes();
        }
    },
    beforeMount() {
        this.initFilters();
    },
    mounted() {
        this.permissionsService.hasPermissionsView('view_clientes');
        this.carregarPreferenciasAutomacoes();
        this.getClientes();
    }
};
</script>

<template>
    <Toast/>
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Clientes
                        Disponíveis</h5>
                </div>

                <div class="col-4 px-0 py-0 text-right mb-2">
                    <div class="col-12 px-0 py-0 text-right"
                         style="display: flex; justify-content: end; align-items: center; gap: 10px">
                        <Button
                            v-if="$store?.getters?.isCompany?.whatsapp && $store.getters.isCompany.whatsapp.trim() !== ''"
                            label="Enviar Mensagem Geral" class="p-button-sm p-button-info" :icon="icons.PLUS"
                            @click="display = true"/>
                    </div>
                </div>
            </div>

            <div class="grid flex flex-wrap mb-3 mt-2 px-4 pt-2" style="align-items: center; justify-content: end">
                <div class="col-12 px-0 py-0 text-right">
                    <div class="col-12 px-0 py-0 text-right" style="display: flex; justify-content: end; gap: 10px">
                        <h5>Envio automático ao quitar o empréstimo</h5>
                        <InputSwitch v-model="toggleValue" @change="handleToggleChange"/>
                    </div>
                </div>
            </div>

            <div class="grid flex flex-wrap mb-3 mt-2 px-4 pt-2" style="align-items: center; justify-content: end">
                <div class="col-12 px-0 py-0 text-right">
                    <div class="col-12 px-0 py-0 text-right" style="display: flex; justify-content: end; gap: 10px">
                        <h5>Envio automático mensagem com audio</h5>
                        <InputSwitch v-model="mensagemAudioValue" @change="mensagemAudioChange"/>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <Button
                            :label="`Todos ${getTotalClientesClassificados()}`"
                            :severity="selectedRiskCategory === null ? 'primary' : 'secondary'"
                            :outlined="selectedRiskCategory !== null"
                            class="p-button-sm"
                            @click="filterByRiskCategory(null)"
                        />
                        <Button
                            v-for="category in ['verde', 'azul', 'amarelo', 'vermelho']"
                            :key="category"
                            :label="`${getRiskCategoryLabel(category)} ${getRiskCategoryCount(category)}`"
                            :severity="getRiskButtonSeverity(category)"
                            :outlined="selectedRiskCategory !== category"
                            class="p-button-sm"
                            v-tooltip.top="getRiskCategoryHelpText(category)"
                            @click="filterByRiskCategory(category)"
                        />
                    </div>
                    <DataTable
                        :value="Clientes"
                        :paginator="true"
                        class="p-datatable-gridlines"
                        :rows="10"
                        dataKey="id"
                        :rowHover="true"
                        v-model:filters="filters"
                        filterDisplay="menu"
                        :loading="loading"
                        :filters="filters"
                        responsiveLayout="scroll"
                        :globalFilterFields="['nome_completo', 'cpf', 'cnpj']"
                        @filter="scheduleGetClientes"
                    >
                        <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row flex-wrap gap-2">
                                <div class="flex gap-2 align-items-center flex-wrap">
                                    <Button type="button" icon="pi pi-filter-slash" label="Clear"
                                            class="p-button-outlined mb-2"
                                            @click="clearFilter()"/>
                                    <Button
                                        :label="filtroComCnpj ? 'Todos os clientes' : 'Apenas com CNPJ'"
                                        :class="filtroComCnpj ? 'p-button-outlined p-button-success mb-2' : 'p-button-outlined mb-2'"
                                        icon="pi pi-building"
                                        @click="filtroComCnpj = !filtroComCnpj; getClientes()"
                                    />
                                    <Button
                                        label="Exportar Excel"
                                        icon="pi pi-file-excel"
                                        class="p-button-success mb-2"
                                        @click="exportarExcel"
                                    />
                                </div>
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search"/>
                                    <InputText v-model="filters['global'].value" placeholder="Pesquisar ..."
                                               style="width: 100%" @input="scheduleGetClientes"/>
                                </span>
                            </div>
                        </template>
                        <template #empty> Nenhum Cliente Encontrado.</template>
                        <template #loading> Carregando os Clientes. Aguarde!</template>

                        <Column field="nome_completo" header="Cliente" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_completo }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar Nome Completo Cliente"/>
                            </template>
                        </Column>
                        <Column field="status" header="Status" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <Button
                                    :label="getStatusLabelByLateParcels(slotProps.data.emprestimos?.count_late_parcels)"
                                    :class="getStatusClassByLateParcels(slotProps.data.emprestimos?.count_late_parcels)"
                                    @click="filterByRiskCategory(getRiskCategoryFromLateParcels(slotProps.data.emprestimos?.count_late_parcels))"
                                />
                            </template>
                        </Column>

                        <Column field="cpf" header="CPF" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ dadosSensiveis(data.cpf) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar pelo CPF"/>
                            </template>
                        </Column>

                        <Column field="rg" header="RG" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ dadosSensiveis(data.rg) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar pelo RG"/>
                            </template>
                        </Column>

                        <Column field="cnpj" header="CNPJ" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ dadosSensiveis(formatCnpj(data.cnpj)) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar pelo CNPJ"/>
                            </template>
                        </Column>

                        <Column field="telefone_celular_1" header="Telefone Principal" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ dadosSensiveis(data.telefone_celular_1) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar Telefone"/>
                            </template>
                        </Column>

                        <Column field="telefone_celular_2" header="Telefone Secundário" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ dadosSensiveis(data.telefone_celular_2) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter"
                                           placeholder="Buscar Telefone"/>
                            </template>
                        </Column>

                        <Column header="Dt. Nascimento" filterField="data_nascimento" dataType="date"
                                style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatDate(data.data_nascimento) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy"
                                          placeholder="Selecione uma data"
                                          class="p-column-filter"/>
                            </template>
                        </Column>

                        <Column header="Dt. Criação" filterField="created_at" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatDate(data.created_at) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy"
                                          placeholder="Selecione uma data"
                                          class="p-column-filter"/>
                            </template>
                        </Column>

                        <Column header="Dt. Quitação" filterField="data_quitacao" dataType="date"
                                style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatDate(data?.data_quitacao) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy"
                                          placeholder="Selecione uma data"
                                          class="p-column-filter"/>
                            </template>
                        </Column>

                        <Column field="edit" header="Whatsapp" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard && slotProps.data.emprestimos?.count_late_parcels < 9"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :icon="icons.WHATSAPP"
                                    v-tooltip.top="'Whatsapp'"
                                    @click.prevent="goToWhatsApp(slotProps.data.telefone_celular_1, slotProps.data)"
                                />
                            </template>
                        </Column>


                    </DataTable>
                </div>
            </div>
        </div>
        <Dialog header="Mensagem em Massa" v-model:visible="display" :breakpoints="{ '960px': '75vw' }"
                :style="{ width: '30vw' }" :modal="true">
            <div class="flex flex-column gap-2 m-2 mt-4">
                <label for="username">Data Inicio</label>
                <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data de Inicio'" v-model="form.dt_inicio"
                          showIcon
                          :showOnFocus="false" class=""/>
            </div>
            <div class="flex flex-column gap-2 m-2 mt-4">
                <label for="username">Data Final</label>
                <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data Final'" v-model="form.dt_final"
                          showIcon
                          :showOnFocus="false" class=""/>
            </div>
            <div class="flex flex-column gap-2 m-2 mt-4">
                <label for="color">Cor do status</label>
                <Dropdown
                    v-model="form.status"
                    :options="[
                        { label: 'Todos', value: 0 },
                        { label: 'Verde', value: 1 },
                        { label: 'Azul', value: 2 },
                        { label: 'Amarelo', value: 3 }
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Selecione o Status"
                    class="w-full"
                />
            </div>
            <p class="line-height-3 mb-4 mt-4">Ao confirmar, todos os clientes com o status na cor selecionada receberão
                uma
                mensagem padrão.</p>
            <Message v-if="error" severity="error">{{ error }}</Message>
            <template #footer>
                <Button label="Confirmar" @click="close" icon="pi pi-check" class="p-button-outlined"/>
            </template>
        </Dialog>
    </div>
</template>
