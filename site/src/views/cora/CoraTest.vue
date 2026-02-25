<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs Cora</h5>
				<p class="text-color-secondary">
					Teste o endpoint de criação de cobrança Cora via <b>POST /cobranca/teste-cora</b>.
				</p>
				
				<!-- Token compartilhado (Cobrança + Transferência) -->
				<div class="formgrid grid mt-3">
					<div class="field col-12 md:col-6">
						<label for="cora_banco_token">Banco Cora (para gerar token)</label>
						<Dropdown
							v-model="tokenBancoId"
							:options="bancosCora"
							optionLabel="name"
							optionValue="id"
							placeholder="Selecione um banco Cora"
							class="w-full"
						/>
					</div>
					<div class="field col-12 md:col-6 flex align-items-end">
						<Button
							label="Gerar token (Stage)"
							icon="pi pi-key"
							class="w-full"
							@click="gerarTokenStage"
							:loading="tokenLoading"
							:disabled="!tokenBancoId"
						/>
					</div>
					<div class="field col-12">
						<label for="shared_bearer_token">Bearer token (JWT)</label>
						<Textarea
							v-model="bearerToken"
							autoResize
							rows="3"
							class="w-full"
							placeholder="Cole aqui o token Bearer (JWT)."
							:class="{ 'p-invalid': transferErrors?.bearer_token }"
						/>
						<small class="text-gray-500">Usado em cobrança e transferência. Necessário para Transferência.</small>
						<small v-if="transferErrors?.bearer_token" class="text-red-500 block mt-1">{{ transferErrors.bearer_token[0] }}</small>
						<small v-if="tokenInfo" class="text-gray-500 block mt-1">{{ tokenInfo }}</small>
					</div>
				</div>

				<TabView>
					<TabPanel header="Cobrança (Invoice)">
						<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-6">
						<label for="cobranca_banco">Banco Cora *</label>
						<Dropdown
							v-model="form.banco_id"
							:options="bancosCora"
							optionLabel="name"
							optionValue="id"
							placeholder="Selecione um banco Cora"
							class="w-full"
							:class="{ 'p-invalid': errors?.banco_id }"
						/>
						<small v-if="errors?.banco_id" class="text-red-500">{{ errors.banco_id[0] }}</small>
					</div>

					<div class="field col-12 md:col-6">
						<label for="cobranca_cliente">Cliente *</label>
						<Dropdown
							v-model="form.cliente_id"
							:options="clientes"
							optionLabel="nome_completo"
							optionValue="id"
							placeholder="Selecione um cliente"
							class="w-full"
							:class="{ 'p-invalid': errors?.cliente_id }"
						/>
						<small v-if="errors?.cliente_id" class="text-red-500">{{ errors.cliente_id[0] }}</small>
					</div>

					<div class="field col-12 md:col-6">
						<label for="cobranca_valor">Valor (R$) *</label>
						<InputNumber
							v-model="form.valor"
							mode="decimal"
							:min="0.01"
							:maxFractionDigits="2"
							placeholder="0,00"
							class="w-full"
							:class="{ 'p-invalid': errors?.valor }"
						/>
						<small v-if="errors?.valor" class="text-red-500">{{ errors.valor[0] }}</small>
					</div>

					<div class="field col-12 md:col-6">
						<label for="cobranca_due_date">Data de vencimento (opcional)</label>
						<Calendar
							v-model="form.due_date"
							dateFormat="yy-mm-dd"
							:minDate="new Date()"
							placeholder="Selecione a data"
							class="w-full"
						/>
					</div>

					<div class="field col-12">
						<Button
							label="Testar Cobrança Cora"
							icon="pi pi-credit-card"
							@click="testarCobranca"
							:loading="loading"
							class="w-full"
						/>
					</div>

					<div v-if="resultado" class="field col-12">
						<Divider />
						<h6>Resultado:</h6>
						<pre class="p-3 border-round surface-ground" style="max-height: 420px; overflow: auto;">{{ JSON.stringify(resultado, null, 2) }}</pre>
					</div>
						</div>
					</TabPanel>

					<TabPanel header="Transferência">
						<div class="formgrid grid mt-4">
							<div class="field col-12">
								<p class="text-color-secondary m-0">
									Chama <b>POST /cora/teste/transferencia</b> (API → Cora Stage `transfers/initiate`).
								</p>
							</div>

							<div class="field col-12 md:col-6">
								<label>Bank code *</label>
								<InputText v-model="transferPayload.destination.bank_code" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Account type *</label>
								<Dropdown
									v-model="transferPayload.destination.account_type"
									:options="accountTypeOptions"
									optionLabel="label"
									optionValue="value"
									class="w-full"
								/>
							</div>
							<div class="field col-12 md:col-6">
								<label>Branch number *</label>
								<InputText v-model="transferPayload.destination.branch_number" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Account number *</label>
								<InputText v-model="transferPayload.destination.account_number" class="w-full" />
							</div>

							<div class="field col-12 md:col-6">
								<label>Holder name *</label>
								<InputText v-model="transferPayload.destination.holder.name" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Document type *</label>
								<Dropdown
									v-model="transferPayload.destination.holder.document.type"
									:options="documentTypeOptions"
									optionLabel="label"
									optionValue="value"
									class="w-full"
								/>
							</div>
							<div class="field col-12 md:col-6">
								<label>Document identity *</label>
								<InputText v-model="transferPayload.destination.holder.document.identity" class="w-full" />
							</div>

							<div class="field col-12 md:col-6">
								<label>Amount (centavos) *</label>
								<InputNumber v-model="transferPayload.amount" mode="decimal" :min="1" :maxFractionDigits="0" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Category *</label>
								<InputText v-model="transferPayload.category" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Code *</label>
								<InputText v-model="transferPayload.code" class="w-full" />
							</div>
							<div class="field col-12">
								<label>Description</label>
								<InputText v-model="transferPayload.description" class="w-full" />
							</div>
							<div class="field col-12 md:col-6">
								<label>Scheduled (YYYY-MM-DD)</label>
								<Calendar v-model="transferScheduled" dateFormat="yy-mm-dd" class="w-full" />
							</div>

							<div class="field col-12">
								<Button
									label="Testar Transferência"
									icon="pi pi-send"
									severity="danger"
									class="w-full"
									@click="testarTransferencia"
									:loading="transferLoading"
								/>
							</div>

							<div v-if="transferResult" class="field col-12">
								<Divider />
								<h6>Resultado:</h6>
								<pre class="p-3 border-round surface-ground" style="max-height: 420px; overflow: auto;">{{ JSON.stringify(transferResult, null, 2) }}</pre>
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
	name: 'CoraTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosCora: ref([]),
			clientes: ref([]),
			tokenBancoId: ref(null),
			tokenLoading: ref(false),
			tokenInfo: ref(null),
			bearerToken: ref(null),
			loading: ref(false),
			errors: ref({}),
			resultado: ref(null),
			transferLoading: ref(false),
			transferErrors: ref({}),
			transferResult: ref(null),
			transferScheduled: ref(null),
			transferPayload: ref({
				destination: {
					account_type: 'CHECKING',
					bank_code: '341',
					account_number: '092135',
					branch_number: '7679',
					holder: {
						name: 'Cora Pagamentos',
						document: {
							identity: '72420176000104',
							type: 'CNPJ'
						}
					}
				},
				amount: 10001,
				description: 'Mandando',
				code: 'your-specific-code-EXP123',
				category: 'PAYROLL',
				scheduled: null
			}),
			accountTypeOptions: [
				{ label: 'CHECKING', value: 'CHECKING' },
				{ label: 'SAVINGS', value: 'SAVINGS' }
			],
			documentTypeOptions: [
				{ label: 'CPF', value: 'CPF' },
				{ label: 'CNPJ', value: 'CNPJ' }
			],
			form: ref({
				banco_id: null,
				cliente_id: null,
				valor: null,
				due_date: null,
				bearer_token: null
			})
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
				this.bancosCora = (response.data.data || []).filter((b) => (b.bank_type || 'normal') === 'cora');
				// setar um padrão para gerar token
				if (!this.tokenBancoId && this.bancosCora.length) {
					this.tokenBancoId = this.bancosCora[0].id;
				}
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar bancos: ' + (error.message || 'falha'),
					life: 3000
				});
			}
		},
		async gerarTokenStage() {
			this.tokenLoading = true;
			this.tokenInfo = null;
			try {
				const res = await axios.post(`${apiPath}/cora/teste/gerar-token`, {
					banco_id: this.tokenBancoId,
					environment: 'stage'
				});

				const accessToken = res.data?.response?.data?.access_token;
				if (accessToken) {
					this.bearerToken = accessToken;
					this.tokenInfo = 'Token gerado com sucesso (stage).';
					this.toast.add({ severity: ToastSeverity.SUCCESS, detail: 'Token gerado', life: 2500 });
				} else {
					this.toast.add({ severity: ToastSeverity.WARN, detail: 'Não retornou access_token', life: 3000 });
					this.tokenInfo = 'Falha: não retornou access_token.';
				}
			} catch (e) {
				const data = e.response?.data;
				this.toast.add({ severity: ToastSeverity.ERROR, detail: data?.message || 'Erro ao gerar token', life: 3500 });
				this.tokenInfo = data?.message || 'Erro ao gerar token.';
			} finally {
				this.tokenLoading = false;
			}
		},
		async carregarClientes() {
			try {
				const response = await this.clientService.getAll();
				this.clientes = response.data.data || [];
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar clientes: ' + (error.message || 'falha'),
					life: 3000
				});
			}
		},
		async testarCobranca() {
			this.loading = true;
			this.errors = {};
			this.resultado = null;

			try {
				const payload = { ...this.form, bearer_token: this.bearerToken };
				if (payload.due_date) {
					payload.due_date = payload.due_date.toISOString ? payload.due_date.toISOString().split('T')[0] : payload.due_date;
				}

				const response = await axios.post(`${apiPath}/cobranca/teste-cora`, payload);
				this.resultado = response.data;

				this.toast.add({
					severity: response.data?.success ? ToastSeverity.SUCCESS : ToastSeverity.WARN,
					detail: response.data?.message || (response.data?.success ? 'Cobrança criada' : 'Teste concluído'),
					life: 3000
				});
			} catch (error) {
				if (error.response?.data?.details) {
					this.errors = error.response.data.details;
				}
				this.resultado = error.response?.data || { error: error.message };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.error || error.response?.data?.message || 'Erro ao testar cobrança Cora',
					life: 3500
				});
			} finally {
				this.loading = false;
			}
		}
		,
		formatDateYMD(d) {
			if (!d) return null;
			try {
				return d.toISOString ? d.toISOString().split('T')[0] : d;
			} catch (_) {
				return d;
			}
		},
		async testarTransferencia() {
			this.transferLoading = true;
			this.transferErrors = {};
			this.transferResult = null;

			try {
				if (!this.bearerToken) {
					this.transferErrors = { bearer_token: ['Informe o bearer token'] };
					return;
				}

				const payload = { ...this.transferPayload };
				payload.scheduled = this.formatDateYMD(this.transferScheduled);

				const res = await axios.post(`${apiPath}/cora/teste/transferencia`, {
					bearer_token: this.bearerToken,
					payload
				});

				this.transferResult = res.data;
				this.toast.add({
					severity: res.data?.success ? ToastSeverity.SUCCESS : ToastSeverity.WARN,
					detail: res.data?.message || 'Teste executado',
					life: 3000
				});
			} catch (e) {
				if (e.response?.data?.errors) this.transferErrors = e.response.data.errors;
				this.transferResult = e.response?.data || { error: e?.message || 'Erro' };
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: e.response?.data?.message || 'Erro ao testar transferência',
					life: 3500
				});
			} finally {
				this.transferLoading = false;
			}
		}
	}
};
</script>

