import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class CategoriesService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/categories/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/categories`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/categories/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/categories`, permissions);
		else return await axios.put(`${apiPath}/categories/${permissions.id}`, permissions);
        

	};

}
