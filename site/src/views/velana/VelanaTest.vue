<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs Velana</h5>
				<p class="text-color-secondary">Teste as funcionalidades de cobrança, checkout e transferência PIX da Velana</p>

				<TabView>
					<TabPanel header="Checkout">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="checkout_banco">Banco Velana *</label>
								<Dropdown 
									v-model="checkoutForm.banco_id" 
									:options="bancosVelana" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco Velana"
									class="w-full"
									:class="{ 'p-invalid': errors.checkout?.banco_id }"
								/>
								<small v-if="errors.checkout?.banco_id" class="text-red-500">{{ errors.checkout.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="checkout_cliente">Cliente *</label>
								<Dropdown 
									v-model="checkoutForm.cliente_id" 
									:options="clientes" 
									optionLabel="nome_completo" 
									optionValue="id"
									placeholder="Selecione um cliente"
									class="w-full"
									:class="{ 'p-invalid': errors.checkout?.cliente_id }"
								/>
								<small v-if="errors.checkout?.cliente_id" class="text-red-500">{{ errors.checkout.cliente_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="checkout_valor">Valor (R$) *</label>
								<InputNumber 
									v-model="checkoutForm.valor" 
									mode="decimal" 
									:min="0.01" 
									:maxFractionDigits="2"
									placeholder="0.00"
									class="w-full"
									:class="{ 'p-invalid': errors.checkout?.valor }"
								/>
								<small v-if="errors.checkout?.valor" class="text-red-500">{{ errors.checkout.valor[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="checkout_description">Descrição</label>
								<InputText 
									v-model="checkoutForm.description" 
									placeholder="Descrição do checkout"
									class="w-full"
								/>
							</div>

							<div class="field col-12">
								<Button 
									label="Testar Checkout" 
									icon="pi pi-send" 
									@click="testarCheckout"
									:loading="loading.checkout"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.checkout" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.checkout, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Cobrança">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="cobranca_banco">Banco Velana *</label>
								<Dropdown 
									v-model="cobrancaForm.banco_id" 
									:options="bancosVelana" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco Velana"
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
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.cobranca, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Transferência PIX">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="transferencia_banco">Banco Velana *</label>
								<Dropdown 
									v-model="transferenciaForm.banco_id" 
									:options="bancosVelana" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco Velana"
									class="w-full"
									:class="{ 'p-invalid': errors.transferencia?.banco_id }"
								/>
								<small v-if="errors.transferencia?.banco_id" class="text-red-500">{{ errors.transferencia.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_valor">Valor (R$) *</label>
								<InputNumber 
									v-model="transferenciaForm.valor" 
									mode="decimal" 
									:min="0.01" 
									:maxFractionDigits="2"
									placeholder="0.00"
									class="w-full"
									:class="{ 'p-invalid': errors.transferencia?.valor }"
								/>
								<small v-if="errors.transferencia?.valor" class="text-red-500">{{ errors.transferencia.valor[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_pix_key">Chave PIX *</label>
								<InputText 
									v-model="transferenciaForm.pix_key" 
									placeholder="CPF, CNPJ, Email, Telefone ou Chave Aleatória"
									class="w-full"
									:class="{ 'p-invalid': errors.transferencia?.pix_key }"
								/>
								<small v-if="errors.transferencia?.pix_key" class="text-red-500">{{ errors.transferencia.pix_key[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_description">Descrição</label>
								<InputText 
									v-model="transferenciaForm.description" 
									placeholder="Descrição da transferência"
									class="w-full"
								/>
							</div>

							<div class="field col-12">
								<Button 
									label="Testar Transferência" 
									icon="pi pi-send" 
									@click="testarTransferencia"
									:loading="loading.transferencia"
									class="w-full"
									severity="danger"
								/>
							</div>

							<div v-if="resultado.transferencia" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.transferencia, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Buscar Checkout">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="buscar_checkout_banco">Banco Velana *</label>
								<Dropdown 
									v-model="buscarCheckoutForm.banco_id" 
									:options="bancosVelana" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco Velana"
									class="w-full"
									:class="{ 'p-invalid': errors.buscarCheckout?.banco_id }"
								/>
								<small v-if="errors.buscarCheckout?.banco_id" class="text-red-500">{{ errors.buscarCheckout.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="buscar_checkout_id">ID do Checkout *</label>
								<InputText 
									v-model="buscarCheckoutForm.checkout_id" 
									placeholder="ID do checkout"
									class="w-full"
									:class="{ 'p-invalid': errors.buscarCheckout?.checkout_id }"
								/>
								<small v-if="errors.buscarCheckout?.checkout_id" class="text-red-500">{{ errors.buscarCheckout.checkout_id[0] }}</small>
							</div>

							<div class="field col-12">
								<Button 
									label="Buscar Checkout" 
									icon="pi pi-search" 
									@click="buscarCheckout"
									:loading="loading.buscarCheckout"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.buscarCheckout" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.buscarCheckout, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Buscar Transação">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="buscar_transacao_banco">Banco Velana *</label>
								<Dropdown 
									v-model="buscarTransacaoForm.banco_id" 
									:options="bancosVelana" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco Velana"
									class="w-full"
									:class="{ 'p-invalid': errors.buscarTransacao?.banco_id }"
								/>
								<small v-if="errors.buscarTransacao?.banco_id" class="text-red-500">{{ errors.buscarTransacao.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="buscar_transacao_id">ID da Transação *</label>
								<InputText 
									v-model="buscarTransacaoForm.transaction_id" 
									placeholder="ID da transação"
									class="w-full"
									:class="{ 'p-invalid': errors.buscarTransacao?.transaction_id }"
								/>
								<small v-if="errors.buscarTransacao?.transaction_id" class="text-red-500">{{ errors.buscarTransacao.transaction_id[0] }}</small>
							</div>

							<div class="field col-12">
								<Button 
									label="Buscar Transação" 
									icon="pi pi-search" 
									@click="buscarTransacao"
									:loading="loading.buscarTransacao"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.buscarTransacao" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.buscarTransacao, null, 2) }}</pre>
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
	name: 'VelanaTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosVelana: ref([]),
			clientes: ref([]),
			loading: {
				checkout: false,
				cobranca: false,
				transferencia: false,
				buscarCheckout: false,
				buscarTransacao: false
			},
			errors: {
				checkout: {},
				cobranca: {},
				transferencia: {},
				buscarCheckout: {},
				buscarTransacao: {}
			},
			resultado: {
				checkout: null,
				cobranca: null,
				transferencia: null,
				buscarCheckout: null,
				buscarTransacao: null
			},
			checkoutForm: {
				banco_id: null,
				cliente_id: null,
				valor: null,
				description: null
			},
			cobrancaForm: {
				banco_id: null,
				cliente_id: null,
				valor: null,
				due_date: null
			},
			transferenciaForm: {
				banco_id: null,
				valor: null,
				pix_key: null,
				description: null
			},
			buscarCheckoutForm: {
				banco_id: null,
				checkout_id: null
			},
			buscarTransacaoForm: {
				banco_id: null,
				transaction_id: null
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
				this.bancosVelana = response.data.data.filter(b => (b.bank_type || 'normal') === 'velana');
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
		async testarCheckout() {
			this.loading.checkout = true;
			this.errors.checkout = {};
			this.resultado.checkout = null;

			try {
				const response = await axios.post(`${apiPath}/velana/teste/checkout`, this.checkoutForm);
				this.resultado.checkout = response.data;
				
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
					this.errors.checkout = error.response.data.errors;
				}
				this.resultado.checkout = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao testar checkout',
					life: 3000
				});
			} finally {
				this.loading.checkout = false;
			}
		},
		async testarCobranca() {
			this.loading.cobranca = true;
			this.errors.cobranca = {};
			this.resultado.cobranca = null;

			try {
				const formData = { ...this.cobrancaForm };
				if (formData.due_date) {
					formData.due_date = formData.due_date.toISOString().split('T')[0];
				}

				const response = await axios.post(`${apiPath}/velana/teste/cobranca`, formData);
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
		async testarTransferencia() {
			this.loading.transferencia = true;
			this.errors.transferencia = {};
			this.resultado.transferencia = null;

			try {
				const response = await axios.post(`${apiPath}/velana/teste/transferencia`, this.transferenciaForm);
				this.resultado.transferencia = response.data;
				
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
					this.errors.transferencia = error.response.data.errors;
				}
				this.resultado.transferencia = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao testar transferência',
					life: 3000
				});
			} finally {
				this.loading.transferencia = false;
			}
		},
		async buscarCheckout() {
			this.loading.buscarCheckout = true;
			this.errors.buscarCheckout = {};
			this.resultado.buscarCheckout = null;

			try {
				const response = await axios.post(`${apiPath}/velana/teste/buscar-checkout`, this.buscarCheckoutForm);
				this.resultado.buscarCheckout = response.data;
				
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
					this.errors.buscarCheckout = error.response.data.errors;
				}
				this.resultado.buscarCheckout = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao buscar checkout',
					life: 3000
				});
			} finally {
				this.loading.buscarCheckout = false;
			}
		},
		async buscarTransacao() {
			this.loading.buscarTransacao = true;
			this.errors.buscarTransacao = {};
			this.resultado.buscarTransacao = null;

			try {
				const response = await axios.post(`${apiPath}/velana/teste/buscar-transacao`, this.buscarTransacaoForm);
				this.resultado.buscarTransacao = response.data;
				
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
					this.errors.buscarTransacao = error.response.data.errors;
				}
				this.resultado.buscarTransacao = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao buscar transação',
					life: 3000
				});
			} finally {
				this.loading.buscarTransacao = false;
			}
		}
	}
};
</script>

