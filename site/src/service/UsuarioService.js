import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class ClientService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/usuario/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/usuario`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/usuario/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/usuario`, permissions);
		else return await axios.put(`${apiPath}/usuario/${permissions.id}`, permissions);
        

	};

}
