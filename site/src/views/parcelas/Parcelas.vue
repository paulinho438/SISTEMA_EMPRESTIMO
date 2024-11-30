<script>
import { ref } from 'vue';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import { useConfirm } from 'primevue/useconfirm';
import AddressAlter from './AddressAlter.vue';
import { useToast } from 'primevue/usetoast';
import moment from 'moment';

export default {
	name: 'City',
	props: {
		address: Object,
		oldCicom: Object,
		loading: Boolean,
		aprovacao: Boolean
	},
	emits: ['updateCicom', 'addCityBeforeSave', 'changeLoading'],
	setup() {
		return {
			emprestimoService: new EmprestimoService(),
			toast: useToast(),
			icons: PrimeIcons,
			city: ref(null),
			cities: ref([]),
			citiesCicom: ref([]),
			visibleRight: ref(false),
			emprestimo: ref({}),
			confirm: useConfirm(),
			occurrence: ref({}),
			newoccurrence: ref({}),
			displayBaixaManual: ref({
				enabled 	: false,
				dt_baixa	: null
			}),
			displayCancelarBaixaConfirmation: ref({
				enabled 	: false,
				ref			: null,
			}),
			displayConfirmation: ref({
				enabled 	: false,
				ref			: null,
			}),
		};
	},
	components: {
		AddressAlter,
	},
	methods: {
		
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
		formatarDataParaString(data) {
			const dia = String(data.getDate()).padStart(2, '0');
			const mes = String(data.getMonth() + 1).padStart(2, '0');
			const ano = data.getFullYear();
			return `${dia}/${mes}/${ano}`;
		},
		dataBaixa() {
			this.displayConfirmation.enabled 	= false;
			this.displayBaixaManual.dt_baixa	= this.formatarDataParaString(new Date());
			this.displayBaixaManual.enabled 	= true;	
		},
		yesCancelarBaixa() {
			this.emprestimoService.cancelarBaixaParcela(this.displayCancelarBaixaConfirmation.ref)
			.then((e) => {
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
			})
			.catch((error) => {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error?.data?.message,
					life: 3000
				});
			});

			this.$emit('updateCicom');

			this.displayCancelarBaixaConfirmation.ref 		= null;
			this.displayCancelarBaixaConfirmation.enabled 	= false;
		},
		yesConfirmation() {

			if(!this.displayBaixaManual.dt_baixa) {
				alert('Informe a data da baixa.');
				return false;
			}

			if(!this.displayBaixaManual.valor) {
				alert('Informe o valor.');
				return false;
			}

			this.emprestimoService.baixaParcela(this.displayConfirmation.ref, moment(this.displayBaixaManual.dt_baixa, 'DD/MM/YYYY').format('YYYY-MM-DD'), this.displayBaixaManual.valor)
			.then((e) => {
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
			})
			.catch((error) => {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error?.data?.message,
					life: 3000
				});
			});

			this.$emit('updateCicom');

			this.displayConfirmation.ref 		= null;
			this.displayConfirmation.enabled 	= false;
			this.displayBaixaManual.enabled		= false;	
		},
		noConfirmation() {

			this.displayConfirmation.ref = null;
			this.displayConfirmation.enabled = false;
			this.displayCancelarBaixaConfirmation.enabled = false;
		},
		pix(url) {
			window.open(url, '_blank');
		},
		cancelarBaixaManual(id) {
			this.displayCancelarBaixaConfirmation.ref = id;
			this.displayCancelarBaixaConfirmation.enabled = true;
		},
		baixaManual(id) {
			this.displayConfirmation.ref = id;
			this.displayConfirmation.enabled = true;
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
		toggleMenu(id){
			console.log(id)
			const menuRef = this.$refs[`menu_${id}`];
			menuRef.toggle(event);
		},
		getOverlayMenuItems(data) {
			const contextMenuItems = [];

			if (data.chave_pix?.length > 0 && !data.dt_baixa) {
				contextMenuItems.push({
					label: 'Visualizar PIX',
					icon: 'pi pi-qrcode',
					command: () => this.pix(data.chave_pix)
				});
			}

			if (data.id && !data.dt_baixa && !this.aprovacao) {
				contextMenuItems.push({
					label: 'Baixa Manual',
					icon: 'pi pi-thumbs-up',
					command: () => this.baixaManual(data.id)
				});
			}

			if (data.id && data.valor_recebido || data.id && data.valor_recebido_pix) {
				contextMenuItems.push({
					label: 'Cancelar Baixa',
					icon: 'pi pi-history',
					command: () => this.cancelarBaixaManual(data.id)
				});
			}

			return contextMenuItems;
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
	}
};
</script>

<template>
	<div class="grid flex flex-wrap mb-3 mt-3 px-4 pt-2">
		<div class="col-8 px-0 py-0">
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.MAP"></i> Parcelas</h5>
		</div>
	</div>
	<Card>
		<template #content>
			<Dialog header="Efetuando a Baixa da Parcela" v-model:visible="displayBaixaManual.enabled" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
				<div class="p-fluid formgrid grid p-3">
					<label class="mb-3" for="zip"><b>Data da Baixa</b></label>
					<InputMask id="inputmask" mask="99/99/9999" v-model="displayBaixaManual.dt_baixa"></InputMask>
				</div>
				<div class="p-fluid formgrid grid p-3">
					<label class="mb-3" for="zip"><b>Valor</b></label>
					<InputNumber id="inputnumber" :modelValue="displayBaixaManual.valor" v-model="displayBaixaManual.valor"
								:mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2"
								class="w-full p-inputtext-sm"></InputNumber>
				</div>
				<template #footer>
					<Button label="Realizar Baixa" @click="yesConfirmation" icon="pi pi-check" class="p-button-outlined" />
				</template>
			</Dialog>
			<Dialog header="Confirmação" v-model:visible="displayCancelarBaixaConfirmation.enabled" :style="{ width: '350px' }" :modal="true">
				<div class="flex align-items-center justify-content-center">
					<i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
					<span>Tem certeza que deseja cancelar a <b>Baixa Manual</b> dessa parcela?</span>
				</div>
				<template #footer>
					<Button label="Não" icon="pi pi-times" @click="noConfirmation" class="p-button-text" />
					<Button label="Sim" icon="pi pi-check" @click="yesCancelarBaixa" class="p-button-text" autofocus />
				</template>
			</Dialog>
			<Dialog header="Confirmação" v-model:visible="displayConfirmation.enabled" :style="{ width: '350px' }" :modal="true">
				<div class="flex align-items-center justify-content-center">
					<i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
					<span>Tem certeza que deseja efetuar a <b>Baixa Manual</b> dessa parcela?</span>
				</div>
				<template #footer>
					<Button label="Não" icon="pi pi-times" @click="noConfirmation" class="p-button-text" />
					<Button label="Sim" icon="pi pi-check" @click="dataBaixa" class="p-button-text" autofocus />
				</template>
			</Dialog>
			<ConfirmDialog></ConfirmDialog>
			<Sidebar v-model:visible="visibleRight" :baseZIndex="1000" position="right">
                <h1 style="font-weight: normal">Informações</h1>
				<div class="col-12">
					<div class="p-fluid formgrid grid">
						<div class="field col-12 md:col-12">
							<label for="zip">Valor do Emprestimo</label>
							<InputNumber id="inputnumber" :modelValue="emprestimo?.valor" v-model="emprestimo.valor" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }"></InputNumber>
						</div>
					</div>
				</div>
            </Sidebar>
			<DataTable
				dataKey="id"
				:value="address"
				:paginator="true"
				:rows="10"
				:loading="false"
				:filters="{}"
				paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
				:rowsPerPageOptions="[5, 10, 25]"
				currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} parcela(s)"
				responsiveLayout="scroll"
			>
				<template #empty>
					<div class="text-center text-red-400">
						Ainda não há parcelas associadas ao empréstimo
					</div>
				</template>
				<Column field="parcela" header="Parcela" :sortable="false" class="w-1"></Column>
				<Column field="saldo" header="Saldo Pendente" :sortable="false" class="w-1">
					<template #body="slotProps">
						<span class="p-column-title">Saldo Pendente</span>
						{{ slotProps.data.saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}

					</template>
				</Column>
				<Column field="total_pago_parcela" header="Valor Pago" :sortable="false" class="w-1">
					<template #body="slotProps">
						<span class="p-column-title">Valor Pago</span>
						{{ slotProps.data.total_pago_parcela.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}

					</template>
				</Column>
				<Column field="valor_recebido" header="Valor Recebido" :sortable="false" class="w-1">
					<template #body="slotProps">
						<span class="p-column-title">Valor Recebido Consultor</span>
						{{ slotProps.data.valor_recebido?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}

					</template>
				</Column>
				<Column field="valor_recebido_pix" header="Valor Recebido Pix" :sortable="false" class="w-1">
					<template #body="slotProps">
						<span class="p-column-title">Valor Recebido Pix</span>
						{{ slotProps.data.valor_recebido_pix?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}

					</template>
				</Column>
				<Column field="venc" header="Vencimento" :sortable="false" class="w-1"></Column>
				<Column field="venc_real" header="Venc. Real" :sortable="false" class="w-1"></Column>
				<Column field="dt_baixa" header="Dt. Baixa" :sortable="false" class="w-1"></Column>
				<Column field="edit" header="Opções" :sortable="false" class="w-1">
					<template #body="slotProps">
						<Menu v-if="getOverlayMenuItems(slotProps.data).length > 0" :ref="`menu_${slotProps.data.parcela}`" :model="getOverlayMenuItems(slotProps.data)" :popup="true" />
						<Button v-if="getOverlayMenuItems(slotProps.data).length > 0" type="button" label="Opções" icon="pi pi-angle-down" @click="toggleMenu(slotProps.data.parcela)" style="width: auto" />
					</template>
				</Column>
			</DataTable>
		</template>
	</Card>
	<AddressAlter :occurrence="occurrence" @close="closeDetail" @saveNew="saveNewAddress" />	

</template>
