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

					<!-- Auditoria dos Cards -->
					<div class="grid mb-3">
						<div class="col-12 md:col-4">
							<label class="block mb-2">Filtro por tipo (auditoria)</label>
							<Dropdown
								v-model="tipoAuditoriaSelecionado"
								:options="tiposAuditoria"
								optionLabel="label"
								optionValue="value"
								placeholder="Selecione o tipo"
								showClear
								class="w-full"
							/>
						</div>
					</div>

					<div class="grid mb-4">
						<div class="col-12">
							<div class="flex justify-content-between align-items-center mb-2">
								<h6 class="m-0">Auditoria - Receita Bruta Total</h6>
								<Button label="Exportar Excel" icon="pi pi-file-excel" class="p-button-success p-button-sm" @click="exportarAuditoriaReceitaBruta" />
							</div>
							<DataTable
								:value="filtrarAuditoriaPorTipo(relatorio.detalhamento_receita_bruta || [])"
								:paginator="true"
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<template #empty> Nenhum item encontrado para Receita Bruta. </template>
								<Column field="origem" header="Origem" />
								<Column field="tipo" header="Tipo" />
								<Column field="id" header="ID Mov." />
								<Column field="data" header="Data">
									<template #body="{ data }">{{ formatDate(data.data) }}</template>
								</Column>
								<Column field="descricao" header="Descrição" style="min-width: 24rem" />
								<Column field="banco" header="Banco" />
								<Column field="valor" header="Valor">
									<template #body="{ data }">{{ formatValorReal(data.valor || 0) }}</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<div class="grid mb-4">
						<div class="col-12">
							<div class="flex justify-content-between align-items-center mb-2">
								<h6 class="m-0">Auditoria - Valor Recebido (Parcelas)</h6>
								<Button label="Exportar Excel" icon="pi pi-file-excel" class="p-button-success p-button-sm" @click="exportarAuditoriaValorRecebido" />
							</div>
							<DataTable
								:value="filtrarAuditoriaPorTipo(relatorio.detalhamento_movimentacoes || [])"
								:paginator="true"
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<template #empty> Nenhuma parcela recebida no período. </template>
								<Column field="id" header="ID Mov." />
								<Column field="tipo" header="Tipo" />
								<Column field="data" header="Data">
									<template #body="{ data }">{{ formatDate(data.data) }}</template>
								</Column>
								<Column field="cliente" header="Cliente" style="min-width: 18rem" />
								<Column field="parcela" header="Parcela" />
								<Column field="descricao" header="Descrição" style="min-width: 22rem" />
								<Column field="banco" header="Banco" />
								<Column field="valor_recebido" header="Valor Recebido">
									<template #body="{ data }">{{ formatValorReal(data.valor_recebido || 0) }}</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<div class="grid mb-4">
						<div class="col-12">
							<div class="flex justify-content-between align-items-center mb-2">
								<h6 class="m-0">Auditoria - Lucro Real Total</h6>
								<Button label="Exportar Excel" icon="pi pi-file-excel" class="p-button-success p-button-sm" @click="exportarAuditoriaLucroReal" />
							</div>
							<DataTable
								:value="filtrarAuditoriaPorTipo(relatorio.detalhamento_movimentacoes || [])"
								:paginator="true"
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<template #empty> Nenhum item de lucro real no período. </template>
								<Column field="id" header="ID Mov." />
								<Column field="tipo" header="Tipo" />
								<Column field="data" header="Data">
									<template #body="{ data }">{{ formatDate(data.data) }}</template>
								</Column>
								<Column field="cliente" header="Cliente" style="min-width: 18rem" />
								<Column field="parcela" header="Parcela" />
								<Column field="descricao" header="Descrição" style="min-width: 22rem" />
								<Column field="lucro_real" header="Lucro Real">
									<template #body="{ data }">
										<strong class="text-green-500">{{ formatValorReal(data.lucro_real || 0) }}</strong>
									</template>
								</Column>
							</DataTable>
						</div>
					</div>

					<div class="grid mb-4">
						<div class="col-12">
							<div class="flex justify-content-between align-items-center mb-2">
								<h6 class="m-0">Auditoria - Parcelas Processadas</h6>
								<Button label="Exportar Excel" icon="pi pi-file-excel" class="p-button-success p-button-sm" @click="exportarAuditoriaParcelasProcessadas" />
							</div>
							<DataTable
								:value="filtrarAuditoriaPorTipo(relatorio.detalhamento_movimentacoes || [])"
								:paginator="true"
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<template #empty> Nenhuma parcela processada no período. </template>
								<Column field="id" header="ID Mov." />
								<Column field="tipo" header="Tipo" />
								<Column field="parcela" header="Parcela" />
								<Column field="cliente" header="Cliente" style="min-width: 18rem" />
								<Column field="data" header="Data">
									<template #body="{ data }">{{ formatDate(data.data) }}</template>
								</Column>
								<Column field="descricao" header="Descrição" style="min-width: 24rem" />
							</DataTable>
						</div>
					</div>

					<div class="grid mb-4">
						<div class="col-12">
							<div class="flex justify-content-between align-items-center mb-2">
								<h6 class="m-0">Auditoria - Outras Receitas</h6>
								<Button label="Exportar Excel" icon="pi pi-file-excel" class="p-button-success p-button-sm" @click="exportarAuditoriaOutrasReceitas" />
							</div>
							<DataTable
								:value="filtrarAuditoriaPorTipo(relatorio.detalhamento_outras_receitas || [])"
								:paginator="true"
								:rows="10"
								responsiveLayout="scroll"
								class="p-datatable-sm"
							>
								<template #empty> Nenhuma outra receita no período. </template>
								<Column field="id" header="ID Mov." />
								<Column field="tipo" header="Tipo" />
								<Column field="data" header="Data">
									<template #body="{ data }">{{ formatDate(data.data) }}</template>
								</Column>
								<Column field="descricao" header="Descrição" style="min-width: 24rem" />
								<Column field="banco" header="Banco" />
								<Column field="valor" header="Valor">
									<template #body="{ data }">{{ formatValorReal(data.valor || 0) }}</template>
								</Column>
							</DataTable>
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
			expandedRows: [],
			tipoAuditoriaSelecionado: null,
			tiposAuditoria: [
				{ label: 'Refinanciamento', value: 'REFINANCIAMENTO' },
				{ label: 'Baixa automática', value: 'BAIXA AUTOMATICA' },
				{ label: 'Fechamento de caixa', value: 'FECHAMENTO DE CAIXA' },
				{ label: 'Baixa manual', value: 'BAIXA MANUAL' },
				{ label: 'Quitação', value: 'QUITACAO' },
				{ label: 'Pix', value: 'PIX' },
				{ label: 'Baixa com desconto', value: 'BAIXA COM DESCONTO' },
				{ label: 'Renovação', value: 'RENOVACAO' },
				{ label: 'Outros', value: 'OUTROS' }
			]
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
		},
		exportarCsv(nomeArquivo, colunas, linhas) {
			const escapeCsv = (valor) => {
				const v = valor === null || valor === undefined ? '' : String(valor);
				return `"${v.replace(/"/g, '""')}"`;
			};
			const header = colunas.map((c) => escapeCsv(c.titulo)).join(';');
			const body = (linhas || []).map((linha) => {
				return colunas.map((c) => escapeCsv(typeof c.valor === 'function' ? c.valor(linha) : linha[c.valor])).join(';');
			}).join('\n');
			const csv = '\uFEFF' + header + '\n' + body;
			const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.setAttribute('download', `${nomeArquivo}.csv`);
			document.body.appendChild(link);
			link.click();
			link.remove();
			window.URL.revokeObjectURL(url);
		},
		filtrarAuditoriaPorTipo(lista) {
			if (!this.tipoAuditoriaSelecionado) {
				return lista || [];
			}
			return (lista || []).filter((item) => String(item?.tipo || 'OUTROS').toUpperCase() === this.tipoAuditoriaSelecionado);
		},
		exportarAuditoriaReceitaBruta() {
			this.exportarCsv(
				'auditoria-receita-bruta',
				[
					{ titulo: 'Origem', valor: 'origem' },
					{ titulo: 'Tipo', valor: 'tipo' },
					{ titulo: 'ID Mov.', valor: 'id' },
					{ titulo: 'Data', valor: (l) => this.formatDate(l.data) },
					{ titulo: 'Descricao', valor: 'descricao' },
					{ titulo: 'Banco', valor: 'banco' },
					{ titulo: 'Valor', valor: (l) => this.formatValorReal(l.valor || 0) }
				],
				this.filtrarAuditoriaPorTipo(this.relatorio?.detalhamento_receita_bruta || [])
			);
		},
		exportarAuditoriaValorRecebido() {
			this.exportarCsv(
				'auditoria-valor-recebido',
				[
					{ titulo: 'ID Mov.', valor: 'id' },
					{ titulo: 'Tipo', valor: 'tipo' },
					{ titulo: 'Data', valor: (l) => this.formatDate(l.data) },
					{ titulo: 'Cliente', valor: 'cliente' },
					{ titulo: 'Parcela', valor: 'parcela' },
					{ titulo: 'Descricao', valor: 'descricao' },
					{ titulo: 'Banco', valor: 'banco' },
					{ titulo: 'Valor Recebido', valor: (l) => this.formatValorReal(l.valor_recebido || 0) }
				],
				this.filtrarAuditoriaPorTipo(this.relatorio?.detalhamento_movimentacoes || [])
			);
		},
		exportarAuditoriaLucroReal() {
			this.exportarCsv(
				'auditoria-lucro-real',
				[
					{ titulo: 'ID Mov.', valor: 'id' },
					{ titulo: 'Tipo', valor: 'tipo' },
					{ titulo: 'Data', valor: (l) => this.formatDate(l.data) },
					{ titulo: 'Cliente', valor: 'cliente' },
					{ titulo: 'Parcela', valor: 'parcela' },
					{ titulo: 'Descricao', valor: 'descricao' },
					{ titulo: 'Lucro Real', valor: (l) => this.formatValorReal(l.lucro_real || 0) }
				],
				this.filtrarAuditoriaPorTipo(this.relatorio?.detalhamento_movimentacoes || [])
			);
		},
		exportarAuditoriaParcelasProcessadas() {
			this.exportarCsv(
				'auditoria-parcelas-processadas',
				[
					{ titulo: 'ID Mov.', valor: 'id' },
					{ titulo: 'Tipo', valor: 'tipo' },
					{ titulo: 'Parcela', valor: 'parcela' },
					{ titulo: 'Cliente', valor: 'cliente' },
					{ titulo: 'Data', valor: (l) => this.formatDate(l.data) },
					{ titulo: 'Descricao', valor: 'descricao' }
				],
				this.filtrarAuditoriaPorTipo(this.relatorio?.detalhamento_movimentacoes || [])
			);
		},
		exportarAuditoriaOutrasReceitas() {
			this.exportarCsv(
				'auditoria-outras-receitas',
				[
					{ titulo: 'ID Mov.', valor: 'id' },
					{ titulo: 'Tipo', valor: 'tipo' },
					{ titulo: 'Data', valor: (l) => this.formatDate(l.data) },
					{ titulo: 'Descricao', valor: 'descricao' },
					{ titulo: 'Banco', valor: 'banco' },
					{ titulo: 'Valor', valor: (l) => this.formatValorReal(l.valor || 0) }
				],
				this.filtrarAuditoriaPorTipo(this.relatorio?.detalhamento_outras_receitas || [])
			);
		}
	}
};
</script>

<style scoped>
.font-bold {
	font-weight: bold;
}
</style>

