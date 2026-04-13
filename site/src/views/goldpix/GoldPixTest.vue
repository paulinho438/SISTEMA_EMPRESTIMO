<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs GoldPix</h5>
				<p class="text-color-secondary">
					Teste cobrança PIX, saque e saldo. Documentação:
					<a href="https://api.goldpix.tech/docs" target="_blank" rel="noopener">api.goldpix.tech/docs</a>
				</p>

				<TabView>
					<TabPanel header="Cobrança PIX">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="cobranca_banco">Banco GoldPix *</label>
								<Dropdown
									v-model="cobrancaForm.banco_id"
									:options="bancosGoldpix"
									optionLabel="name"
									optionValue="id"
									placeholder="Selecione um banco GoldPix"
									class="w-full"
									:class="{ 'p-invalid': errors.cobranca?.banco_id }"
								/>
								<small v-if="errors.cobranca?.banco_id" class="text-red-500">{{ errors.cobranca.banco_id[0] }}</small>
							</div>
							<div class="field col-12 md:col-6">
								<label for="cobranca_cliente">Cliente *</label>
								<Dropdown
									v-model="cobrancaForm.cliente_id"
									:options="clientes"
									optionLabel="nome_completo"
									optionValue="id"
									placeholder="Selecione um cliente"
									class="w-full"
									:class="{ 'p-invalid': errors.cobranca?.cliente_id }"
								/>
								<small v-if="errors.cobranca?.cliente_id" class="text-red-500">{{ errors.cobranca.cliente_id[0] }}</small>
							</div>
							<div class="field col-12 md:col-6">
								<label for="cobranca_valor">Valor (R$) *</label>
								<InputNumber
									v-model="cobrancaForm.valor"
									mode="decimal"
									:min="0.01"
									:maxFractionDigits="2"
									placeholder="0.00"
									class="w-full"
									:class="{ 'p-invalid': errors.cobranca?.valor }"
								/>
								<small v-if="errors.cobranca?.valor" class="text-red-500">{{ errors.cobranca.valor[0] }}</small>
							</div>
							<div class="field col-12">
								<Button
									label="Testar cobrança"
									icon="pi pi-credit-card"
									@click="testarCobranca"
									:loading="loading.cobranca"
									class="w-full"
								/>
							</div>
							<div v-if="resultado.cobranca" class="field col-12">
								<Divider />
								<h6>Resultado</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.cobranca, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Saque">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="saque_banco">Banco GoldPix *</label>
								<Dropdown
									v-model="saqueForm.banco_id"
									:options="bancosGoldpix"
									optionLabel="name"
									optionValue="id"
									placeholder="Selecione um banco GoldPix"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.banco_id }"
								/>
								<small v-if="errors.saque?.banco_id" class="text-red-500">{{ errors.saque.banco_id[0] }}</small>
							</div>
							<div class="field col-12 md:col-6">
								<label for="saque_valor">Valor (R$) *</label>
								<InputNumber
									v-model="saqueForm.valor"
									mode="decimal"
									:min="0.01"
									:maxFractionDigits="2"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.valor }"
								/>
								<small v-if="errors.saque?.valor" class="text-red-500">{{ errors.saque.valor[0] }}</small>
							</div>
							<div class="field col-12 md:col-6">
								<label for="saque_pix_key">Chave PIX *</label>
								<InputText v-model="saqueForm.pix_key" class="w-full" :class="{ 'p-invalid': errors.saque?.pix_key }" />
								<small v-if="errors.saque?.pix_key" class="text-red-500">{{ errors.saque.pix_key[0] }}</small>
							</div>
							<div class="field col-12 md:col-6">
								<label for="saque_pix_key_type">Tipo da chave (opcional)</label>
								<Dropdown
									v-model="saqueForm.pix_key_type"
									:options="pixKeyTypeOptions"
									optionLabel="label"
									optionValue="value"
									placeholder="Inferir automaticamente"
									class="w-full"
									showClear
								/>
							</div>
							<div class="field col-12 md:col-6">
								<label for="saque_cpf_cnpj">CPF/CNPJ do destinatário</label>
								<InputText
									v-model="saqueForm.cpf_cnpj_destino"
									placeholder="Obrigatório para e-mail, telefone ou chave aleatória"
									class="w-full"
								/>
							</div>
							<div class="field col-12 md:col-6">
								<label for="saque_external_id">External ID (opcional)</label>
								<InputText v-model="saqueForm.external_id" class="w-full" />
							</div>
							<div class="field col-12">
								<Button
									label="Testar saque"
									icon="pi pi-send"
									@click="testarSaque"
									:loading="loading.saque"
									class="w-full"
									severity="danger"
								/>
							</div>
							<div v-if="resultado.saque" class="field col-12">
								<Divider />
								<h6>Resultado</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.saque, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Consultar saldo">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="saldo_banco">Banco GoldPix *</label>
								<Dropdown
									v-model="saldoForm.banco_id"
									:options="bancosGoldpix"
									optionLabel="name"
									optionValue="id"
									placeholder="Selecione um banco GoldPix"
									class="w-full"
									:class="{ 'p-invalid': errors.saldo?.banco_id }"
								/>
								<small v-if="errors.saldo?.banco_id" class="text-red-500">{{ errors.saldo.banco_id[0] }}</small>
							</div>
							<div class="field col-12">
								<Button
									label="Consultar saldo"
									icon="pi pi-wallet"
									@click="consultarSaldo"
									:loading="loading.saldo"
									class="w-full"
								/>
							</div>
							<div v-if="resultado.saldo" class="field col-12">
								<Divider />
								<h6>Resultado</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.saldo, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>
				</TabView>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import BancoService from '@/service/BancoService';
import ClientService from '@/service/ClientService';
import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default {
	name: 'GoldPixTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosGoldpix: ref([]),
			clientes: ref([]),
			loading: { cobranca: false, saque: false, saldo: false },
			errors: { cobranca: {}, saque: {}, saldo: {} },
			resultado: { cobranca: null, saque: null, saldo: null },
			pixKeyTypeOptions: [
				{ label: 'CPF', value: 'cpf' },
				{ label: 'CNPJ', value: 'cnpj' },
				{ label: 'E-mail', value: 'email' },
				{ label: 'Telefone', value: 'phone' },
				{ label: 'Chave aleatória', value: 'random' }
			],
			cobrancaForm: { banco_id: null, cliente_id: null, valor: null },
			saqueForm: {
				banco_id: null,
				valor: null,
				pix_key: null,
				pix_key_type: null,
				cpf_cnpj_destino: '',
				external_id: null
			},
			saldoForm: { banco_id: null }
		};
	},
	mounted() {
		this.carregarBancos();
		this.carregarClientes();
	},
	methods: {
		async carregarBancos() {
			try {
				const response = await this.bancoService.getAll();
				this.bancosGoldpix = (response.data.data || []).filter((b) => (b.bank_type || 'normal') === 'goldpix');
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar bancos: ' + error.message,
					life: 3000
				});
			}
		},
		async carregarClientes() {
			try {
				const response = await this.clientService.getAll();
				this.clientes = response.data.data || [];
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar clientes: ' + error.message,
					life: 3000
				});
			}
		},
		async testarCobranca() {
			this.loading.cobranca = true;
			this.errors.cobranca = {};
			this.resultado.cobranca = null;
			try {
				const response = await axios.post(`${apiPath}/goldpix/teste/cobranca`, { ...this.cobrancaForm });
				this.resultado.cobranca = response.data;
				this.toast.add({
					severity: response.data.success ? ToastSeverity.SUCCESS : ToastSeverity.WARN,
					detail: response.data.message || (response.data.success ? 'OK' : 'Aviso'),
					life: 3000
				});
			} catch (error) {
				if (error.response?.data?.errors) {
					this.errors.cobranca = error.response.data.errors;
				}
				this.resultado.cobranca = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao testar cobrança',
					life: 3000
				});
			} finally {
				this.loading.cobranca = false;
			}
		},
		async testarSaque() {
			this.loading.saque = true;
			this.errors.saque = {};
			this.resultado.saque = null;
			try {
				const body = {
					banco_id: this.saqueForm.banco_id,
					valor: this.saqueForm.valor,
					pix_key: this.saqueForm.pix_key,
					external_id: this.saqueForm.external_id || undefined
				};
				if (this.saqueForm.pix_key_type) {
					body.pix_key_type = this.saqueForm.pix_key_type;
				}
				if (this.saqueForm.cpf_cnpj_destino) {
					body.cpf_cnpj_destino = this.saqueForm.cpf_cnpj_destino;
				}
				const response = await axios.post(`${apiPath}/goldpix/teste/saque`, body);
				this.resultado.saque = response.data;
				this.toast.add({
					severity: response.data.success ? ToastSeverity.SUCCESS : ToastSeverity.WARN,
					detail: response.data.message || (response.data.success ? 'OK' : 'Aviso'),
					life: 3000
				});
			} catch (error) {
				if (error.response?.data?.errors) {
					this.errors.saque = error.response.data.errors;
				}
				this.resultado.saque = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao testar saque',
					life: 3000
				});
			} finally {
				this.loading.saque = false;
			}
		},
		async consultarSaldo() {
			this.loading.saldo = true;
			this.errors.saldo = {};
			this.resultado.saldo = null;
			try {
				const response = await axios.post(`${apiPath}/goldpix/teste/consultar-saldo`, this.saldoForm);
				this.resultado.saldo = response.data;
				this.toast.add({
					severity: response.data.success ? ToastSeverity.SUCCESS : ToastSeverity.WARN,
					detail: response.data.message || (response.data.success ? 'OK' : 'Aviso'),
					life: 3000
				});
			} catch (error) {
				if (error.response?.data?.errors) {
					this.errors.saldo = error.response.data.errors;
				}
				this.resultado.saldo = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao consultar saldo',
					life: 3000
				});
			} finally {
				this.loading.saldo = false;
			}
		}
	}
};
</script>
