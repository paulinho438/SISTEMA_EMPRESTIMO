<template>
	<div class="grid">
		<div class="col-12">
			<div class="card">
				<h5>Fila W-API</h5>
				<p class="text-color-secondary">Visualize e remova itens da fila de mensagens da W-API (api.w-api.app). Acesso restrito ao usuário MASTERGERAL.</p>

				<div class="formgrid grid mt-4">
					<div class="field col-12 md:col-6">
						<label for="company">Empresa (instância) *</label>
						<Dropdown
							v-model="selectedCompany"
							:options="companies"
							optionLabel="company"
							optionValue="id"
							placeholder="Selecione uma empresa"
							class="w-full"
							@change="onCompanyChange"
						/>
					</div>
					<div class="field col-12 md:col-6 flex align-items-end gap-2">
						<Button
							label="Atualizar fila"
							icon="pi pi-refresh"
							:loading="loading"
							:disabled="!selectedCompany"
							@click="carregarFila"
						/>
					</div>
				</div>

				<div v-if="erro" class="mt-3 p-3 border-round surface-ground text-red-500">
					{{ erro }}
				</div>

				<div v-if="selectedCompany && filaCarregada" class="mt-4">
					<DataTable
						:value="itensFila"
						:loading="loading"
						stripedRows
						responsiveLayout="scroll"
						class="p-datatable-sm"
						:paginator="true"
						:rows="10"
						:rowsPerPageOptions="[5, 10, 25, 50]"
					>
						<template #empty>
							<div class="p-4 text-center text-color-secondary">Nenhum item na fila.</div>
						</template>
						<template #loading>
							<ProgressSpinner style="width: 50px; height: 50px" />
						</template>
						<Column field="id" header="ID" sortable v-if="temCampoId">
							<template #body="{ data }">
								{{ data.id ?? data._id ?? '-' }}
							</template>
						</Column>
						<Column header="Dados" v-if="itensFila.length">
							<template #body="{ data }">
								<pre class="m-0 text-sm surface-ground p-2 border-round" style="max-width: 400px; overflow: auto;">{{ JSON.stringify(data, null, 2) }}</pre>
							</template>
						</Column>
						<Column header="Ações" style="width: 100px">
							<template #body="{ data }">
								<Button
									icon="pi pi-trash"
									class="p-button-danger p-button-text p-button-sm"
									v-tooltip.top="'Remover da fila'"
									:loading="deletingId === (data.id ?? data._id)"
									@click="deletarItem(data)"
								/>
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import axios from 'axios';
import PermissionsService from '@/service/PermissionsService';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default {
	name: 'FilaWapi',
	setup() {
		return { toast: useToast(), router: useRouter(), permissionsService: new PermissionsService() };
	},
	data() {
		return {
			companies: [],
			selectedCompany: null,
			loading: false,
			deletingId: null,
			erro: null,
			filaData: null
		};
	},
	computed: {
		itensFila() {
			if (!this.filaData) return [];
			const d = this.filaData.data ?? this.filaData;
			return Array.isArray(d) ? d : (d.items ?? d.list ?? []);
		},
		temCampoId() {
			return this.itensFila.some(
				(item) => item != null && ('id' in item || '_id' in item)
			);
		},
		filaCarregada() {
			return this.filaData !== null;
		}
	},
	mounted() {
		if (!this.permissionsService.hasPermissions('view_mastergeral')) {
			this.router.push({ name: 'accessDenied' });
			return;
		}
		this.carregarCompanies();
	},
	methods: {
		async carregarCompanies() {
			try {
				const response = await axios.get(`${apiPath}/wapi/fila/companies`);
				this.companies = response.data.data ?? [];
			} catch (error) {
				if (error.response?.status === 403) {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: 'Acesso negado. Apenas MASTERGERAL.',
						life: 4000
					});
					return;
				}
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Erro ao carregar empresas: ' + (error.response?.data?.message || error.message),
					life: 4000
				});
			}
		},
		onCompanyChange() {
			this.filaData = null;
			this.erro = null;
			if (this.selectedCompany) this.carregarFila();
		},
		async carregarFila() {
			if (!this.selectedCompany) return;
			this.loading = true;
			this.erro = null;
			try {
				const response = await axios.get(`${apiPath}/wapi/fila`, {
					params: { company_id: this.selectedCompany, per_page: 50, page: 1 }
				});
				this.filaData = response.data;
				if (!response.data.success) {
					this.erro = response.data.message ?? 'Erro ao listar fila';
				}
			} catch (error) {
				this.filaData = null;
				this.erro = error.response?.data?.message ?? error.message ?? 'Erro ao carregar fila';
				if (error.response?.status === 403) {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: 'Acesso negado. Apenas MASTERGERAL.',
						life: 4000
					});
				}
			} finally {
				this.loading = false;
			}
		},
		async deletarItem(item) {
			const id = item.id ?? item._id ?? item.key;
			if (id == null) {
				this.toast.add({
					severity: ToastSeverity.WARN,
					detail: 'Item sem identificador para exclusão.',
					life: 3000
				});
				return;
			}
			this.deletingId = id;
			try {
				const response = await axios.delete(`${apiPath}/wapi/fila/${encodeURIComponent(id)}`, {
					params: { company_id: this.selectedCompany }
				});
				if (response.data.success) {
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: 'Item removido da fila.',
						life: 3000
					});
					await this.carregarFila();
				} else {
					this.toast.add({
						severity: ToastSeverity.WARN,
						detail: response.data.message ?? 'Erro ao remover.',
						life: 3000
					});
				}
			} catch (error) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: error.response?.data?.message ?? error.message ?? 'Erro ao remover item.',
					life: 4000
				});
			} finally {
				this.deletingId = null;
			}
		}
	}
};
</script>
