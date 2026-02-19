<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Relatórios</h5>

				<!-- Abas -->
				<div class="flex gap-2 mt-3 mb-4">
					<Button
						:label="'Sumário dos Contratos Ativos'"
						:outlined="abaAtiva !== 'sumario'"
						@click="abaAtiva = 'sumario'"
					/>
					<Button
						label="Mensal"
						:outlined="abaAtiva !== 'mensal'"
						@click="abaAtiva = 'mensal'"
					/>
					<Button
						label="Sumário Clientes"
						:outlined="abaAtiva !== 'clientes'"
						@click="abaAtiva = 'clientes'"
					/>
				</div>

				<!-- Conteúdo Sumário / Mensal -->
				<div v-if="abaAtiva === 'sumario' || abaAtiva === 'mensal'">
					<Divider />

					<!-- Filtros -->
					<div class="formgrid grid mt-4">
						<div class="field col-12 md:col-3">
							<label for="mes">Mês</label>
							<Dropdown
								v-model="filtros.mes"
								:options="meses"
								optionLabel="label"
								optionValue="value"
								placeholder="Selecione o mês"
								class="w-full"
							/>
						</div>
						<div class="field col-12 md:col-3">
							<label for="ano">Ano</label>
							<Dropdown
								v-model="filtros.ano"
								:options="anos"
								placeholder="Selecione o ano"
								class="w-full"
							/>
						</div>
						<div class="field col-12 md:col-3 flex align-items-end">
							<Button
								label="Gerar"
								icon="pi pi-search"
								@click="gerarRelatorio"
								:loading="loading"
								class="p-button-primary"
							/>
						</div>
					</div>

					<!-- Métricas -->
					<div v-if="relatorio" class="mt-5">
						<div class="flex flex-column gap-3">
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>Total de Recebimentos no Mês</span>
								<span class="font-bold">{{ formatValorReal(relatorio.total_recebimentos) }}</span>
							</div>
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>Total de Amortização</span>
								<span class="font-bold">{{ formatValorReal(relatorio.total_amortizacao) }}</span>
							</div>
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>Total de Juros (Remuneratórios e Mora)</span>
								<span class="font-bold">{{ formatValorReal(relatorio.total_juros) }}</span>
							</div>
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>Descontos aplicados</span>
								<span class="font-bold">{{ formatValorReal(relatorio.descontos_aplicados) }}</span>
							</div>

							<!-- IRPJ e CSLL: exibir apenas em Março, Junho, Setembro e Dezembro -->
							<template v-if="exibirIRPJCSLL">
								<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
									<span>IRPJ *</span>
									<span class="font-bold">{{ formatValorReal(relatorio.irpj?.total) }}</span>
								</div>
								<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
									<span>Adicional do IRPJ *</span>
									<span class="font-bold">{{ formatValorReal(relatorio.irpj?.adicional) }}</span>
								</div>
								<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
									<span>CSLL *</span>
									<span class="font-bold">{{ formatValorReal(relatorio.csll) }}</span>
								</div>
							</template>

							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>COFINS</span>
								<span class="font-bold">{{ formatValorReal(relatorio.cofins) }}</span>
							</div>
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>PIS</span>
								<span class="font-bold">{{ formatValorReal(relatorio.pis) }}</span>
							</div>
							<div
								v-if="Number(relatorio.iof_total_mes || 0) > 0"
								class="flex justify-content-between align-items-center p-2 border-round surface-ground"
							>
								<span>IOF Total (operações feitas este mês)</span>
								<span class="font-bold">{{ formatValorReal(relatorio.iof_total_mes) }}</span>
							</div>
							<div class="flex justify-content-between align-items-center p-2 border-round surface-ground">
								<span>Valor dos títulos atrasados (vencidos neste mês)</span>
								<span class="font-bold">{{ formatValorReal(relatorio.titulos_atrasados) }}</span>
							</div>
						</div>

						<!-- Observações -->
						<div class="mt-4 p-3 border-round surface-ground" style="background-color: #fff8f0;">
							<div class="flex align-items-start gap-2">
								<i class="pi pi-exclamation-triangle text-orange-500 mt-1"></i>
								<div>
									<div class="font-semibold text-orange-700 mb-2">* Observações:</div>
									<ol class="m-0 pl-3 text-orange-800">
										<li>Impostos calculados considerando opção por Lucro Presumido</li>
										<li>IRPJ e CSLL são calculados trimestralmente (em Março, Junho, Setembro e Dezembro)</li>
										<li>Impostos têm vencimento no mês seguinte ao cálculo</li>
										<li v-if="exibirIRPJCSLL">Este é um mês de apuração trimestral - IRPJ e CSLL estão sendo exibidos</li>
									</ol>
								</div>
							</div>
						</div>

						<!-- Botão Baixar CSV -->
						<div class="mt-4">
							<Button
								label="Baixar CSV"
								icon="pi pi-download"
								@click="baixarCSV"
								:loading="loadingCSV"
								class="p-button-outlined"
							/>
						</div>
					</div>
				</div>

				<!-- Conteúdo Sumário Clientes (placeholder) -->
				<div v-if="abaAtiva === 'clientes'" class="mt-4">
					<Divider />
					<p class="text-color-secondary">Sumário Clientes - em desenvolvimento</p>
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
import axios from '@/plugins/axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default {
	name: 'SumarioContratosAtivos',
	setup() {
		return {
			toast: useToast(),
			relatorioFiscalService: new RelatorioFiscalService()
		};
	},
	data() {
		const anoAtual = new Date().getFullYear();
		const anos = Array.from({ length: 10 }, (_, i) => anoAtual - 2 + i);
		const meses = [
			{ label: 'Janeiro', value: 1 },
			{ label: 'Fevereiro', value: 2 },
			{ label: 'Março', value: 3 },
			{ label: 'Abril', value: 4 },
			{ label: 'Maio', value: 5 },
			{ label: 'Junho', value: 6 },
			{ label: 'Julho', value: 7 },
			{ label: 'Agosto', value: 8 },
			{ label: 'Setembro', value: 9 },
			{ label: 'Outubro', value: 10 },
			{ label: 'Novembro', value: 11 },
			{ label: 'Dezembro', value: 12 }
		];

		return {
			abaAtiva: 'sumario',
			loading: ref(false),
			loadingCSV: ref(false),
			filtros: {
				mes: new Date().getMonth() + 1,
				ano: anoAtual
			},
			meses,
			anos,
			relatorio: null
		};
	},
	computed: {
		// Março (3), Junho (6), Setembro (9) e Dezembro (12) são meses de apuração trimestral
		exibirIRPJCSLL() {
			const mes = Number(this.filtros.mes);
			return [3, 6, 9, 12].includes(mes);
		}
	},
	methods: {
		formatValorReal(valor) {
			if (valor == null || valor === undefined) return 'R$ 0,00';
			return new Intl.NumberFormat('pt-BR', {
				style: 'currency',
				currency: 'BRL'
			}).format(valor);
		},
		async gerarRelatorio() {
			this.loading = true;
			this.relatorio = null;

			try {
				const mes = String(this.filtros.mes).padStart(2, '0');
				const mesAno = `${this.filtros.ano}-${mes}`;
				const response = await this.relatorioFiscalService.relatorioMensal(mesAno, 'presumido');

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
		async baixarCSV() {
			if (!this.relatorio) return;

			this.loadingCSV = true;
			try {
				const mes = String(this.filtros.mes).padStart(2, '0');
				const mesAno = `${this.filtros.ano}-${mes}`;

				const response = await axios.get(`${apiPath}/relatorio-fiscal/sumario-csv`, {
					params: { mes: mesAno, ano: this.filtros.ano },
					responseType: 'blob'
				});

				const url = window.URL.createObjectURL(new Blob([response.data]));
				const link = document.createElement('a');
				link.href = url;
				link.setAttribute('download', `sumario-contratos-ativos-${mesAno}.csv`);
				document.body.appendChild(link);
				link.click();
				link.remove();
				window.URL.revokeObjectURL(url);

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: 'CSV baixado com sucesso',
					life: 3000
				});
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao baixar CSV',
					life: 3000
				});
			} finally {
				this.loadingCSV = false;
			}
		}
	}
};
</script>
