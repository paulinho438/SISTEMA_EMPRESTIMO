<script>
import { ref } from 'vue';
import { PrimeIcons } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import { useConfirm } from 'primevue/useconfirm';
import AddressAlter from '../parcelas/AddressAlter.vue';

export default {
    name: 'City',
    props: {
        address: Object,
        oldCicom: Object,
        loading: Boolean,
        emp: Object
    },
    emits: ['updateCicom', 'addCityBeforeSave', 'changeLoading'],
    setup() {
        return {
            icons: PrimeIcons,
            city: ref(null),
            cities: ref([]),
            citiesCicom: ref([]),
            visibleRight: ref(false),
            emprestimo: ref({}),
            confirm: useConfirm(),
            occurrence: ref({}),
            enviado: ref(false),
            newoccurrence: ref({}),
            dropdownValues: ref([
                { name: 'Segunda a Sexta', code: '1' },
                { name: 'Segunda a Sábado', code: '2' },
                { name: 'Segunda a Domingo', code: '3' }
            ]),
            dropdownValue: ref(null),
            emprestimoService: new EmprestimoService(),
            feriados: ref([])
        };
    },
    components: {
        AddressAlter
    },
    methods: {
        handleLucro() {
            if (this.emprestimo.valor && this.emprestimo.mensalidade && this.emprestimo.parcelas) {
                this.emprestimo.valortotal = this.emprestimo.lucro + this.emprestimo.valor;
                this.emprestimo.mensalidade = this.emprestimo.valortotal / this.emprestimo.parcelas;

                const porcentagem = ((this.emprestimo.valortotal - this.emprestimo.valor) / this.emprestimo.valor) * 100;
                this.emprestimo.juros = porcentagem.toFixed(2);
            }
        },
        handleParcela() {
            if (this.emprestimo.valor && this.emprestimo.mensalidade) {
                this.emprestimo.valortotal = this.emprestimo.parcelas * this.emprestimo.mensalidade;
                this.emprestimo.lucro = this.emprestimo.valortotal - this.emprestimo.valor;

                const porcentagem = ((this.emprestimo.valortotal - this.emprestimo.valor) / this.emprestimo.valor) * 100;
                this.emprestimo.juros = porcentagem.toFixed(2);
            }
        },
        handleValormensalidade() {
            if (this.emprestimo.valor && this.emprestimo.parcelas) {
                this.emprestimo.valortotal = this.emprestimo.parcelas * this.emprestimo.mensalidade;
                this.emprestimo.lucro = this.emprestimo.valortotal - this.emprestimo.valor;

                const porcentagem = ((this.emprestimo.parcelas * this.emprestimo.mensalidade - this.emprestimo.valor) / this.emprestimo.valor) * 100;
                this.emprestimo.juros = porcentagem.toFixed(2);
            }
        },
        formatarDataParaString(data) {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            return `${dia}/${mes}/${ano}`;
        },
        gerarParcelas() {
            let parcela = {};
            // Defina a data inicial
            let dataLanc = new Date(this.address.dt_lancamento);

            let dataInicial = new Date(this.address.dt_lancamento);

            // Array para armazenar as parcelas
            const parcelas = [];

            // Loop para gerar 25 parcelas
            for (let i = 0; i < this.emprestimo.parcelas; i++) {
                parcela = {};
                parcela.parcela = 1 + i;
                parcela.parcela = parcela.parcela.toString().padStart(3, '0');
                parcela.valor = this.emprestimo.mensalidade;
                parcela.saldo = this.emprestimo.mensalidade;
                parcela.total_pago_parcela = 0;
                parcela.dt_lancamento = this.formatarDataParaString(new Date(dataLanc));

                dataInicial.setDate(dataInicial.getDate() + this.emprestimo.intervalo);

                // Verifica se o dia da semana é sábado (6) ou domingo (0)
                // Se for, adiciona mais um dia até encontrar um dia útil (segunda a sexta)
                if (this.dropdownValue?.code == '1') {
                    while (dataInicial.getDay() === 0 || dataInicial.getDay() === 6) {
                        dataInicial.setDate(dataInicial.getDate() + 1);
                    }
                } else if (this.dropdownValue?.code == '2') {
                    while (dataInicial.getDay() === 0) {
                        dataInicial.setDate(dataInicial.getDate() + 1);
                    }
                }

                parcela.venc = this.formatarDataParaString(new Date(dataInicial));

                if (this.isFeriado(dataInicial)) {
                    dataInicial.setDate(dataInicial.getDate() + 1);
                    if (this.dropdownValue?.code == '1') {
                        while (dataInicial.getDay() === 0 || dataInicial.getDay() === 6) {
                            dataInicial.setDate(dataInicial.getDate() + 1);
                        }
                    } else if (this.dropdownValue?.code == '2') {
                        while (dataInicial.getDay() === 0) {
                            dataInicial.setDate(dataInicial.getDate() + 1);
                        }
                    }
					if (this.isFeriado(dataInicial)) {
						dataInicial.setDate(dataInicial.getDate() + 1);
					}
                }

                parcela.venc_real = this.formatarDataParaString(new Date(dataInicial));

                this.$emit('saveParcela', parcela);

                parcelas.push(this.formatarDataParaString(new Date(dataInicial)));

                this.enviado = true;
            }

            this.$emit('saveInfoEmprestimo', this.emprestimo);

            this.visibleRight = false;
        },
        isFeriado(data) {
            const dataFormatada = this.formatarDataParaString(data);

            return this.feriados.some((feriado) => feriado.data_feriado === dataFormatada);
        },
        getFeriados() {
            this.feriados = ref(null);
            this.emprestimoService
                .feriados()
                .then((response) => {
                    this.feriados = response.data?.data;
                    console.log('feriados', this.feriados);
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(e),
                        life: 3000
                    });
                })
                .finally(() => {});
        },
        async searchCity(event) {
            // try {
            // 	let response = await this.cityService.search(event.query, this.cicom?.cities);
            // 	this.cities = response.data.data;
            // } catch (e) {
            // 	console.log(e);
            // }
        },
        closeDetail() {
            this.occurrence = {};
        },
        saveNewAddress(address) {
            this.address.push(address);
            this.occurrence = {};
        },
        addCity() {
            // this.$emit('changeLoading');
            // const cityUpdate = {
            // 	...this.city,
            // 	cicom_id: this.cicom?.id
            // };
            // if (cityUpdate.cicom_id && this.oldCicom?.enabled) {
            // 	try {
            // 		this.cityService
            // 			.save(cityUpdate)
            // 			.then((response) => {})
            // 			.finally(() => {
            // 				this.city = null;
            // 				this.cicom.cities.push(cityUpdate);
            // 				this.$emit('changeLoading');
            // 			});
            // 	} catch (e) {
            // 		console.log(e);
            // 	}
            // } else {
            // 	this.$emit('addCityBeforeSave', cityUpdate);
            // 	this.city = null;
            // }
        },
        editCity(city) {
            try {
                // this.cityService.save(city);
                this.$emit('updateCicom');

                this.occurrence = city;

                // let index = this.address.indexOf(city);
                // this.address.splice(index, 1);

                this.$emit('changeLoading');
            } catch (e) {
                console.log(e);
            }
        },
        addCity(city) {
            try {
                // this.cityService.save(city);
                this.$emit('updateCicom');

                this.occurrence.create = true;

                // let index = this.address.indexOf(city);
                // this.address.splice(index, 1);

                this.$emit('changeLoading');
            } catch (e) {
                console.log(e);
            }
        },
        confirm(event) {
            this.confirm.require({
                target: event.currentTarget,
                group: 'templating',
                message: 'Please confirm to proceed moving forward.',
                icon: 'pi pi-qrcode',
                acceptIcon: 'pi pi-check',
                rejectIcon: 'pi pi-times',
                rejectClass: 'p-button-sm',
                acceptClass: 'p-button-outlined p-button-sm',
                accept: () => {
                    toast.add({ severity: 'info', summary: 'Confirmed', detail: 'You have accepted', life: 3000 });
                },
                reject: () => {
                    toast.add({ severity: 'error', summary: 'Rejected', detail: 'You have rejected', life: 3000 });
                }
            });
        },
        removeCity(city) {
            this.confirm.require({
                message: `Deseja remover o Endereço ?`,
                header: 'Remover Endereço',
                icon: this.icons.EXCLAMATION_TRIANGLE,
                acceptIcon: this.icons.CHECK,
                acceptLabel: 'Sim',
                acceptClass: 'p-button-info',
                rejectIcon: this.icons.BAN,
                rejectLabel: 'Não',
                accept: () => {
                    try {
                        city.cicom_id = null;
                        // this.cityService.save(city);
                        this.$emit('updateCicom');

                        let index = this.address.indexOf(city);
                        this.address.splice(index, 1);

                        this.$emit('changeLoading');
                    } catch (e) {
                        console.log(e);
                    }
                }
            });
        }
    },
    mounted() {
        this.getFeriados();
    }
};
</script>

<template>
    <ConfirmPopup group="templating">
        <template #message="slotProps">
            <div class="flex flex-column align-items-center w-full gap-3 border-bottom-1 surface-border p-3 mb-3">
                <i :class="slotProps.message.icon" class="text-6xl text-danger-500"></i>
                <p>{{ slotProps.message.message }}</p>
                <p>{{ slotProps.message.message }}</p>
                <p>{{ slotProps.message.message }}</p>
            </div>
        </template>
    </ConfirmPopup>
    <div class="grid flex flex-wrap mb-3 px-4 pt-2">
        <div class="col-12 px-0 py-0 text-right">
            <Button v-if="!this.address?.parcelas && !this.enviado" label="Gerar Emprestimo" class="p-button-sm p-button-info" :icon="icons.PLUS" @click="visibleRight = true" />
        </div>
    </div>
    <Sidebar v-model:visible="visibleRight" :baseZIndex="1000" position="right">
        <h1 style="font-weight: normal">Informações</h1>
        <div class="col-12">
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="zip">Valor do Emprestimo</label>
                    <InputNumber id="inputnumber" :modelValue="emprestimo?.valor" v-model="emprestimo.valor" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Parcelas</label>
                    <InputNumber :modelValue="emprestimo?.parcelas" v-model="emprestimo.parcelas" inputId="minmax-buttons" mode="decimal" showButtons :min="0" :max="100" @blur="handleParcela" />
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="zip">Valor da Mensalidade</label>
                    <InputNumber id="inputnumber" v-model="emprestimo.mensalidade" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" @blur="handleValormensalidade"></InputNumber>
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Juros</label>
                    <InputNumber disabled="true" prefix="%" :modelValue="emprestimo?.juros" v-model="emprestimo.juros" inputId="minmaxfraction" :minFractionDigits="2" :maxFractionDigits="5" locale="en-US" />
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Lucro</label>
                    <InputNumber id="inputnumber" :modelValue="emprestimo?.lucro" v-model="emprestimo.lucro" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" @blur="handleLucro"></InputNumber>
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Valor Total</label>
                    <InputNumber disabled="true" id="inputnumber" :modelValue="emprestimo?.valortotal" v-model="emprestimo.valortotal" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Intervalo entre as parcelas</label>
                    <InputNumber :modelValue="emprestimo?.intervalo" v-model="emprestimo.intervalo" placeholder="Dias" inputId="minmax-buttons" mode="decimal" showButtons :min="0" :max="100" @blur="handleParcela" />
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <label for="firstname2">Opção de cobrança</label>
                    <Dropdown v-model="dropdownValue" :options="dropdownValues" optionLabel="name" placeholder="Selecione" />
                </div>
            </div>
            <div class="p-fluid formgrid grid">
                <div class="field col-12 md:col-12">
                    <Button ref="popup" @click="gerarParcelas" icon="pi pi-check" label="Confirmar" class="mr-2"></Button>
                </div>
            </div>
        </div>
    </Sidebar>
    <AddressAlter :occurrence="occurrence" @close="closeDetail" @saveNew="saveNewAddress" />
</template>
