import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class FornecedorService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/fornecedor/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/fornecedor`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/fornecedor/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/fornecedor`, permissions);
		else return await axios.put(`${apiPath}/fornecedor/${permissions.id}`, permissions);
        

	};

}
