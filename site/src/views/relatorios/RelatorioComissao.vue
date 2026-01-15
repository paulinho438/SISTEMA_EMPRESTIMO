<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Relatório de Comissão de Consultores</h5>
				<p class="text-color-secondary">Relatório de empréstimos por consultor para cálculo de comissão</p>

				<Divider />

				<!-- Filtros -->
				<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-4">
						<label for="consultor">Consultor *</label>
						<AutoComplete 
							v-model="selectedConsultor" 
							:suggestions="consultoresFiltrados" 
							@complete="searchConsultor"
							field="nome_completo"
							placeholder="Digite o nome do consultor"
							class="w-full"
							:class="{ 'p-invalid': errors.consultor }"
							:loading="loadingConsultor"
						/>
						<small v-if="errors.consultor" class="text-red-500">{{ errors.consultor[0] }}</small>
					</div>

					<div class="field col-12 md:col-4">
						<label for="data_inicio">Data Início *</label>
						<Calendar 
							v-model="filtros.data_inicio" 
							dateFormat="yy-mm-dd"
							placeholder="Selecione a data inicial"
							class="w-full"
							:class="{ 'p-invalid': errors.data_inicio }"
							showIcon
						/>
						<small v-if="errors.data_inicio" class="text-red-500">{{ errors.data_inicio[0] }}</small>
					</div>

					<div class="field col-12 md:col-4">
						<label for="data_fim">Data Fim *</label>
						<Calendar 
							v-model="filtros.data_fim" 
							dateFormat="yy-mm-dd"
							placeholder="Selecione a data final"
							class="w-full"
							:class="{ 'p-invalid': errors.data_fim }"
							showIcon
						/>
						<small v-if="errors.data_fim" class="text-red-500">{{ errors.data_fim[0] }}</small>
					</div>

					<div class="field col-12">
						<Button 
							label="Gerar Relatório" 
							icon="pi pi-search" 
							@click="gerarRelatorio"
							:loading="loading"
							class="w-full"
						/>
					</div>
				</div>

				<!-- Resultados -->
				<div v-if="relatorio" class="mt-5">
					<Divider />

					<!-- Informações do Relatório -->
					<div class="grid mb-4">
						<div class="col-12">
							<h6>Consultor: {{ relatorio.consultor?.nome_completo }}</h6>
							<p class="text-color-secondary">
								Período: {{ relatorio.periodo?.data_inicio }} até {{ relatorio.periodo?.data_fim }}
							</p>
						</div>
					</div>

					<!-- Totais -->
					<div class="grid mb-4">
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-primary">{{ relatorio.totais?.total_emprestimos }}</div>
										<div class="text-sm text-color-secondary">Total de Empréstimos</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-green-500">{{ formatValorReal(relatorio.totais?.total_valor) }}</div>
										<div class="text-sm text-color-secondary">Valor Total</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-blue-500">{{ formatValorReal(relatorio.totais?.total_lucro) }}</div>
										<div class="text-sm text-color-secondary">Lucro Total</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-orange-500">{{ formatValorReal(relatorio.totais?.total_saldo_a_receber) }}</div>
										<div class="text-sm text-color-secondary">Saldo a Receber</div>
									</div>
								</template>
							</Card>
						</div>
					</div>

					<!-- Tabela de Empréstimos -->
					<DataTable 
						:value="emprestimosOrdenados" 
						:paginator="true" 
						:rows="20"
						:loading="loading"
						responsiveLayout="scroll"
						class="p-datatable-sm"
						:sortField="sortField"
						:sortOrder="sortOrder"
						@sort="onSort"
					>
						<Column field="id" header="ID" :sortable="true" style="min-width: 5rem">
							<template #body="{ data }">
								{{ data.id }}
							</template>
						</Column>

						<Column field="dt_lancamento" header="Data Lançamento" :sortable="true" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.dt_lancamento }}
							</template>
						</Column>

						<Column field="cliente.nome_completo" header="Cliente" :sortable="true" style="min-width: 15rem">
							<template #body="{ data }">
								{{ data.cliente?.nome_completo }}
							</template>
						</Column>

						<Column field="valor" header="Valor" :sortable="true" style="min-width: 10rem">
							<template #body="{ data }">
								{{ formatValorReal(data.valor) }}
							</template>
						</Column>

						<Column field="lucro" header="Lucro" :sortable="true" style="min-width: 10rem">
							<template #body="{ data }">
								{{ formatValorReal(data.lucro) }}
							</template>
						</Column>

						<Column field="qt_parcelas_atrasadas" header="Parcelas Atrasadas" :sortable="true" style="min-width: 12rem">
							<template #body="{ data }">
								<span :class="getAtrasadasClass(data.qt_parcelas_atrasadas)">
									{{ data.qt_parcelas_atrasadas }} / {{ data.qt_parcelas_total }}
								</span>
							</template>
						</Column>

						<Column field="status" header="Status" :sortable="true" style="min-width: 10rem">
							<template #body="{ data }">
								<Button 
									:label="data.status" 
									:class="'p-button-rounded ' + data.status_class + ' mr-2 mb-2'" 
									size="small"
								/>
							</template>
						</Column>

						<Column field="saldo_a_receber" header="Saldo a Receber" :sortable="true" style="min-width: 12rem">
							<template #body="{ data }">
								{{ formatValorReal(data.saldo_a_receber) }}
							</template>
						</Column>

						<Column field="valor_total_pago" header="Valor Pago" :sortable="true" style="min-width: 12rem">
							<template #body="{ data }">
								{{ formatValorReal(data.valor_total_pago) }}
							</template>
						</Column>
					</DataTable>

					<!-- Resumo por Status -->
					<div class="grid mt-4">
						<div class="col-12">
							<h6>Resumo por Status:</h6>
							<div class="grid">
								<div class="col-12 md:col-2">
									<span class="p-button-rounded p-button-success p-button-sm mr-2">Em Dias: {{ relatorio.totais?.total_em_dias }}</span>
								</div>
								<div class="col-12 md:col-2">
									<span class="p-button-rounded p-button-info p-button-sm mr-2">Atrasado: {{ relatorio.totais?.total_atrasado }}</span>
								</div>
								<div class="col-12 md:col-2">
									<span class="p-button-rounded p-button-warning p-button-sm mr-2">Muito Atrasado: {{ relatorio.totais?.total_muito_atrasado }}</span>
								</div>
								<div class="col-12 md:col-2">
									<span class="p-button-rounded p-button-danger p-button-sm mr-2">Vencido: {{ relatorio.totais?.total_vencido }}</span>
								</div>
								<div class="col-12 md:col-2">
									<span class="p-button-rounded p-button-success p-button-sm mr-2">Pago: {{ relatorio.totais?.total_pago }}</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default {
	name: 'RelatorioComissao',
	setup() {
		return {
			toast: useToast(),
			emprestimoService: new EmprestimoService()
		};
	},
	data() {
		return {
			loading: ref(false),
			loadingConsultor: ref(false),
			consultoresFiltrados: ref([]),
			selectedConsultor: ref(null),
			filtros: {
				user_id: null,
				data_inicio: null,
				data_fim: null
			},
			errors: {},
			relatorio: null
		};
	},
	methods: {
		async searchConsultor(event) {
			this.loadingConsultor = true;
			try {
				const response = await this.emprestimoService.searchConsultor(event.query);
				this.consultoresFiltrados = response.data || [];
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao buscar consultores: ' + error.message,
					life: 3000
				});
			} finally {
				this.loadingConsultor = false;
			}
		},
		async gerarRelatorio() {
			// Validar campos
			if (!this.selectedConsultor) {
				this.toast.add({
					severity: ToastSeverity.WARN,
					detail: 'Selecione um consultor',
					life: 3000
				});
				return;
			}

			if (!this.filtros.data_inicio || !this.filtros.data_fim) {
				this.toast.add({
					severity: ToastSeverity.WARN,
					detail: 'Selecione as datas inicial e final',
					life: 3000
				});
				return;
			}

			this.loading = true;
			this.errors = {};
			this.relatorio = null;

			try {
				const formData = {
					user_id: this.selectedConsultor.id,
					data_inicio: this.filtros.data_inicio ? this.filtros.data_inicio.toISOString().split('T')[0] : null,
					data_fim: this.filtros.data_fim ? this.filtros.data_fim.toISOString().split('T')[0] : null
				};

				const response = await axios.post(`${apiPath}/emprestimo/relatorio-comissao`, formData);

				if (response.data.success) {
					this.relatorio = response.data;
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: 'Relatório gerado com sucesso',
						life: 3000
					});
				} else {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: response.data.message || 'Erro ao gerar relatório',
						life: 3000
					});
				}
			} catch (error) {
				if (error.response?.data?.errors) {
					this.errors = error.response.data.errors;
				}
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao gerar relatório',
					life: 3000
				});
			} finally {
				this.loading = false;
			}
		},
		formatValorReal(valor) {
			if (!valor) return 'R$ 0,00';
			return valor.toLocaleString('pt-BR', {
				style: 'currency',
				currency: 'BRL'
			});
		},
		getAtrasadasClass(qtAtrasadas) {
			if (qtAtrasadas === 0) return 'text-green-500 font-bold';
			if (qtAtrasadas >= 1 && qtAtrasadas <= 3) return 'text-blue-500 font-bold';
			if (qtAtrasadas >= 4 && qtAtrasadas <= 9) return 'text-orange-500 font-bold';
			return 'text-red-500 font-bold';
		},
		onSort(event) {
			this.sortField = event.sortField;
			this.sortOrder = event.sortOrder;
		},
		parseDateBR(dateString) {
			if (!dateString) return new Date(0);
			const [dia, mes, ano] = dateString.split('/');
			return new Date(parseInt(ano), parseInt(mes) - 1, parseInt(dia));
		}
	},
	computed: {
		emprestimosOrdenados() {
			if (!this.relatorio || !this.relatorio.emprestimos) {
				return [];
			}

			const emprestimos = [...this.relatorio.emprestimos];

			// Ordenar por nome do cliente (alfabética) e depois por data de criação
			emprestimos.sort((a, b) => {
				// Primeiro: ordenar por nome do cliente
				const nomeA = (a.cliente?.nome_completo || '').toLowerCase();
				const nomeB = (b.cliente?.nome_completo || '').toLowerCase();
				
				if (nomeA < nomeB) return -1;
				if (nomeA > nomeB) return 1;
				
				// Se os nomes forem iguais, ordenar por data de lançamento (mais antigo primeiro)
				const dataA = this.parseDateBR(a.dt_lancamento);
				const dataB = this.parseDateBR(b.dt_lancamento);
				
				if (dataA < dataB) return -1;
				if (dataA > dataB) return 1;
				
				return 0;
			});

			return emprestimos;
		}
	},
	watch: {
		selectedConsultor(newValue) {
			if (newValue) {
				this.filtros.user_id = newValue.id;
			}
		}
	}
};
</script>

<style scoped>
.text-green-500 {
	color: #10b981;
}

.text-blue-500 {
	color: #3b82f6;
}

.text-orange-500 {
	color: #f97316;
}

.text-red-500 {
	color: #ef4444;
}

.font-bold {
	font-weight: bold;
}
</style>

