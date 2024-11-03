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

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/empresas`, permissions);
		else return await axios.put(`${apiPath}/empresas/${permissions.id}`, permissions);
	};

	

}
