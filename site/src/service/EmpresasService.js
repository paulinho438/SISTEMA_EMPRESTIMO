import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class EmpresasService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/empresas/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/empresas`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/empresas/${id}/delete`);
	};

    save = async (data) => {
        if (undefined === data.id) return await axios.post(`${apiPath}/empresas`, data);
		else return await axios.put(`${apiPath}/empresas/${data.id}`, data);
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

	

}
