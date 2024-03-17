<script>
import { ref } from 'vue';
import { PrimeIcons } from 'primevue/api';
// import CityService from '@/service/CityService';
import { useConfirm } from 'primevue/useconfirm';
import AddressAlter from './AddressAlter.vue';

export default {
	name: 'City',
	props: {
		address: Object,
		oldCicom: Object,
		loading: Boolean
	},
	emits: ['updateCicom', 'addCityBeforeSave', 'changeLoading'],
	setup() {
		return {
			icons: PrimeIcons,
			city: ref(null),
			cities: ref([]),
			citiesCicom: ref([]),
			// cityService: new CityService(),
			confirm: useConfirm(),
			occurrence: ref({}),
			newoccurrence: ref({}),
		};
	},
	components: {
		AddressAlter,
	},
	methods: {
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
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.MAP"></i> Endereços</h5>
		</div>
	</div>
	<Card>
		<template #content>
			<ConfirmDialog></ConfirmDialog>
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-12 px-0 py-0 text-right">
					<Button label="Adicionar Endereço" class="p-button-sm p-button-info" :icon="icons.PLUS" @click="addCity" />
				</div>
			</div>
			
			<DataTable
				dataKey="id"
				:value="address"
				:paginator="true"
				:rows="10"
				:loading="false"
				:filters="{}"
				paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
				:rowsPerPageOptions="[5, 10, 25]"
				currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} endereço(s)"
				responsiveLayout="scroll"
			>
				<template #empty>
					<div class="text-center text-red-400">
						Ainda não há endereços associados
					</div>
				</template>
				<Column field="id" header="Id" :sortable="true" class="w-1"></Column>
				<Column field="description" header="Descrição" :sortable="true" class="w-2"></Column>
				<Column field="address" header="Endereço" :sortable="true" class="w-2"></Column>
				<Column field="number" header="Número" :sortable="true" class="w-1"></Column>
				<Column field="complement" header="Complemento" :sortable="true" class="w-3"></Column>
				<Column field="neighborhood" header="Bairro" :sortable="true" class="w-3"></Column>
				<Column field="city" header="Cidade" :sortable="true" class="w-3"></Column>
				<Column field="edit" header="Editar" :sortable="false" class="w-1">
					<template #body="slotProps">
						<Button class="p-button p-button-icon-only p-button-text p-button-secondary px-0 py-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCity(slotProps.data)" />
					</template>
				</Column>
				<Column field="edit" header="Remover" :sortable="false" class="w-1">
					<template #body="slotProps">
						<Button class="p-button p-button-icon-only p-button-text p-button-danger px-0 py-0" type="button" :icon="icons.TRASH" v-tooltip.top="'Remover'" @click.prevent="removeCity(slotProps.data)" />
					</template>
				</Column>
			</DataTable>
		</template>
	</Card>
	<AddressAlter :occurrence="occurrence" @close="closeDetail" @saveNew="saveNewAddress" />	

</template>
