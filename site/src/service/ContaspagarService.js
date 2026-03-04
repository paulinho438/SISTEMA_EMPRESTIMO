import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class ContaspagarService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/contaspagar/${id}`);
	};

    getAll = async (params = {}) => {
		return await axios.get(`${apiPath}/contaspagar`, { params });
	};

	getPagamentosPendentes = async () => {
		return await axios.get(`${apiPath}/contaspagar/pagamentos/pendentes`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/contaspagar/${id}/delete`);
	};

    save = async (permissions, isFormData = false) => {
        if (isFormData) {
            const id = permissions.get?.('id');
            if (id) {
                // POST com _method=PUT: PHP não popula $_POST em PUT, então FormData não chega ao Laravel
                permissions.append('_method', 'PUT');
                return await axios.post(`${apiPath}/contaspagar/${id}`, permissions);
            }
            return await axios.post(`${apiPath}/contaspagar`, permissions);
        }
        if (undefined === permissions.id) return await axios.post(`${apiPath}/contaspagar`, permissions);
        return await axios.put(`${apiPath}/contaspagar/${permissions.id}`, permissions);
    };

}
