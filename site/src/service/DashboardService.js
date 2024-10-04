import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class DashboardService {

    constructor() {
		this.router = useRouter();
	}

    infoConta = async () => {
		return await axios.get(`${apiPath}/dashboard/info-conta`);
	};

	getAll = async () => {
		return await axios.get(`${apiPath}/empresas`);
	};

	zap = async (zap) => {

		try {
			const response = await fetch(`${zap}/logar`);
			return await response.json();
		  } catch (error) {
			console.log(error);
			this.errored = true;
		  } finally {
			this.loading = false;
		  }
		
	};

	getPagamentosPendentes = async () => {
		return await axios.get(`${apiPath}/contaspagar/pagamentos/pendentes`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/contaspagar/${id}/delete`);
	};

    save = async (dados) => {
        return await axios.post(`${apiPath}/empresa`, dados);
	};

}
