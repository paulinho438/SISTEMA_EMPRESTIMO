<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de APIs Cora</h5>
				<p class="text-color-secondary">
					Teste o endpoint de criação de cobrança Cora via <b>POST /cobranca/teste-cora</b>.
				</p>

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
						<label for="bearer_token">Bearer token (opcional)</label>
						<Textarea
							v-model="form.bearer_token"
							autoResize
							rows="3"
							class="w-full"
							placeholder="Cole aqui o token Bearer (JWT). Se informado, o backend usa direto no /v2/invoices (stage) e não chama /token."
						/>
						<small class="text-gray-500">Use apenas para teste. O token expira.</small>
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
			loading: ref(false),
			errors: ref({}),
			resultado: ref(null),
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
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar bancos: ' + (error.message || 'falha'),
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
				const payload = { ...this.form };
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
	}
};
</script>

