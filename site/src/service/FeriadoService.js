import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class FeriadoService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/feriado/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/feriado`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/feriado/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/feriado`, permissions);
		else return await axios.put(`${apiPath}/feriado/${permissions.id}`, permissions);
        

	};

}
