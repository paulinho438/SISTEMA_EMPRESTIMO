<script>
import { latLng } from 'leaflet';
import { GoogleMap, Marker } from 'vue3-google-map';
import { PrimeIcons } from 'primevue/api';
import { ref } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import ExternoService from '@/service/ExternoService';


export default {
	name: 'occurenceDetail',
	props: {
		occurrence: Object,
	},
	emits: ['close', 'saveNew'],
	components: {
		GoogleMap,
		Marker
	},
	setup() {
		return {
			icons: PrimeIcons,
			contextPath: useLayout(),
			externoService: new ExternoService(),
		};
	},
	data() {
		return {
			showMap: ref(true),
			mapKey: ref(import.meta.env.VITE_APP_GOOGLE_MAPS_KEY),
			zoom: ref(15),
			center: ref(null),
			cep: null,
		};
	},
	methods: {
		onMapClick(event) {
			// Acesse a latitude e longitude do evento
			const { latLng } = event;
			const lat = latLng.lat();
			const lng = latLng.lng();

			console.log(`Latitude: ${lat}, Longitude: ${lng}`);

			// Faça o que precisar com as coordenadas (por exemplo, atualizar variáveis de dados)
			this.occurrence.latitude 	= lat;
			this.occurrence.longitude 	= lng;
			this.center = { lat, lng };
			this.markerOptions.position = { lat, lng };
		},
		addEndereco() {
			this.$emit('changeLoading');
			this.$emit('saveNew', this.occurrence);
		},
		setCenter() {
			if (this.occurrence?.latitude != '') {
				this.center = latLng(this.occurrence.latitude, this.occurrence.longitude);
				this.showMap = true;
			}
		},
		resetCenter(){
			this.center = null;
		},
		zoomUpdate(zoom) {
			this.currentZoom = zoom;
		},
		centerUpdate(center) {
			this.currentCenter = center;
		},
		showLongText() {
			this.showParagraph = !this.showParagraph;
		},
		innerClick() {}
	},
	computed: {
		title() {
			return this.occurrence?.id ? 'Editar Endereço' : 'Criar Endereço';
		},
		markerOptions() {
			return {
				position: this.center,
				icon: {
					url: `/images/marker_50_50.png`,
					title: `${this.occurrence?.address} ${this.occurrence?.number}`
				},
				label: {
					text: `${this.occurrence?.address} ${this.occurrence?.number}`,
					className: 'py-2 px-2 bg-gray-100 mt-8 border-1 border-gray-300 border-round w-25rem h-auto flex-wrap white-space-normal'
				}
			};
		}
	},
	watch: {
		cep(cepVemDoFormulario) {
			if (cepVemDoFormulario == "") {
				return;
			}

			if (cepVemDoFormulario.substr(8) != "_") {
				this.occurrence.cep = cepVemDoFormulario;

				this.externoService.getEndereco(cepVemDoFormulario).then((data) => {
					this.occurrence.address = data.logradouro;
					this.occurrence.neighborhood = data.bairro;
					this.occurrence.city = data.localidade;
					this.occurrence.number = '';
					this.occurrence.complement = '';
				});

				this.externoService.getLatLong(cepVemDoFormulario).then((data) => {
					if (data.status == "OK") {

						this.occurrence.latitude 	= data.results[0].geometry.viewport.northeast.lat;
						this.occurrence.longitude 	= data.results[0].geometry.viewport.northeast.lng;

						this.setCenter()

					}
				});
			}
		},
		occurrence(val) {
			this.resetCenter();

			if (val.id) {
				this.setCenter();
				
			}
		}
	}
};
</script>

<template>
	<Dialog :visible="occurrence.id || occurrence.create" modal :closable="false" :maximizable="true" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" @hid="$emit('close')" class="">
		<template #header>
			<div class="uppercase font-bold text-sm">{{ title }}</div>
		</template>

		<div class="w-full">
                <div class="p-fluid formgrid grid">
					<div class="field col-12 md:col-12">
                        <label for="state">Descrição</label>
						<InputText id="firstname2" :modelValue="occurrence?.description" v-model="occurrence.description" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="state">CEP</label>
                        <InputMask id="inputmask" mask="99999-999" :modelValue="occurrence?.cep" v-model.trim="cep" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-10">
                        <label for="firstname2">Endereço</label>
                        <InputText id="firstname2" :modelValue="occurrence?.address" v-model="occurrence.address" type="text" />
                    </div>
					<div class="field col-12 md:col-2">
                        <label for="state">Número</label>
						<InputText id="firstname2" :modelValue="occurrence?.number" v-model="occurrence.number" type="text" />

                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Bairro</label>
                        <InputText id="firstname2" :modelValue="occurrence?.neighborhood" v-model="occurrence.neighborhood" type="text" />
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Cidade</label>
                        <InputText id="firstname2" :modelValue="occurrence?.city" v-model="occurrence.city" type="text" />
                    </div>
					<div class="field col-12 md:col-12">
                        <label for="firstname2">Complemento</label>
                        <InputText id="firstname2" :modelValue="occurrence?.complement" v-model="occurrence.complement" type="text" />
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Latitude</label>
                        <InputText id="firstname2" :disabled="true" :modelValue="occurrence?.latitude" v-model="occurrence.latitude" type="text" />
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Longitude</label>
                        <InputText id="firstname2" :disabled="true" :modelValue="occurrence?.longitude" v-model="occurrence.longitude" type="text" />
                    </div>
					
                </div>
            
        	</div>

		<div class="w-full text-sm">
			<div class="grid">
				<div class="col-4">
					<div class="font-bold uppercas mb-2">Localização:</div>
				</div>
			</div>
		</div>
		<div class="w-12 flex border-1 border-gray-400" v-if="showMap">
			<GoogleMap :api-key="mapKey.trim()" :zoom="zoom" :center="center" @click="onMapClick" style="width: 100%; height: 500px">
				<Marker :options="markerOptions"></Marker>
			</GoogleMap>
		</div>
		<template #footer>
			<div class="w-full text-sm">
				<div class="grid flex flex-row-reverse mt-2">
					<div v-if="occurrence.create">
						<Button label="Salvar" class="p-button p-button-info p-button-sm ml-3" :icon="icons.SAVE" type="button" @click="addEndereco" />
					</div>
					<div class="">
						<Button @click="$emit('close')" label="Fechar" :icon="icons.BAN" class="p-button-danger p-button-sm" />
					</div>
				</div>
			</div>
		</template>
	</Dialog>

</template>
