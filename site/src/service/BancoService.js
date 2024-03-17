import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class BancoService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/bancos/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/bancos`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/bancos/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/bancos`, permissions);
		else return await axios.put(`${apiPath}/bancos/${permissions.id}`, permissions);
        

	};

}
