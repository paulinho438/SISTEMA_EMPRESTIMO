<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import ClientService from '@/service/ClientService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

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
            loading: ref(false),
            filters: ref(null),
            display: ref(false),
            form: ref({}),
            toggleValue: ref(false)
        };
    },
    methods: {
        async close() {
            try {
                await this.clientService.mensagemEmMassa(this.form);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Mensagem enviada com sucesso!',
                    life: 3000
                });

                setTimeout(() => {
                    this.router.push({ name: 'emprestimosfinalizadosList' });
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

            if (data.emprestimos.count_late_parcels <= 2) {
                mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$${data.emprestimos.valor + 100}. Gostaria de contratar?`;
            }

            if (data.emprestimos.count_late_parcels >= 3 && data.emprestimos.count_late_parcels <= 5) {
                mensagem = `Olá ${data.nome_completo}, estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$${data.emprestimos.valor}. Gostaria de contratar?`;
            }

            if (data.emprestimos.count_late_parcels >= 6 && data.emprestimos.count_late_parcels <= 8) {
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
        async handleToggleChange() {

            this.clientService
                .alterEnvioAutomaticoRenovacao()
                .then((response) => {
                    this.toggleValue = response.data;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_clientes_sensitive') ? dado : '*********';
        },
        getClientes() {
            this.loading = true;

            this.clientService
                .getEnvioAutomaticoRenovacao()
                .then((response) => {
                    this.toggleValue = response.data;
                })
                .finally(() => {
                    this.loading = false;
                });

            this.clientService
                .getClientesDisponiveis()
                .then((response) => {
                    this.Clientes = response.data;
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: error.message,
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
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
                nome_completo: { value: null, matchMode: FilterMatchMode.CONTAINS }
            };
        },
        clearFilter() {
            this.initFilters();
        }
    },
    beforeMount() {
        this.initFilters();
    },
    mounted() {
        this.permissionsService.hasPermissionsView('view_clientes');
        this.getClientes();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Clientes Disponíveis</h5>
                </div>

                <div class="col-4 px-0 py-0 text-right">
                    <div class="col-12 px-0 py-0 text-right" style="display: flex; justify-content: end; align-items: center; gap: 10px">
                        <Button v-if="$store?.getters?.isCompany?.whatsapp && $store.getters.isCompany.whatsapp.trim() !== ''" label="Enviar Mensagem Geral" class="p-button-sm p-button-info" :icon="icons.PLUS" @click="display = true" />
                    </div>
                </div>
            </div>
            <div class="grid flex flex-wrap mb-3 px-4 pt-2" style="align-items: center; justify-content: end">
                <div class="col-4 px-0 py-0 text-right">
                    <div class="col-12 px-0 py-0 text-right" style="display: flex; justify-content: end; gap: 10px">
                        <h5>Envio automático ao quitar o empréstimo</h5>
                        <InputSwitch v-model="toggleValue" @change="handleToggleChange" />
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="mt-3">
                    <DataTable
                        dataKey="id"
                        :value="Clientes"
                        :paginator="true"
                        :rows="10"
                        :loading="loading"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} cliente(s)"
                        responsiveLayout="scroll"
                    >
                        <template #header>
                            <div class="flex justify-content-between">
                                <Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined p-button-sm" @click="clearFilter()" />
                                <span class="p-input-icon-left">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters.nome_completo.value" placeholder="Informe o Nome" class="p-inputtext-sm" />
                                </span>
                            </div>
                        </template>

                        <Column field="name" header="Nome Completo" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Nome Completo</span>
                                {{ slotProps.data.nome_completo }}
                            </template>
                        </Column>
                        <Column field="status" header="status" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <Button :label="slotProps.data.emprestimos.count_late_parcels" :class="getStatusClass(slotProps.data.emprestimos.count_late_parcels)" />
                            </template>
                        </Column>
                        <Column field="name" header="CPF" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">CPF</span>
                                {{ dadosSensiveis(slotProps.data.cpf) }}
                            </template>
                        </Column>
                        <Column field="name" header="RG" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">RG</span>
                                {{ dadosSensiveis(slotProps.data.rg) }}
                            </template>
                        </Column>
                        <Column field="name" header="Telefone Principal" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Telefone Principal</span>
                                {{ dadosSensiveis(slotProps.data.telefone_celular_1) }}
                            </template>
                        </Column>
                        <Column field="name" header="Telefone Secundário" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Telefone Secundário</span>
                                {{ dadosSensiveis(slotProps.data.telefone_celular_2) }}
                            </template>
                        </Column>
                        <Column field="name" header="Dt. Nascimento" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Dt. Nascimento</span>
                                {{ slotProps.data.data_nascimento }}
                            </template>
                        </Column>

                        <Column field="created_at" header="Dt. Criação" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Dt. Criação</span>
                                {{ slotProps.data.created_at }}
                            </template>
                        </Column>

                        <Column field="data_quitacao" header="Dt. Quitação" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Dt. Quitação</span>
                                {{ slotProps.data.emprestimos.data_quitacao }}
                            </template>
                        </Column>

                        <Column field="edit" header="Whatsapp" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard && slotProps.data.emprestimos.count_late_parcels < 9"
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
        <Dialog header="Mensagem em Massa" v-model:visible="display" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
            <div class="flex flex-column gap-2 m-2 mt-4">
                <label for="username">Data Inicio</label>
                <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data de Inicio'" v-model="form.dt_inicio" showIcon :showOnFocus="false" class="" />
            </div>
            <div class="flex flex-column gap-2 m-2 mt-4">
                <label for="username">Data Final</label>
                <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data Final'" v-model="form.dt_final" showIcon :showOnFocus="false" class="" />
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
            <p class="line-height-3 mb-4 mt-4">Ao confirmar, todos os clientes com o status na cor selecionada receberão uma mensagem padrão.</p>
            <Message v-if="error" severity="error">{{ error }}</Message>
            <template #footer>
                <Button label="Confirmar" @click="close" icon="pi pi-check" class="p-button-outlined" />
            </template>
        </Dialog>
    </div>
</template>
