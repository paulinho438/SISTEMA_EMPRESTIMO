<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs APIX (apixpag.com)</h5>
				<p class="text-color-secondary">Teste os endpoints de cobrança PIX, transferência e saldo da APIX. Documentação: <a href="https://app.apixpag.com/docs/deposits" target="_blank" rel="noopener">app.apixpag.com/docs/deposits</a></p>

				<TabView>
					<TabPanel header="Cobrança PIX (Depósito)">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="cobranca_banco">Banco APIX *</label>
								<Dropdown 
									v-model="cobrancaForm.banco_id" 
									:options="bancosAPIX" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco APIX"
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

							<div class="field col-12 md:col-6">
								<label for="cobranca_due_date">Data de Vencimento</label>
								<Calendar 
									v-model="cobrancaForm.due_date" 
									dateFormat="yy-mm-dd"
									:minDate="new Date()"
									placeholder="Selecione a data"
									class="w-full"
								/>
							</div>

							<div class="field col-12">
								<Button 
									label="Testar Cobrança" 
									icon="pi pi-credit-card" 
									@click="testarCobranca"
									:loading="loading.cobranca"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.cobranca" class="field col-12">
								<Divider />
								<div v-if="resultado.cobranca?.last_response?.curl" class="mb-3">
									<h6>CURL utilizado:</h6>
									<pre class="p-3 border-round surface-ground font-mono text-sm" style="max-height: 200px; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ resultado.cobranca.last_response.curl }}</pre>
									<Button label="Copiar CURL" icon="pi pi-copy" class="p-button-outlined p-button-sm mt-2" @click="copiarCurl(resultado.cobranca.last_response.curl)" />
								</div>
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.cobranca, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Saque (Withdraw)">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="saque_banco">Banco APIX *</label>
								<Dropdown 
									v-model="saqueForm.banco_id" 
									:options="bancosAPIX" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco APIX"
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
									placeholder="0.00"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.valor }"
								/>
								<small v-if="errors.saque?.valor" class="text-red-500">{{ errors.saque.valor[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="saque_pix_key">Chave PIX *</label>
								<InputText 
									v-model="saqueForm.pix_key" 
									placeholder="Email, CPF, CNPJ, telefone ou chave aleatória"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.pix_key }"
								/>
								<small v-if="errors.saque?.pix_key" class="text-red-500">{{ errors.saque.pix_key[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="saque_key_type">Tipo da Chave *</label>
								<Dropdown 
									v-model="saqueForm.key_type" 
									:options="keyTypeOptions" 
									optionLabel="label" 
									optionValue="value"
									placeholder="Selecione"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.key_type }"
								/>
								<small v-if="errors.saque?.key_type" class="text-red-500">{{ errors.saque.key_type[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="saque_key_document">Documento (key_document) *</label>
								<InputText 
									v-model="saqueForm.key_document" 
									placeholder="Ex: 12345678901"
									class="w-full"
									:class="{ 'p-invalid': errors.saque?.key_document }"
								/>
								<small v-if="errors.saque?.key_document" class="text-red-500">{{ errors.saque.key_document[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="saque_external_id">External ID (opcional)</label>
								<InputText 
									v-model="saqueForm.external_id" 
									placeholder="Ex: saque_7d4e9f2a"
									class="w-full"
								/>
							</div>

							<div class="field col-12 md:col-6">
								<label for="saque_client_callback_url">URL de callback (opcional)</label>
								<InputText 
									v-model="saqueForm.client_callback_url" 
									placeholder="https://seuservidor.com/webhook/saques"
									class="w-full"
								/>
							</div>

							<div class="field col-12">
								<Button 
									label="Testar Saque" 
									icon="pi pi-send" 
									@click="testarSaque"
									:loading="loading.saque"
									class="w-full"
									severity="danger"
								/>
							</div>

							<div v-if="resultado.saque" class="field col-12">
								<Divider />
								<div v-if="resultado.saque?.last_response?.curl" class="mb-3">
									<h6>CURL utilizado:</h6>
									<pre class="p-3 border-round surface-ground font-mono text-sm" style="max-height: 200px; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ resultado.saque.last_response.curl }}</pre>
									<Button label="Copiar CURL" icon="pi pi-copy" class="p-button-outlined p-button-sm mt-2" @click="copiarCurl(resultado.saque.last_response.curl)" />
								</div>
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.saque, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Consultar Saldo">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="saldo_banco">Banco APIX *</label>
								<Dropdown 
									v-model="saldoForm.banco_id" 
									:options="bancosAPIX" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco APIX"
									class="w-full"
									:class="{ 'p-invalid': errors.saldo?.banco_id }"
								/>
								<small v-if="errors.saldo?.banco_id" class="text-red-500">{{ errors.saldo.banco_id[0] }}</small>
							</div>

							<div class="field col-12">
								<Button 
									label="Consultar Saldo" 
									icon="pi pi-wallet" 
									@click="consultarSaldo"
									:loading="loading.saldo"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.saldo" class="field col-12">
								<Divider />
								<div v-if="resultado.saldo?.last_response?.curl" class="mb-3">
									<h6>CURL utilizado (Consulta Saldo):</h6>
									<pre class="p-3 border-round surface-ground font-mono text-sm" style="max-height: 200px; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ resultado.saldo.last_response.curl }}</pre>
									<Button label="Copiar CURL" icon="pi pi-copy" class="p-button-outlined p-button-sm mt-2" @click="copiarCurl(resultado.saldo.last_response.curl)" />
								</div>
								<h6>Resultado:</h6>
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
	name: 'ApixTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosAPIX: ref([]),
			clientes: ref([]),
			loading: {
				cobranca: false,
				saque: false,
				saldo: false
			},
			errors: {
				cobranca: {},
				saque: {},
				saldo: {}
			},
			resultado: {
				cobranca: null,
				saque: null,
				saldo: null
			},
			keyTypeOptions: [
				{ label: 'Email', value: 'email' },
				{ label: 'CPF', value: 'cpf' },
				{ label: 'CNPJ', value: 'cnpj' },
				{ label: 'Telefone', value: 'phone' },
				{ label: 'Chave aleatória (EVP)', value: 'evp' }
			],
			cobrancaForm: {
				banco_id: null,
				cliente_id: null,
				valor: null,
				due_date: null
			},
			saqueForm: {
				banco_id: null,
				valor: null,
				pix_key: null,
				key_type: null,
				key_document: null,
				external_id: null,
				client_callback_url: null
			},
			saldoForm: {
				banco_id: null
			}
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
				this.bancosAPIX = (response.data.data || []).filter(b => (b.bank_type || 'normal') === 'apix');
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
				const formData = { ...this.cobrancaForm };
				if (formData.due_date) {
					formData.due_date = formData.due_date.toISOString ? formData.due_date.toISOString().split('T')[0] : formData.due_date;
				}

				const response = await axios.post(`${apiPath}/apix/teste/cobranca`, formData);
				this.resultado.cobranca = response.data;

				if (response.data.success) {
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: response.data.message,
						life: 3000
					});
				} else {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: response.data.message,
						life: 3000
					});
				}
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
				const response = await axios.post(`${apiPath}/apix/teste/saque`, this.saqueForm);
				this.resultado.saque = response.data;

				if (response.data.success) {
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: response.data.message,
						life: 3000
					});
				} else {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: response.data.message,
						life: 3000
					});
				}
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
		copiarCurl(curl) {
			if (!curl) return;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(curl).then(() => {
					this.toast.add({ severity: ToastSeverity.SUCCESS, detail: 'CURL copiado!', life: 2000 });
				}).catch(() => {
					this.toast.add({ severity: ToastSeverity.WARN, detail: 'Copie o CURL manualmente.', life: 2000 });
				});
			} else {
				this.toast.add({ severity: ToastSeverity.WARN, detail: 'Copie o CURL manualmente.', life: 2000 });
			}
		},
		async consultarSaldo() {
			this.loading.saldo = true;
			this.errors.saldo = {};
			this.resultado.saldo = null;

			try {
				const response = await axios.post(`${apiPath}/apix/teste/consultar-saldo`, this.saldoForm);
				this.resultado.saldo = response.data;

				if (response.data.success) {
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: response.data.message,
						life: 3000
					});
				} else {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: response.data.message,
						life: 3000
					});
				}
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
