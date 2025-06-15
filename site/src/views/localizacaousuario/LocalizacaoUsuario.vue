<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { latLng } from 'leaflet';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import LogService from '@/service/LogService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';
import ExternoService from '@/service/ExternoService';

import { GoogleMap, Marker, Polyline } from 'vue3-google-map';

export default {
    name: 'localizacaousuarioList',
    setup() {
        return {
            logService: new LogService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast(),
            externoService: new ExternoService()
        };
    },
    components: {
        GoogleMap,
        Marker,
        Polyline
    },
    computed: {
        markerOptions() {
            return {
                position: this.center,
                icon: {
                    url: `/images/marker_50_50.png`,
                    title: this.textoReferencia
                }
            };
        }
    },
    data() {
        return {
            LogReal: ref([]),
            flightPaths: [],
            Log: ref([]),
            showMap: ref(true),
            mapKey: ref(import.meta.env.VITE_APP_GOOGLE_MAPS_KEY),
            zoom: ref(15),
            center: ref({ lat: -16.6699897, lng: -49.2898949 }),
            loading: ref(false),
            filters: ref(null),
            occurrence: ref({}),
            textoReferencia: `Empresa Age Controle`,
            passos: 0,
            form: ref({
                dt_inicio: null,
                dt_final: null,
                atrasadas: null
            }),
            valorRecebido: ref(0),
            valorPago: ref(0),
            markers: [],
            consultoresMarkers: [],
            consultores: [],
            localizacaoClientesMarkers: [],
            localizacaoClientes: [],
            cobraramanhaMarkers: [],
            cobraramanha: [],
            rotaconsultor: [],
            directions: null,
            directionsRenderer: null,
            flightPlanCoordinates: [
                { lat: 37.772, lng: -122.214 },
                { lat: 21.291, lng: -157.821 },
                { lat: -18.142, lng: 178.431 },
                { lat: -27.467, lng: 153.027 }
            ],
            flightPath: {
                path: [],
                geodesic: true,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 2
            },
            route: [
                { lat: -23.55052, lng: -46.633308 }, // Ponto A
                { lat: -23.55152, lng: -46.634308 }, // Ponto B
                { lat: -23.55252, lng: -46.635308 } // Ponto C
            ],
            map: null, // Referência ao mapa
            polyline: null, // Referência à linha
            atrasadasOptions: [
                { label: 'Todos', value: 'todos' },
                { label: 'Azul', value: 'azul' },
                { label: 'Verde', value: 'verde' },
                { label: 'Amarelo', value: 'amarelo' },
                { label: 'Vermelho', value: 'vermelho' },
                { label: 'Todos os atrasados', value: 'todos_atrasados' }
            ],
            cep: null,
            iconMapping: {
                0: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
                1: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                2: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                3: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                4: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                5: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                6: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                7: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                8: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                9: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
                10: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                11: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                12: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                13: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                14: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                15: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                16: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                17: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                18: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                19: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                20: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                21: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                // Adicione mais mapeamentos conforme necessário
            },
            intervalId: null
        };
    },
    methods: {
        setCenter() {
            if (this.occurrence?.latitude != '') {
                this.center = latLng(this.occurrence.latitude, this.occurrence.longitude);
                this.textoReferencia = 'Ponto de Pesquisa.';
                this.stopFetchingConsultores();
                this.markers = [];
            }
        },
        startFetchingConsultores() {
            this.intervalId = setInterval(() => {
                this.getConsultores();
            }, 10000); // 5000 ms = 5 segundos
        },
        stopFetchingConsultores() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },
        drawPolyline() {
            if (this.map && this.route.length > 1) {
                this.polyline = new google.maps.Polyline({
                    path: this.route,
                    geodesic: true,
                    strokeColor: '#FF0000',
                    strokeOpacity: 1.0,
                    strokeWeight: 4
                });

                this.polyline.setMap(this.map);
            }
        },
        onMapLoad(map) {
            this.map = map;
            this.drawPolyline();
        },
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        getIconUrl(atrasadas) {
            if (atrasadas === 0) {
                return 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
            } else if (atrasadas > 0 && atrasadas <= 2) {
                return 'http://maps.google.com/mapfiles/ms/icons/green-dot.png';
            } else if (atrasadas >= 3 && atrasadas <= 9) {
                return 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png';
            } else {
                return 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
            }
        },
        getLog() {
            this.loading = true;

            // this.logService
            //     .getAll()
            //     .then((response) => {
            //         this.Log = response.data;
            //         this.LogReal = response.data;
            //         this.setLastWeekDates();
            //     })
            //     .catch((error) => {
            //         this.toast.add({
            //             severity: ToastSeverity.ERROR,
            //             detail: error.message,
            //             life: 3000
            //         });
            //     })
            //     .finally(() => {
            //         this.loading = false;
            //     });
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
                            if (this.form.atrasadas !== 'todos') {
                                if (this.form.atrasadas === 'azul') {
                                    return item.atrasadas === 0;
                                } else if (this.form.atrasadas === 'verde') {
                                    return item.atrasadas > 0 && item.atrasadas <= 2;
                                } else if (this.form.atrasadas === 'amarelo') {
                                    return item.atrasadas >= 3 && item.atrasadas <= 9;
                                } else if (this.form.atrasadas === 'vermelho') {
                                    return item.atrasadas >= 10;
                                } else if (this.form.atrasadas === 'todos_atrasados') {
                                    return item.atrasadas > 0;
                                }
                            }
                            return true;
                        })
                        .map((item) => {
                            const iconUrl = this.getIconUrl(item.atrasadas);

                            return {
                                options: {
                                    position: { lat: Number(item.latitude), lng: Number(item.longitude) },
                                    title: `${item.nome_completo} [${item.nome_empresa}]`,
                                    icon: {
                                        url: iconUrl,
                                        scaledSize: new google.maps.Size(42, 42) // Tamanho do ícone
                                    }
                                }
                            };
                        });

                    this.passos++;
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
        getConsultores() {
            this.loading = true;

            this.logService
                .getAllConsultorMaps()
                .then((response) => {
                    this.consultores = response.data.filter((item) => item.latitude && item.longitude);

                    this.consultoresMarkers = response.data.map((item) => {
                        const iconUrl = this.getIconUrl(0);

                        return {
                            options: {
                                position: { lat: Number(item.latitude), lng: Number(item.longitude) },
                                title: `${item.user_name}`,
                                icon: {
                                    url: `/images/motoboy.png`,
                                    scaledSize: new google.maps.Size(42, 42) // Tamanho do ícone
                                }
                            }
                        };
                    });
                    this.passos++;
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

            this.logService
                .getLocalizacaoClientes()
                .then((response) => {
                    this.localizacaoClientes = response.data.filter((item) => item.latitude && item.longitude);

                    this.localizacaoClientesMarkers = response.data.map((item) => {
                        return {
                            options: {
                                position: { lat: Number(item.latitude), lng: Number(item.longitude) },
                                title: `${item.user_name} Última atualização : ${item.created_at}`,
                                icon: {
                                    url: `/images/cliente.png`,
                                    scaledSize: new google.maps.Size(42, 42) // Tamanho do ícone
                                }
                            }
                        };
                    });
                    this.passos++;
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
        getPontosCobrarAmanha() {
            this.loading = true;
            let data = {
                data: this.form.dt_inicio
            };
            this.logService
                .getAllCobrarAmanhaMaps(data)
                .then((response) => {
                    this.cobraramanha = response.data.filter((item) => item.latitude && item.longitude);

                    this.cobraramanhaMarkers = response.data.map((item) => {
                        const iconUrl = this.getIconUrl(0);

                        return {
                            options: {
                                position: { lat: Number(item.latitude), lng: Number(item.longitude) },
                                title: `${item.descricao}`,
                                icon: {
                                    url: `/images/icone_alert.png`,
                                    scaledSize: new google.maps.Size(42, 42) // Tamanho do ícone
                                }
                            }
                        };
                    });
                    this.passos++;
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
        getDistanceInMeters(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Raio da Terra em metros
            const toRad = (x) => (x * Math.PI) / 180;

            const φ1 = toRad(lat1);
            const φ2 = toRad(lat2);
            const Δφ = toRad(lat2 - lat1);
            const Δλ = toRad(lon2 - lon1);

            const a = Math.sin(Δφ / 2) ** 2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        },
        getRotaConsultor() {
            this.loading = true;

            let data = {
                consultor: this.form.consultor,
                data: this.form.dt_inicio
            };

            this.logService
                .getRotaConsultor(data)
                .then((response) => {
                    this.rotaconsultor = response.data;

                    let locations = this.rotaconsultor.filter((location) => location.latitude && location.longitude);

                    let routes = [];
                    let currentRoute = [];

                    const getDistanceInMeters = (lat1, lon1, lat2, lon2) => {
                        const R = 6371e3; // Raio da Terra em metros
                        const toRad = (x) => (x * Math.PI) / 180;

                        const φ1 = toRad(lat1);
                        const φ2 = toRad(lat2);
                        const Δφ = toRad(lat2 - lat1);
                        const Δλ = toRad(lon2 - lon1);

                        const a = Math.sin(Δφ / 2) ** 2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                        return R * c;
                    };

                    for (let i = 0; i < locations.length; i++) {
                        const current = locations[i];
                        const lat = Number(current.latitude);
                        const lng = Number(current.longitude);

                        if (currentRoute.length === 0) {
                            currentRoute.push({ lat, lng });
                        } else {
                            const prev = currentRoute[currentRoute.length - 1];
                            const distance = getDistanceInMeters(prev.lat, prev.lng, lat, lng);

                            if (distance > 500) {
                                routes.push(currentRoute);
                                currentRoute = [{ lat, lng }];
                            } else {
                                currentRoute.push({ lat, lng });
                            }
                        }
                    }

                    if (currentRoute.length > 0) {
                        routes.push(currentRoute);
                    }

                    // flightPaths agora será um array de paths
                    this.flightPaths = routes.map((route) => ({
                        path: route,
                        geodesic: true,
                        strokeColor: '#FF0000',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                    }));
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
        calculateRoute() {
            const directionsService = new google.maps.DirectionsService();
            const waypoints = this.rotaconsultor
                .filter((location) => location.latitude && location.longitude)
                .map((location) => ({
                    location: new google.maps.LatLng(location.latitude, location.longitude),
                    stopover: true
                }));

            if (waypoints.length < 2) {
                this.toast.add({
                    severity: ToastSeverity.WARN,
                    detail: 'É necessário pelo menos dois pontos para calcular a rota',
                    life: 3000
                });
                return;
            }

            const origin = waypoints.shift().location;
            const destination = waypoints.pop().location;

            directionsService.route(
                {
                    origin: origin,
                    destination: destination,
                    waypoints: waypoints,
                    travelMode: google.maps.TravelMode.DRIVING
                },
                (result, status) => {
                    if (status === google.maps.DirectionsStatus.OK) {
                        if (!this.directionsRenderer) {
                            this.directionsRenderer = new google.maps.DirectionsRenderer();
                            this.directionsRenderer.setMap(this.$refs.mapRef.$mapObject);
                        }
                        this.directionsRenderer.setDirections(result);
                    } else {
                        this.toast.add({
                            severity: ToastSeverity.ERROR,
                            detail: 'Não foi possível calcular a rota',
                            life: 3000
                        });
                    }
                }
            );
        },
        busca() {
            this.passos = 0;
            if (this.form.consultor != null) {
                // this.stopFetchingConsultores();
                this.getRotaConsultor();
            }

            if (this.form.localizacaoCliente != null) {
                console.log('localizacaoCliente', this.form.localizacaoCliente);
                console.log('localizacaoClientes', this.localizacaoClientes);

                const localizacao = this.localizacaoClientes.find((item) => item.user_id === this.form.localizacaoCliente);

                if (localizacao) {
                    //console.log('Localização encontrada:', localizacao);
                    this.center = latLng(localizacao.latitude, localizacao.longitude);

                    // exemplo: centralizar o mapa
                } else {
                    console.log('Localização não encontrada para o cliente selecionado.');
                }
            }
            // this.center = latLng(this.occurrence.latitude, this.occurrence.longitude);

            this.getPontosCobrarAmanha();
            this.getClientes();
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
        // busca() {
        //     if (!this.form.dt_inicio || !this.form.dt_final) {
        //         this.toast.add({
        //             severity: ToastSeverity.WARN,
        //             detail: 'Selecione as datas de início e fim',
        //             life: 3000
        //         });
        //         return;
        //     }

        //     const dt_inicio = new Date(this.form.dt_inicio);
        //     const dt_final = new Date(this.form.dt_final);

        //     dt_inicio.setHours(0, 0, 0, 999); // Ensure the end date covers the entire day
        //     dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

        //     this.Log = this.LogReal.filter((mov) => {
        //         const dt_mov = new Date(mov.created_at); // Converte a string de data para um objeto Date
        //         return dt_mov >= dt_inicio && dt_mov <= dt_final;
        //     });
        // },
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
        this.getPontosCobrarAmanha();
        this.startFetchingConsultores();
    },
    watch: {
        cep(cepVemDoFormulario) {
            console.log('cep', cepVemDoFormulario);
            if (cepVemDoFormulario == '') {
                return;
            }

            if (cepVemDoFormulario.substr(8) != '_') {
                this.occurrence.cep = cepVemDoFormulario;

                this.externoService.getEndereco(cepVemDoFormulario).then((data) => {
                    this.occurrence.address = data.logradouro;
                    this.occurrence.neighborhood = data.bairro;
                    this.occurrence.city = data.localidade;
                    this.occurrence.number = '';
                    this.occurrence.complement = '';
                });

                this.externoService.getLatLong(cepVemDoFormulario).then((data) => {
                    if (data.status == 'OK') {
                        this.occurrence.latitude = data.results[0].geometry.viewport.northeast.lat;
                        this.occurrence.longitude = data.results[0].geometry.viewport.northeast.lng;

                        this.setCenter();
                    }
                });
            }
        }
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
                            <label for="username">Data</label>
                            <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data'" v-model="form.dt_inicio" showIcon :showOnFocus="false" class="" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="username">Atrasadas</label>
                            <Dropdown v-model="form.atrasadas" :options="atrasadasOptions" optionLabel="label" optionValue="value" placeholder="Selecione uma cor" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="consultor">Consultor</label>
                            <Dropdown v-model="form.consultor" :options="consultores" optionLabel="user_name" optionValue="user_id" placeholder="Selecione um consultor" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="consultor">Clientes</label>
                            <Dropdown v-model="form.localizacaoCliente" :options="localizacaoClientes" optionLabel="user_name" optionValue="user_id" placeholder="Selecione um cliente" filter/>
                        </div>
                    </div>
                    <div v-if="passos >= 4" class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="consultor">CEP</label>
                            <InputMask id="inputmask" mask="99999-999" :modelValue="occurrence?.cep" v-model.trim="cep"></InputMask>
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <Button label="Pesquisar" @click.prevent="busca()" class="p-button-primary mr-2 mb-2 mt-4" />
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <GoogleMap ref="mapRef" :api-key="mapKey.trim()" :zoom="zoom" :center="center" @click="onMapClick" @load="onMapLoad" style="width: 100%; height: 500px">
                        <!-- <Marker :options="markerOptions"></Marker> -->
                        <Marker v-for="(marker, index) in markers" :key="index" :options="marker.options" :title="marker.title"></Marker>

                        <Marker v-if="flightPath.path.length == 0" v-for="(marker, index) in consultoresMarkers" :key="index" :options="marker.options" :title="marker.title"></Marker>
                        <Marker v-if="flightPath.path.length == 0" v-for="(marker, index) in cobraramanhaMarkers" :key="index" :options="marker.options" :title="marker.title"></Marker>

                        <Marker v-if="flightPath.path.length == 0" v-for="(marker, index) in localizacaoClientesMarkers" :key="index" :options="marker.options" :title="marker.title"></Marker>

                        <Polyline v-for="(path, index) in flightPaths" :key="index" :options="path" />
                    </GoogleMap>
                </div>
            </div>
        </div>
    </div>
</template>
