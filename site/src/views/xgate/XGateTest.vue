<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs XGate</h5>
				<p class="text-color-secondary">Teste as funcionalidades de cobrança e transferência PIX da XGate</p>

				<TabView>
					<TabPanel header="Cobrança PIX">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="cobranca_banco">Banco XGate *</label>
								<Dropdown 
									v-model="cobrancaForm.banco_id" 
									:options="bancosXGate" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco XGate"
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

					<TabPanel header="Transferência PIX (Chave)">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="transferencia_banco">Banco XGate *</label>
								<Dropdown 
									v-model="transferenciaForm.banco_id" 
									:options="bancosXGate" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco XGate"
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

					<TabPanel header="Transferência PIX (Cliente)">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="transferencia_cliente_banco">Banco XGate *</label>
								<Dropdown 
									v-model="transferenciaClienteForm.banco_id" 
									:options="bancosXGate" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco XGate"
									class="w-full"
									:class="{ 'p-invalid': errors.transferenciaCliente?.banco_id }"
								/>
								<small v-if="errors.transferenciaCliente?.banco_id" class="text-red-500">{{ errors.transferenciaCliente.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_cliente_cliente">Cliente *</label>
								<Dropdown 
									v-model="transferenciaClienteForm.cliente_id" 
									:options="clientesComPix" 
									optionLabel="nome_completo" 
									optionValue="id"
									placeholder="Selecione um cliente com PIX"
									class="w-full"
									:class="{ 'p-invalid': errors.transferenciaCliente?.cliente_id }"
								/>
								<small v-if="errors.transferenciaCliente?.cliente_id" class="text-red-500">{{ errors.transferenciaCliente.cliente_id[0] }}</small>
								<small class="text-gray-500">Apenas clientes com chave PIX cadastrada</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_cliente_valor">Valor (R$) *</label>
								<InputNumber 
									v-model="transferenciaClienteForm.valor" 
									mode="decimal" 
									:min="0.01" 
									:maxFractionDigits="2"
									placeholder="0.00"
									class="w-full"
									:class="{ 'p-invalid': errors.transferenciaCliente?.valor }"
								/>
								<small v-if="errors.transferenciaCliente?.valor" class="text-red-500">{{ errors.transferenciaCliente.valor[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="transferencia_cliente_description">Descrição</label>
								<InputText 
									v-model="transferenciaClienteForm.description" 
									placeholder="Descrição da transferência"
									class="w-full"
								/>
							</div>

							<div class="field col-12">
								<Button 
									label="Testar Transferência" 
									icon="pi pi-send" 
									@click="testarTransferenciaCliente"
									:loading="loading.transferenciaCliente"
									class="w-full"
									severity="danger"
								/>
							</div>

							<div v-if="resultado.transferenciaCliente" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.transferenciaCliente, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Consultar Saldo">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="saldo_banco">Banco XGate *</label>
								<Dropdown 
									v-model="saldoForm.banco_id" 
									:options="bancosXGate" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco XGate"
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
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.saldo, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>

					<TabPanel header="Criar/Atualizar Cliente">
						<div class="formgrid grid mt-4">
							<div class="field col-12 md:col-6">
								<label for="cliente_banco">Banco XGate *</label>
								<Dropdown 
									v-model="clienteForm.banco_id" 
									:options="bancosXGate" 
									optionLabel="name" 
									optionValue="id"
									placeholder="Selecione um banco XGate"
									class="w-full"
									:class="{ 'p-invalid': errors.cliente?.banco_id }"
								/>
								<small v-if="errors.cliente?.banco_id" class="text-red-500">{{ errors.cliente.banco_id[0] }}</small>
							</div>

							<div class="field col-12 md:col-6">
								<label for="cliente_cliente">Cliente *</label>
								<Dropdown 
									v-model="clienteForm.cliente_id" 
									:options="clientes" 
									optionLabel="nome_completo" 
									optionValue="id"
									placeholder="Selecione um cliente"
									class="w-full"
									:class="{ 'p-invalid': errors.cliente?.cliente_id }"
								/>
								<small v-if="errors.cliente?.cliente_id" class="text-red-500">{{ errors.cliente.cliente_id[0] }}</small>
							</div>

							<div class="field col-12">
								<Button 
									label="Criar/Atualizar Cliente" 
									icon="pi pi-user-plus" 
									@click="criarOuAtualizarCliente"
									:loading="loading.cliente"
									class="w-full"
								/>
							</div>

							<div v-if="resultado.cliente" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 400px; overflow: auto;">{{ JSON.stringify(resultado.cliente, null, 2) }}</pre>
							</div>
						</div>
					</TabPanel>
				</TabView>
			</div>
		</div>
	</div>
</template>

<script>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import BancoService from '@/service/BancoService';
import ClientService from '@/service/ClientService';
import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default {
	name: 'XGateTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosXGate: ref([]),
			clientes: ref([]),
			loading: {
				cobranca: false,
				transferencia: false,
				transferenciaCliente: false,
				saldo: false,
				cliente: false
			},
			errors: {
				cobranca: {},
				transferencia: {},
				transferenciaCliente: {},
				saldo: {},
				cliente: {}
			},
			resultado: {
				cobranca: null,
				transferencia: null,
				transferenciaCliente: null,
				saldo: null,
				cliente: null
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
			transferenciaClienteForm: {
				banco_id: null,
				cliente_id: null,
				valor: null,
				description: null
			},
			saldoForm: {
				banco_id: null
			},
			clienteForm: {
				banco_id: null,
				cliente_id: null
			}
		};
	},
	computed: {
		clientesComPix() {
			return this.clientes.filter(c => c.pix_cliente);
		}
	},
	mounted() {
		this.carregarBancos();
		this.carregarClientes();
	},
	methods: {
		async carregarBancos() {
			try {
				const response = await this.bancoService.getAll();
				this.bancosXGate = response.data.data.filter(b => (b.bank_type || 'normal') === 'xgate');
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
					formData.due_date = formData.due_date.toISOString().split('T')[0];
				}

				const response = await axios.post(`${apiPath}/xgate/teste/cobranca`, formData);
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
				const response = await axios.post(`${apiPath}/xgate/teste/transferencia`, this.transferenciaForm);
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
		async testarTransferenciaCliente() {
			this.loading.transferenciaCliente = true;
			this.errors.transferenciaCliente = {};
			this.resultado.transferenciaCliente = null;

			try {
				const response = await axios.post(`${apiPath}/xgate/teste/transferencia-cliente`, this.transferenciaClienteForm);
				this.resultado.transferenciaCliente = response.data;
				
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
					this.errors.transferenciaCliente = error.response.data.errors;
				}
				this.resultado.transferenciaCliente = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao testar transferência',
					life: 3000
				});
			} finally {
				this.loading.transferenciaCliente = false;
			}
		},
		async consultarSaldo() {
			this.loading.saldo = true;
			this.errors.saldo = {};
			this.resultado.saldo = null;

			try {
				const response = await axios.post(`${apiPath}/xgate/teste/consultar-saldo`, this.saldoForm);
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
		},
		async criarOuAtualizarCliente() {
			this.loading.cliente = true;
			this.errors.cliente = {};
			this.resultado.cliente = null;

			try {
				const response = await axios.post(`${apiPath}/xgate/teste/criar-cliente`, this.clienteForm);
				this.resultado.cliente = response.data;
				
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
					this.errors.cliente = error.response.data.errors;
				}
				this.resultado.cliente = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message || 'Erro ao criar/atualizar cliente',
					life: 3000
				});
			} finally {
				this.loading.cliente = false;
			}
		}
	}
};
</script>
