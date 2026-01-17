<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Relatório Fiscal - Lucro Presumido</h5>
				<p class="text-color-secondary">Relatório fiscal para declaração de impostos (IRPJ e CSLL) no regime de lucro presumido</p>

				<Divider />

				<!-- Filtros -->
				<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-4">
						<label>Tipo de Cálculo</label>
						<Dropdown 
							v-model="tipoCalculo" 
							:options="tiposCalculo" 
							optionLabel="label"
							optionValue="value"
							placeholder="Selecione o tipo de cálculo"
							class="w-full"
							@change="onTipoCalculoChange"
						/>
					</div>

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
										<div class="text-2xl font-bold text-primary">{{ formatValorReal(relatorio.receita_bruta) }}</div>
										<div class="text-sm text-color-secondary">Receita Bruta</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-4">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-green-500">{{ formatValorReal(relatorio.lucro_presumido) }}</div>
										<div class="text-sm text-color-secondary">Lucro Presumido ({{ relatorio.configuracao.percentual_presuncao }}%)</div>
									</div>
								</template>
							</Card>
						</div>
						<div class="col-12 md:col-4">
							<Card>
								<template #content>
									<div class="text-center">
										<div class="text-2xl font-bold text-red-500">{{ formatValorReal(relatorio.total_impostos) }}</div>
										<div class="text-sm text-color-secondary">Total de Impostos</div>
									</div>
								</template>
							</Card>
						</div>
					</div>

					<!-- Configuração Fiscal -->
					<div class="grid mb-4">
						<div class="col-12">
							<h6>Configuração Fiscal</h6>
							<div class="grid">
								<div class="col-12 md:col-3">
									<small class="text-color-secondary">Percentual de Presunção:</small>
									<div class="font-bold">{{ relatorio.configuracao.percentual_presuncao }}%</div>
								</div>
								<div class="col-12 md:col-3">
									<small class="text-color-secondary">Alíquota IRPJ:</small>
									<div class="font-bold">{{ relatorio.configuracao.aliquota_irpj }}%</div>
								</div>
								<div class="col-12 md:col-3">
									<small class="text-color-secondary">Alíquota IRPJ Adicional:</small>
									<div class="font-bold">{{ relatorio.configuracao.aliquota_irpj_adicional }}%</div>
								</div>
								<div class="col-12 md:col-3">
									<small class="text-color-secondary">Alíquota CSLL:</small>
									<div class="font-bold">{{ relatorio.configuracao.aliquota_csll }}%</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Cálculos Tributários -->
					<div class="grid mb-4">
						<div class="col-12">
							<h6>Cálculos Tributários</h6>
							<DataTable :value="[relatorio]" class="p-datatable-sm">
								<Column field="receita_bruta" header="Receita Bruta">
									<template #body="{ data }">
										{{ formatValorReal(data.receita_bruta) }}
									</template>
								</Column>
								<Column field="despesas_dedutiveis" header="Despesas Dedutíveis">
									<template #body="{ data }">
										{{ formatValorReal(data.despesas_dedutiveis) }}
									</template>
								</Column>
								<Column field="lucro_presumido" header="Lucro Presumido">
									<template #body="{ data }">
										{{ formatValorReal(data.lucro_presumido) }}
									</template>
								</Column>
								<Column field="base_tributavel" header="Base Tributável">
									<template #body="{ data }">
										{{ formatValorReal(data.base_tributavel) }}
									</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<!-- Impostos -->
					<div class="grid mb-4">
						<div class="col-12">
							<h6>Impostos</h6>
							<DataTable :value="[relatorio]" class="p-datatable-sm">
								<Column field="irpj.normal" header="IRPJ Normal (15%)">
									<template #body="{ data }">
										{{ formatValorReal(data.irpj.normal) }}
									</template>
								</Column>
								<Column field="irpj.adicional" header="IRPJ Adicional (10%)">
									<template #body="{ data }">
										{{ formatValorReal(data.irpj.adicional) }}
									</template>
								</Column>
								<Column field="irpj.total" header="IRPJ Total">
									<template #body="{ data }">
										<strong>{{ formatValorReal(data.irpj.total) }}</strong>
									</template>
								</Column>
								<Column field="csll" header="CSLL (9%)">
									<template #body="{ data }">
										<strong>{{ formatValorReal(data.csll) }}</strong>
									</template>
								</Column>
								<Column field="total_impostos" header="Total de Impostos">
									<template #body="{ data }">
										<strong class="text-red-500">{{ formatValorReal(data.total_impostos) }}</strong>
									</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<!-- Tabela de Movimentações (se houver) -->
					<div v-if="relatorio.movimentacoes && relatorio.movimentacoes.length > 0" class="grid mb-4">
						<div class="col-12">
							<h6>Movimentações Financeiras ({{ relatorio.movimentacoes.length }} registros)</h6>
							<DataTable 
								:value="relatorio.movimentacoes" 
								:paginator="true" 
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<Column field="dt_movimentacao" header="Data">
									<template #body="{ data }">
										{{ formatDate(data.dt_movimentacao) }}
									</template>
								</Column>
								<Column field="descricao" header="Descrição" style="min-width: 20rem" />
								<Column field="banco.name" header="Banco">
									<template #body="{ data }">
										{{ data.banco?.name || '-' }}
									</template>
								</Column>
								<Column field="valor" header="Valor">
									<template #body="{ data }">
										{{ formatValorReal(data.valor) }}
									</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<!-- Tabela de Despesas (se houver) -->
					<div v-if="relatorio.despesas && relatorio.despesas.length > 0" class="grid mb-4">
						<div class="col-12">
							<h6>Despesas Dedutíveis ({{ relatorio.despesas.length }} registros)</h6>
							<DataTable 
								:value="relatorio.despesas" 
								:paginator="true" 
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<Column field="dt_baixa" header="Data Pagamento">
									<template #body="{ data }">
										{{ formatDate(data.dt_baixa) }}
									</template>
								</Column>
								<Column field="descricao" header="Descrição" style="min-width: 20rem" />
								<Column field="fornecedor.nome_completo" header="Fornecedor">
									<template #body="{ data }">
										{{ data.fornecedor?.nome_completo || '-' }}
									</template>
								</Column>
								<Column field="tipodoc" header="Tipo Doc." />
								<Column field="valor" header="Valor">
									<template #body="{ data }">
										{{ formatValorReal(data.valor) }}
									</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<!-- Detalhamento por Empréstimo (apenas para tipo proporcional) -->
					<div v-if="relatorio.tipo_calculo === 'proporcional' && relatorio.detalhamento_emprestimos && relatorio.detalhamento_emprestimos.length > 0" class="grid mb-4">
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
								<Column field="lucro_total" header="Lucro Total">
									<template #body="{ data }">
										{{ formatValorReal(data.lucro_total) }}
									</template>
								</Column>
								<Column field="num_parcelas" header="Parcelas" />
								<Column field="lucro_por_parcela" header="Lucro/Parcela">
									<template #body="{ data }">
										{{ formatValorReal(data.lucro_por_parcela) }}
									</template>
								</Column>
								<Column field="total_lucro_periodo" header="Lucro no Período">
									<template #body="{ data }">
										<strong>{{ formatValorReal(data.total_lucro_periodo) }}</strong>
									</template>
								</Column>
								<template #expansion="slotProps">
									<div class="p-3">
										<h6>Parcelas Recebidas no Período</h6>
										<DataTable 
											:value="slotProps.data.parcelas_recebidas_periodo" 
											:paginator="true" 
											:rows="5"
											responsiveLayout="scroll"
											class="p-datatable-sm"
										>
											<Column field="data_recebimento" header="Data Recebimento">
												<template #body="{ data }">
													{{ formatDate(data.data_recebimento) }}
												</template>
											</Column>
											<Column field="valor_recebido" header="Valor Recebido">
												<template #body="{ data }">
													{{ formatValorReal(data.valor_recebido) }}
												</template>
											</Column>
											<Column field="lucro_proporcional" header="Lucro Proporcional">
												<template #body="{ data }">
													<strong>{{ formatValorReal(data.lucro_proporcional) }}</strong>
												</template>
											</Column>
											<Column field="descricao" header="Descrição" style="min-width: 30rem" />
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
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import RelatorioFiscalService from '@/service/RelatorioFiscalService';

export default {
	name: 'RelatorioFiscal',
	setup() {
		return {
			toast: useToast(),
			relatorioFiscalService: new RelatorioFiscalService()
		};
	},
		data() {
		return {
			loading: ref(false),
			loadingExcel: ref(false),
			loadingPDF: ref(false),
			tipoCalculo: ref('proporcional'),
			tiposCalculo: [
				{ label: 'Lucro Proporcional', value: 'proporcional' },
				{ label: 'Lucro Presumido', value: 'presumido' }
			],
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
		// Definir mês atual como padrão (Date object para o Calendar)
		const hoje = new Date();
		// Definir dia 1 para evitar problemas com dias inexistentes
		this.filtros.mes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
	},
	methods: {
		onTipoCalculoChange() {
			this.relatorio = null;
		},
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
					// Converter Date para formato YYYY-MM
					let mes = this.filtros.mes;
					if (mes instanceof Date) {
						const ano = mes.getFullYear();
						const mesNum = String(mes.getMonth() + 1).padStart(2, '0');
						mes = `${ano}-${mesNum}`;
					} else if (typeof mes === 'string' && !mes.includes('-')) {
						// Se for string no formato YYYYMM, converter para YYYY-MM
						mes = mes.substring(0, 4) + '-' + mes.substring(4);
					}
					response = await this.relatorioFiscalService.relatorioMensal(mes, this.tipoCalculo);
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
					response = await this.relatorioFiscalService.relatorioAnual(this.filtros.ano, this.tipoCalculo);
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
					response = await this.relatorioFiscalService.relatorioPeriodo(dataInicio, dataFim, this.tipoCalculo);
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
					detail: error.response?.data?.message || 'Erro ao gerar relatório',
					life: 3000
				});
			} finally {
				this.loading = false;
			}
		},
		async exportarExcel() {
			this.loadingExcel = true;
			try {
				let params = {};
				
				if (this.tipoRelatorio === 'mensal') {
					// Converter Date para formato YYYY-MM
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

				params.tipo = this.tipoCalculo;

				const response = await this.relatorioFiscalService.exportarExcel(params);
				
				// Criar link de download
				const url = window.URL.createObjectURL(new Blob([response.data]));
				const link = document.createElement('a');
				link.href = url;
				let nomeArquivo = 'relatorio-fiscal';
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
				let params = {};
				
				if (this.tipoRelatorio === 'mensal') {
					// Converter Date para formato YYYY-MM
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

				params.tipo = this.tipoCalculo;

				const response = await this.relatorioFiscalService.exportarPDF(params);
				
				// Criar link de download
				const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
				const link = document.createElement('a');
				link.href = url;
				let nomeArquivo = 'relatorio-fiscal';
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

