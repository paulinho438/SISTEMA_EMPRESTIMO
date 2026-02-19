<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import ClientService from '@/service/ClientService';

const props = defineProps({
    modelValue: { type: Array, default: () => [] }
});
const emit = defineEmits(['update:modelValue']);

const router = useRouter();
const clientService = new ClientService();

const garantias = computed({
    get: () => props.modelValue || [],
    set: (v) => emit('update:modelValue', v)
});

const tiposGarantia = [
    { label: 'Avalista', value: 'avalista' },
    { label: 'Imóvel', value: 'imovel' },
    { label: 'Veículo', value: 'veiculo' },
    { label: 'Devedor solidário', value: 'devedor_solidario' },
    { label: 'Recebíveis', value: 'recebiveis' },
    { label: 'Outras garantias', value: 'outras' }
];

const garantiaVazia = () => ({ tipo: 'avalista', pessoa_id: null, dados: {} });

function adicionarGarantia() {
    garantias.value = [...(garantias.value || []), garantiaVazia()];
}

function removerGarantia(index) {
    garantias.value = garantias.value.filter((_, i) => i !== index);
}

function moverGarantia(index, dir) {
    const arr = [...garantias.value];
    const novo = index + dir;
    if (novo < 0 || novo >= arr.length) return;
    [arr[index], arr[novo]] = [arr[novo], arr[index]];
    garantias.value = arr;
}

function garantirDados(g) {
    if (!g.dados) g.dados = {};
    return g.dados;
}

const clientesPFFiltered = ref([]);
async function searchClientesPF(event) {
    const q = event.query || '';
    try {
        const res = await clientService.getByTipoPessoa('PF', q);
        clientesPFFiltered.value = res.data?.data || [];
    } catch {
        clientesPFFiltered.value = [];
    }
}

function onPFSelect(garantia, selected) {
    if (!selected) return;
    garantia.pessoa_id = selected.id;
    garantia.dados = garantia.dados || {};
    Object.assign(garantia.dados, {
        nome_completo: selected.nome_completo,
        cpf: selected.cpf,
        rg: selected.rg,
        orgao_emissor: selected.orgao_emissor_rg,
        email: selected.email,
        telefone: selected.telefone_celular_1,
        renda_mensal: selected.renda_mensal,
        estado_civil: selected.estado_civil,
        regime_bens: selected.regime_bens,
    });
    if (selected.address?.[0]) {
        const a = selected.address[0];
        garantia.dados.cep = a.cep;
        garantia.dados.estado = a.estado;
        garantia.dados.cidade = a.city;
        garantia.dados.bairro = a.neighborhood;
        garantia.dados.endereco = a.address;
        garantia.dados.numero = a.number;
        garantia.dados.complemento = a.complement;
    }
    garantia.dados.pessoa_selecionada = selected;
}

function abrirCadastroPF() {
    router.push({ name: 'pfAdd' });
}
</script>

<template>
    <div class="card">
        <h6 class="mb-3">3. Garantias</h6>
        <p class="text-500 text-sm mb-3">Adicione as garantias do contrato. Cada garantia pode ser de um tipo diferente.</p>

        <div v-for="(g, idx) in garantias" :key="idx" class="mb-3 border-1 surface-border border-round p-3">
            <div class="flex justify-content-between align-items-center mb-2">
                <Dropdown
                    v-model="g.tipo"
                    :options="tiposGarantia"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Tipo de garantia"
                    class="w-18rem"
                />
                <div class="flex gap-1">
                    <Button icon="pi pi-arrow-up" class="p-button-text p-button-sm" :disabled="idx === 0" @click="moverGarantia(idx, -1)" />
                    <Button icon="pi pi-arrow-down" class="p-button-text p-button-sm" :disabled="idx === garantias.length - 1" @click="moverGarantia(idx, 1)" />
                    <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="removerGarantia(idx)" />
                </div>
            </div>

            <!-- Avalista / Devedor solidário -->
            <div v-if="g.tipo === 'avalista' || g.tipo === 'devedor_solidario'" class="grid formgrid p-fluid">
                <div class="field col-12 md:col-6">
                    <label>Selecionar PF existente ou cadastrar novo</label>
                    <div class="flex gap-2">
                        <AutoComplete
                            v-model="garantirDados(g).pessoa_selecionada"
                            :suggestions="clientesPFFiltered"
                            optionLabel="label_completo"
                            placeholder="Buscar pessoa física"
                            class="flex-1"
                            @complete="searchClientesPF"
                            @item-select="(e) => onPFSelect(g, e.value)"
                        />
                        <Button label="Cadastrar PF" icon="pi pi-plus" class="p-button-outlined p-button-sm" @click="abrirCadastroPF" />
                    </div>
                </div>
                <div class="field col-12 md:col-4">
                    <label>Nome Completo *</label>
                    <InputText v-model="garantirDados(g).nome_completo" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>CPF *</label>
                    <InputMask v-model="garantirDados(g).cpf" mask="999.999.999-99" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>RG</label>
                    <InputMask v-model="garantirDados(g).rg" mask="9.999.999" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Órgão Emissor</label>
                    <InputText v-model="garantirDados(g).orgao_emissor" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>CEP *</label>
                    <InputMask v-model="garantirDados(g).cep" mask="99999-999" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Estado</label>
                    <InputText v-model="garantirDados(g).estado" maxlength="2" placeholder="UF" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Cidade *</label>
                    <InputText v-model="garantirDados(g).cidade" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Bairro *</label>
                    <InputText v-model="garantirDados(g).bairro" />
                </div>
                <div class="field col-12 md:col-6">
                    <label>Endereço *</label>
                    <InputText v-model="garantirDados(g).endereco" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Número *</label>
                    <InputText v-model="garantirDados(g).numero" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Complemento</label>
                    <InputText v-model="garantirDados(g).complemento" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>E-mail *</label>
                    <InputText v-model="garantirDados(g).email" type="email" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Telefone *</label>
                    <InputMask v-model="garantirDados(g).telefone" mask="(99) 99999-9999" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Renda mensal</label>
                    <InputNumber v-model="garantirDados(g).renda_mensal" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" class="w-full" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Estado Civil *</label>
                    <InputText v-model="garantirDados(g).estado_civil" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Regime de Bens</label>
                    <InputText v-model="garantirDados(g).regime_bens" />
                </div>
            </div>

            <!-- Imóvel -->
            <div v-if="g.tipo === 'imovel'" class="grid formgrid p-fluid">
                <div class="field col-12 md:col-4">
                    <label>Tabelionato</label>
                    <InputText v-model="garantirDados(g).tabelionato" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Número da Matrícula</label>
                    <InputText v-model="garantirDados(g).matricula" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Proprietário</label>
                    <InputText v-model="garantirDados(g).proprietario" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Valor de avaliação</label>
                    <InputNumber v-model="garantirDados(g).valor_avaliacao" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" class="w-full" />
                </div>
                <div class="field col-12">
                    <label>Descrição do Imóvel</label>
                    <InputText v-model="garantirDados(g).descricao" />
                </div>
            </div>

            <!-- Veículo -->
            <div v-if="g.tipo === 'veiculo'" class="grid formgrid p-fluid">
                <div class="field col-12 md:col-3">
                    <label>Fabricante *</label>
                    <InputText v-model="garantirDados(g).fabricante" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Modelo *</label>
                    <InputText v-model="garantirDados(g).modelo" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Ano Fabricação *</label>
                    <InputNumber v-model="garantirDados(g).ano_fabricacao" :min="1900" :max="2100" class="w-full" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Ano Modelo *</label>
                    <InputNumber v-model="garantirDados(g).ano_modelo" :min="1900" :max="2100" class="w-full" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Chassi *</label>
                    <InputText v-model="garantirDados(g).chassi" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Renavam *</label>
                    <InputText v-model="garantirDados(g).renavam" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Placa *</label>
                    <InputText v-model="garantirDados(g).placa" />
                </div>
                <div class="field col-12 md:col-2">
                    <label>Cor *</label>
                    <InputText v-model="garantirDados(g).cor" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Valor de avaliação *</label>
                    <InputNumber v-model="garantirDados(g).valor_avaliacao" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" class="w-full" />
                </div>
                <div class="field col-12">
                    <label>Outros Dados</label>
                    <InputText v-model="garantirDados(g).outros_dados" />
                </div>
            </div>

            <!-- Recebíveis -->
            <div v-if="g.tipo === 'recebiveis'" class="grid formgrid p-fluid">
                <div class="field col-12 md:col-3">
                    <label>Tipo</label>
                    <InputText v-model="garantirDados(g).tipo" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Número de identificação</label>
                    <InputText v-model="garantirDados(g).numero_identificacao" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Valor de avaliação</label>
                    <InputNumber v-model="garantirDados(g).valor_avaliacao" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" class="w-full" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Sacado</label>
                    <InputText v-model="garantirDados(g).sacado" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Data de Vencimento</label>
                    <Calendar v-model="garantirDados(g).data_vencimento" dateFormat="dd/mm/yy" />
                </div>
            </div>

            <!-- Outras -->
            <div v-if="g.tipo === 'outras'" class="grid formgrid p-fluid">
                <div class="field col-12 md:col-6">
                    <label>Descrição *</label>
                    <InputText v-model="garantirDados(g).descricao" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Número de série *</label>
                    <InputText v-model="garantirDados(g).numero_serie" />
                </div>
                <div class="field col-12 md:col-3">
                    <label>Estado de conservação *</label>
                    <InputText v-model="garantirDados(g).estado_conservacao" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Localização física *</label>
                    <InputText v-model="garantirDados(g).localizacao_fisica" />
                </div>
                <div class="field col-12 md:col-4">
                    <label>Valor de avaliação *</label>
                    <InputNumber v-model="garantirDados(g).valor_avaliacao" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" class="w-full" />
                </div>
            </div>
        </div>

        <Button label="Adicionar Garantia" icon="pi pi-plus" class="p-button-outlined p-button-sm" @click="adicionarGarantia" />
    </div>
</template>
