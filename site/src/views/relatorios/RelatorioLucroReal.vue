<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Relatório de Lucro Real</h5>
				<p class="text-color-secondary">Relatório do lucro real obtido pela empresa, baseado no campo lucro_real das parcelas recebidas</p>

				<Divider />

				<!-- Filtros -->
				<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-4">
						<label>Tipo de Relatório</label>
						<Dropdown 
							v-model="tipoRelatorio" 
							:options="tiposRelatorio" 
							optionLabel="label"
							optionValue="value"
							placeholder="Selecione o tipo"
							class="w-full"
							@change="onTipoRelatorioChange"
						/>
					</div>

					<div v-if="tipoRelatorio === 'mensal'" class="field col-12 md:col-4">
						<label for="mes">Mês/Ano</label>
						<Calendar 
							v-model="filtros.mes" 
							view="month" 
							dateFormat="yy-mm"
							placeholder="Selecione o mês"
							class="w-full"
							showIcon
						/>
					</div>

					<div v-if="tipoRelatorio === 'anual'" class="field col-12 md:col-4">
						<label for="ano">Ano</label>
						<InputNumber 
							v-model="filtros.ano" 
							:min="2020"
							:max="2050"
							placeholder="Digite o ano"
							class="w-full"
						/>
					</div>

					<div v-if="tipoRelatorio === 'periodo'" class="field col-12 md:col-3">
						<label for="data_inicio">Data Início</label>
						<Calendar 
							v-model="filtros.data_inicio" 
							dateFormat="yy-mm-dd"
							placeholder="Data inicial"
							class="w-full"
							showIcon
						/>
					</div>

					<div v-if="tipoRelatorio === 'periodo'" class="field col-12 md:col-3">
						<label for="data_fim">Data Fim</label>
						<Calendar 
							v-model="filtros.data_fim" 
							dateFormat="yy-mm-dd"
							placeholder="Data final"
							class="w-full"
							showIcon
						/>
					</div>

					<div class="field col-12">
						<Button 
							label="Gerar Relatório" 
							icon="pi pi-search" 
							@click="gerarRelatorio"
							:loading="loading"
							class="p-button-primary"
						/>
						<Button 
							v-if="relatorio"
							label="Exportar Excel" 
							icon="pi pi-file-excel" 
							@click="exportarExcel"
							:loading="loadingExcel"
							class="p-button-success ml-2"
						/>
						<Button 
							v-if="relatorio"
							label="Exportar PDF" 
							icon="pi pi-file-pdf" 
							@click="exportarPDF"
							:loading="loadingPDF"
							class="p-button-danger ml-2"
						/>
					</div>
				</div>

				<!-- Resultados -->
				<div v-if="relatorio" class="mt-5">
					<Divider />

					<!-- Período -->
					<div class="grid mb-4">
						<div class="col-12">
							<h6>Período: {{ formatDate(relatorio.periodo.inicio) }} até {{ formatDate(relatorio.periodo.fim) }}</h6>
						</div>
					</div>

					<!-- Cards de Resumo -->
					<div class="grid mb-4">
						<div class="col-12 md:col-4">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-primary">{{ formatValorReal(relatorio.resumo.receita_bruta_total) }}</div>
										<div class="text-sm text-color-secondary">Receita Bruta Total</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-4">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-green-500">{{ formatValorReal(relatorio.resumo.valor_recebido_total) }}</div>
										<div class="text-sm text-color-secondary">Valor Recebido (Parcelas)</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-4">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-blue-500">{{ formatValorReal(relatorio.resumo.lucro_real_total) }}</div>
										<div class="text-sm text-color-secondary">Lucro Real Total</div>
									</div>
								</template>
							</Card>
						</div>
					</div>

					<!-- Estatísticas -->
					<div class="grid mb-4">
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-purple-500">{{ relatorio.resumo.total_parcelas_processadas }}</div>
										<div class="text-sm text-color-secondary">Parcelas Processadas</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-orange-500">{{ relatorio.resumo.total_emprestimos }}</div>
										<div class="text-sm text-color-secondary">Empréstimos</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-3">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-indigo-500">{{ formatValorReal(relatorio.resumo.outras_receitas) }}</div>
										<div class="text-sm text-color-secondary">Outras Receitas</div>
									</div>
								</template>
							</Card>
						</div>
					</div>

					<!-- Detalhamento por Empréstimo -->
					<div v-if="relatorio.detalhamento_emprestimos && relatorio.detalhamento_emprestimos.length > 0" class="grid mb-4">
						<div class="col-12">
							<h6>Detalhamento por Empréstimo ({{ relatorio.detalhamento_emprestimos.length }} empréstimos)</h6>
							<DataTable 
								:value="relatorio.detalhamento_emprestimos" 
								:paginator="true" 
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
								:expandableRows="true"
								v-model:expandedRows="expandedRows"
							>
								<Column :expander="true" headerStyle="width: 3rem" />
								<Column field="emprestimo_id" header="ID Empréstimo" />
								<Column field="cliente" header="Cliente" style="min-width: 20rem" />
								<Column field="valor_emprestado" header="Valor Emprestado">
									<template #body="{ data }">
										{{ formatValorReal(data.valor_emprestado) }}
									</template>
								</Column>
								<Column field="lucro_total_emprestimo" header="Lucro Total">
									<template #body="{ data }">
										{{ formatValorReal(data.lucro_total_emprestimo) }}
									</template>
								</Column>
								<Column field="total_valor_recebido" header="Valor Recebido no Período">
									<template #body="{ data }">
										{{ formatValorReal(data.total_valor_recebido) }}
									</template>
								</Column>
								<Column field="total_lucro_real_periodo" header="Lucro Real no Período">
									<template #body="{ data }">
										<strong class="text-green-500">{{ formatValorReal(data.total_lucro_real_periodo) }}</strong>
									</template>
								</Column>
								<Column field="parcelas_recebidas_periodo.length" header="Parcelas Recebidas">
									<template #body="{ data }">
										{{ data.parcelas_recebidas_periodo?.length || 0 }}
									</template>
								</Column>

								<template #expansion="slotProps">
									<div class="p-3">
										<h6 class="mb-3">Parcelas Recebidas</h6>
										<DataTable :value="slotProps.data.parcelas_recebidas_periodo" class="p-datatable-sm">
											<Column field="parcela_numero" header="Parcela" />
											<Column field="data_recebimento" header="Data">
												<template #body="{ data }">
													{{ formatDate(data.data_recebimento) }}
												</template>
											</Column>
											<Column field="valor_recebido" header="Valor Recebido">
												<template #body="{ data }">
													{{ formatValorReal(data.valor_recebido) }}
												</template>
											</Column>
											<Column field="lucro_real" header="Lucro Real">
												<template #body="{ data }">
													<strong class="text-green-500">{{ formatValorReal(data.lucro_real) }}</strong>
												</template>
											</Column>
											<Column field="descricao" header="Descrição" style="min-width: 20rem" />
											<Column field="banco" header="Banco" />
										</DataTable>
									</div>
								</template>
							</DataTable>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import RelatorioLucroRealService from '@/service/RelatorioLucroRealService';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';

export default {
	name: 'RelatorioLucroReal',
	setup() {
		return {
			relatorioLucroRealService: new RelatorioLucroRealService(),
			toast: useToast(),
			loading: ref(false),
			loadingExcel: ref(false),
			loadingPDF: ref(false),
			tipoRelatorio: ref('mensal'),
			tiposRelatorio: [
				{ label: 'Mensal', value: 'mensal' },
				{ label: 'Anual', value: 'anual' },
				{ label: 'Período Customizado', value: 'periodo' }
			],
			filtros: {
				mes: null,
				ano: new Date().getFullYear(),
				data_inicio: null,
				data_fim: null
			},
			relatorio: null,
			expandedRows: []
		};
	},
	mounted() {
		const hoje = new Date();
		this.filtros.mes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
	},
	methods: {
		onTipoRelatorioChange() {
			this.relatorio = null;
		},
		async gerarRelatorio() {
			this.loading = true;
			this.relatorio = null;

			try {
				let response;

				if (this.tipoRelatorio === 'mensal') {
					if (!this.filtros.mes) {
						this.toast.add({
							severity: ToastSeverity.WARN,
							detail: 'Selecione o mês',
							life: 3000
						});
						this.loading = false;
						return;
					}
					let mes = this.filtros.mes;
					if (mes instanceof Date) {
						const ano = mes.getFullYear();
						const mesNum = String(mes.getMonth() + 1).padStart(2, '0');
						mes = `${ano}-${mesNum}`;
					} else if (typeof mes === 'string' && !mes.includes('-')) {
						mes = mes.substring(0, 4) + '-' + mes.substring(4);
					}
					response = await this.relatorioLucroRealService.relatorioMensal(mes);
				} else if (this.tipoRelatorio === 'anual') {
					if (!this.filtros.ano) {
						this.toast.add({
							severity: ToastSeverity.WARN,
							detail: 'Informe o ano',
							life: 3000
						});
						this.loading = false;
						return;
					}
					response = await this.relatorioLucroRealService.relatorioAnual(this.filtros.ano);
				} else {
					if (!this.filtros.data_inicio || !this.filtros.data_fim) {
						this.toast.add({
							severity: ToastSeverity.WARN,
							detail: 'Selecione as datas inicial e final',
							life: 3000
						});
						this.loading = false;
						return;
					}
					const dataInicio = typeof this.filtros.data_inicio === 'string' 
						? this.filtros.data_inicio.split('T')[0]
						: this.filtros.data_inicio.toISOString().split('T')[0];
					const dataFim = typeof this.filtros.data_fim === 'string'
						? this.filtros.data_fim.split('T')[0]
						: this.filtros.data_fim.toISOString().split('T')[0];
					response = await this.relatorioLucroRealService.relatorioPeriodo(dataInicio, dataFim);
				}

				if (response.data) {
					this.relatorio = response.data;
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: 'Relatório gerado com sucesso',
						life: 3000
					});
				}
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao gerar relatório',
					life: 3000
				});
			} finally {
				this.loading = false;
			}
		},
		async exportarExcel() {
			this.loadingExcel = true;
			try {
				const params = {
					tipo: this.tipoRelatorio
				};

				if (this.tipoRelatorio === 'mensal') {
					let mes = this.filtros.mes;
					if (mes instanceof Date) {
						const ano = mes.getFullYear();
						const mesNum = String(mes.getMonth() + 1).padStart(2, '0');
						mes = `${ano}-${mesNum}`;
					} else if (typeof mes === 'string' && !mes.includes('-')) {
						mes = mes.substring(0, 4) + '-' + mes.substring(4);
					}
					params.mes = mes;
				} else if (this.tipoRelatorio === 'anual') {
					params.ano = this.filtros.ano;
				} else {
					const dataInicio = typeof this.filtros.data_inicio === 'string' 
						? this.filtros.data_inicio.split('T')[0]
						: this.filtros.data_inicio.toISOString().split('T')[0];
					const dataFim = typeof this.filtros.data_fim === 'string'
						? this.filtros.data_fim.split('T')[0]
						: this.filtros.data_fim.toISOString().split('T')[0];
					params.data_inicio = dataInicio;
					params.data_fim = dataFim;
				}

				const response = await this.relatorioLucroRealService.exportarExcel(params);
				
				const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
				const link = document.createElement('a');
				link.href = url;
				let nomeArquivo = 'relatorio-lucro-real';
				if (params.mes) nomeArquivo += `-${params.mes}`;
				else if (params.ano) nomeArquivo += `-${params.ano}`;
				else if (params.data_inicio && params.data_fim) nomeArquivo += `-${params.data_inicio}-a-${params.data_fim}`;
				link.setAttribute('download', `${nomeArquivo}.xlsx`);
				document.body.appendChild(link);
				link.click();
				link.remove();

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: 'Excel exportado com sucesso',
					life: 3000
				});
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao exportar Excel',
					life: 3000
				});
			} finally {
				this.loadingExcel = false;
			}
		},
		async exportarPDF() {
			this.loadingPDF = true;
			try {
				const params = {
					tipo: this.tipoRelatorio
				};

				if (this.tipoRelatorio === 'mensal') {
					let mes = this.filtros.mes;
					if (mes instanceof Date) {
						const ano = mes.getFullYear();
						const mesNum = String(mes.getMonth() + 1).padStart(2, '0');
						mes = `${ano}-${mesNum}`;
					} else if (typeof mes === 'string' && !mes.includes('-')) {
						mes = mes.substring(0, 4) + '-' + mes.substring(4);
					}
					params.mes = mes;
				} else if (this.tipoRelatorio === 'anual') {
					params.ano = this.filtros.ano;
				} else {
					const dataInicio = typeof this.filtros.data_inicio === 'string' 
						? this.filtros.data_inicio.split('T')[0]
						: this.filtros.data_inicio.toISOString().split('T')[0];
					const dataFim = typeof this.filtros.data_fim === 'string'
						? this.filtros.data_fim.split('T')[0]
						: this.filtros.data_fim.toISOString().split('T')[0];
					params.data_inicio = dataInicio;
					params.data_fim = dataFim;
				}

				const response = await this.relatorioLucroRealService.exportarPDF(params);
				
				const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
				const link = document.createElement('a');
				link.href = url;
				let nomeArquivo = 'relatorio-lucro-real';
				if (params.mes) nomeArquivo += `-${params.mes}`;
				else if (params.ano) nomeArquivo += `-${params.ano}`;
				else if (params.data_inicio && params.data_fim) nomeArquivo += `-${params.data_inicio}-a-${params.data_fim}`;
				link.setAttribute('download', `${nomeArquivo}.pdf`);
				document.body.appendChild(link);
				link.click();
				link.remove();

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: 'PDF exportado com sucesso',
					life: 3000
				});
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao exportar PDF',
					life: 3000
				});
			} finally {
				this.loadingPDF = false;
			}
		},
		formatValorReal(valor) {
			if (!valor && valor !== 0) return 'R$ 0,00';
			return valor.toLocaleString('pt-BR', {
				style: 'currency',
				currency: 'BRL'
			});
		},
		formatDate(dateString) {
			if (!dateString) return '-';
			const date = new Date(dateString);
			return date.toLocaleDateString('pt-BR');
		}
	}
};
</script>

<style scoped>
.font-bold {
	font-weight: bold;
}
</style>

