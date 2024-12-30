import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class EmpresaService {

    constructor() {
		this.router = useRouter();
	}

    get = async () => {
		return await axios.get(`${apiPath}/empresa`);
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

	desconectarZap = async (zap) => {

		try {
			const response = await fetch(`${zap}/logout`);
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

	save = async (data) => {
        return await axios.put(`${apiPath}/empresa/${data.id}`, data);
	};

}
