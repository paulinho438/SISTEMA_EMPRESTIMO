<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Teste de API PixGo</h5>
				<p class="text-color-secondary">
					Apenas criação de cobrança PIX (mínimo R$ 10,00). Documentação:
					<a href="https://pixgo.org/api/v1/docs#endpoints" target="_blank" rel="noopener">pixgo.org/api/v1/docs</a>
				</p>

				<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-6">
						<label for="cobranca_banco">Banco PixGo *</label>
						<Dropdown
							v-model="cobrancaForm.banco_id"
							:options="bancosPixgo"
							optionLabel="name"
							optionValue="id"
							placeholder="Selecione um banco PixGo"
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
							:min="10"
							:maxFractionDigits="2"
							placeholder="10.00"
							class="w-full"
							:class="{ 'p-invalid': errors.cobranca?.valor }"
						/>
						<small class="text-color-secondary">Mínimo R$ 10,00 (regra PixGo).</small>
						<small v-if="errors.cobranca?.valor" class="text-red-500 block">{{ errors.cobranca.valor[0] }}</small>
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
	name: 'PixGoTest',
	setup() {
		return {
			toast: useToast(),
			bancoService: new BancoService(),
			clientService: new ClientService()
		};
	},
	data() {
		return {
			bancosPixgo: ref([]),
			clientes: ref([]),
			loading: { cobranca: false },
			errors: { cobranca: {} },
			resultado: { cobranca: null },
			cobrancaForm: { banco_id: null, cliente_id: null, valor: 10 }
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
				this.bancosPixgo = (response.data.data || []).filter((b) => (b.bank_type || 'normal') === 'pixgo');
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
				const response = await axios.post(`${apiPath}/pixgo/teste/cobranca`, { ...this.cobrancaForm });
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
		}
	}
};
</script>
