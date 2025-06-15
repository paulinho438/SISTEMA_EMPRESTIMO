import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class ClientService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/cliente/${id}`);
	};

	criarUsuarioAPP = async (id) => {
		return await axios.get(`${apiPath}/cliente/${id}/enviar_acesso_app`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/cliente`);
	};

	getClientesDisponiveis = async () => {
		return await axios.get(`${apiPath}/clientesdisponiveis`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/cliente/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/cliente`, permissions);
		else return await axios.put(`${apiPath}/cliente/${permissions.id}`, permissions);
	};

	mensagemEmMassa = async (dados) => {
		return await axios.post(`${apiPath}/enviarmensagemmassa`, dados);
	};

	cobrarClientes = async () => {
		return await axios.get(`${apiPath}/cobranca/buttonpressed`);
	};

	getEnvioAutomaticoRenovacao = async () => {
		return await axios.get(`${apiPath}/getenvioautomaticorenovacao`);
	};

	getMensagemAudioAutomatico = async () => {
		return await axios.get(`${apiPath}/getmensagemaudioautomatico`);
	};

	alterEnvioAutomaticoRenovacao = async () => {
		return await axios.post(`${apiPath}/empresas/alterenvioautomaticorenovacao`);
	};

	alterMensagemAudioAutomatico = async () => {
		return await axios.post(`${apiPath}/empresas/altermensagemaudioautomatico`);
	};

}
