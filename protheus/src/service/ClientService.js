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

    getAll = async () => {
		return await axios.get(`${apiPath}/cliente`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/cliente/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/cliente`, permissions);
		else return await axios.put(`${apiPath}/cliente/${permissions.id}`, permissions);
        

	};

}
