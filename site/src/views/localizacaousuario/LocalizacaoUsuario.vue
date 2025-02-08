<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import LogService from '@/service/LogService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

import { GoogleMap, Marker } from 'vue3-google-map';

export default {
    name: 'localizacaousuarioList',
    setup() {
        return {
            logService: new LogService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    components: {
        GoogleMap,
        Marker
    },
    computed: {
        markerOptions() {
            return {
                position: this.center,
                icon: {
                    url: `/images/marker_50_50.png`,
                    title: `${this.occurrence?.address} ${this.occurrence?.number}`
                },
                label: {
                    text: `Empresa Age Controle`,
                    className: 'py-2 px-2 bg-gray-100 mt-8 border-1 border-gray-300 border-round w-25rem h-auto flex-wrap white-space-normal'
                }
            };
        }
    },
    data() {
        return {
            LogReal: ref([]),
            Log: ref([]),
            showMap: ref(true),
            mapKey: ref(import.meta.env.VITE_APP_GOOGLE_MAPS_KEY),
            zoom: ref(15),
            center: ref({ lat: -16.6699897, lng: -49.2898949 }),
            loading: ref(false),
            filters: ref(null),
            form: ref({}),
            valorRecebido: ref(0),
            valorPago: ref(0),
            markers: [],
            iconMapping: {
                1: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                8: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                3: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
                // Adicione mais mapeamentos conforme necessário
            }
        };
    },
    methods: {
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        getLog() {
            this.loading = true;

            this.logService
                .getAll()
                .then((response) => {
                    this.Log = response.data;
                    this.LogReal = response.data;
                    this.setLastWeekDates();
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
        getClientes() {
            this.loading = true;

            this.logService
                .getAllClientesMaps()
                .then((response) => {
                    this.markers = response.data
                        .filter((item) => {
                            const lat = Number(item.latitude);
                            const lng = Number(item.longitude);
                            return !isNaN(lat) && !isNaN(lng);
                        }) // Filtra itens sem latitude ou longitude válidas
                        .filter((item) => {
                            return item.atrasadas > 0;
                        }) // Filtra itens sem latitude ou longitude válidas
                        .map((item) => {
                            const iconUrl = this.iconMapping[item.company_id] || 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png'; // Ícone padrão se não houver mapeamento

                            return {
                                options: {
                                    position: { lat: Number(item.latitude), lng: Number(item.longitude) },
                                    title: `${item.nome_completo}`,
                                    icon: {
                                        url: iconUrl,
                                        scaledSize: new google.maps.Size(42, 42) // Tamanho do ícone
                                    },
                                    // label: {
                                    //     text: `${item.nome_completo}`,
                                    //     className: 'py-2 px-2 mt-8 w-25rem h-auto flex-wrap white-space-normal'
                                    // }
                                }
                            };
                        });

                    console.log(this.markers);
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
        setLastWeekDates() {
            const today = new Date();
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate());
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate());

            this.form.dt_inicio = lastWeekStart;
            this.form.dt_final = lastWeekEnd;

            this.busca(); // Call the search method to filter data
        },
        busca() {
            if (!this.form.dt_inicio || !this.form.dt_final) {
                this.toast.add({
                    severity: ToastSeverity.WARN,
                    detail: 'Selecione as datas de início e fim',
                    life: 3000
                });
                return;
            }

            const dt_inicio = new Date(this.form.dt_inicio);
            const dt_final = new Date(this.form.dt_final);

            dt_inicio.setHours(0, 0, 0, 999); // Ensure the end date covers the entire day
            dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

            this.Log = this.LogReal.filter((mov) => {
                const dt_mov = new Date(mov.created_at); // Converte a string de data para um objeto Date
                return dt_mov >= dt_inicio && dt_mov <= dt_final;
            });
        },
        editCategory(id) {
            if (undefined === id) this.router.push('/Movimentacaofinanceira/add');
            else this.router.push(`/Movimentacaofinanceira/${id}/edit`);
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.movimentacaofinanceiraService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getLog();
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
        onMapClick(event) {
            // Acesse a latitude e longitude do evento
            const { latLng } = event;
            const lat = latLng.lat();
            const lng = latLng.lng();

            console.log(`Latitude: ${lat}, Longitude: ${lng}`);

            // // Faça o que precisar com as coordenadas (por exemplo, atualizar variáveis de dados)
            // this.occurrence.latitude 	= lat;
            // this.occurrence.longitude 	= lng;
            // this.center = { lat, lng };
            // this.markerOptions.position = { lat, lng };
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
        this.permissionsService.hasPermissionsView('view_movimentacaofinanceira');
        this.getLog();
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
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i>Localização de Usuário</h5>
                </div>
            </div>
            <div class="card">
                <div class="grid">
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="username">Data Inicio</label>
                            <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data de Inicio'" v-model="form.dt_inicio" showIcon :showOnFocus="false" class="" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="username">Data Final</label>
                            <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data Final'" v-model="form.dt_final" showIcon :showOnFocus="false" class="" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <Button label="Pesquisar" @click.prevent="busca()" class="p-button-primary mr-2 mb-2 mt-4" />
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <GoogleMap :api-key="mapKey.trim()" :zoom="zoom" :center="center" @click="onMapClick" style="width: 100%; height: 500px">
                        <Marker :options="markerOptions"></Marker>

                        <Marker v-for="(marker, index) in markers" :key="index" :options="marker.options" :title="marker.title"></Marker>
                    </GoogleMap>
                </div>
            </div>
        </div>
    </div>
</template>
