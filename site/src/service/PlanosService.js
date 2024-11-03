import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class PlanosService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/planos/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/planos`);
	};

	

    delete = async (id) => {
		return await axios.get(`${apiPath}/planos/${id}/delete`);
	};

    save = async (dados) => {
        if (undefined === dados.id) return await axios.post(`${apiPath}/planos`, dados);
		else return await axios.put(`${apiPath}/planos/${dados.id}`, dados);
	};

	

}
