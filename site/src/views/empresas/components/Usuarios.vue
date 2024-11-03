<script>
import { ref } from 'vue';
import { PrimeIcons } from 'primevue/api';
// import CityService from '@/service/CityService';
import { useConfirm } from 'primevue/useconfirm';

export default {
	name: 'City',
	props: {
		usuarios: Object,
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
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.MAP"></i> Usuários da Empresa </h5>
		</div>
	</div>
	<Card>
		<template #content>
			<ConfirmDialog></ConfirmDialog>
			<DataTable
				dataKey="id"
				:value="usuarios"
				:paginator="true"
				:rows="10"
				:loading="false"
				:filters="{}"
				paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
				:rowsPerPageOptions="[5, 10, 25]"
				currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} usuário(s)"
				responsiveLayout="scroll"
			>
				<template #empty>
					<div class="text-center text-red-400">
						Ainda não há usuários associados
					</div>
				</template>
				<Column field="id" header="Id" :sortable="true" class="w-1"></Column>
				<Column field="login" header="Login" :sortable="true" class="w-2"></Column>
				<Column field="nome_completo" header="Nome Completo" :sortable="true" class="w-2"></Column>
				<Column field="rg" header="RG" :sortable="true" class="w-2"></Column>
				<Column field="cpf" header="CPF" :sortable="true" class="w-1"></Column>
				<Column field="data_nascimento" header="Dt. Nascimento" :sortable="true" class="w-3"></Column>
				<Column field="status" header="Status" :sortable="true" class="w-3"></Column>
			</DataTable>
		</template>
	</Card>

</template>
