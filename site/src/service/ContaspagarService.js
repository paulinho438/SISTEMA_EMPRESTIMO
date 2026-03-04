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
        const config = isFormData ? {} : {};
        if (isFormData) {
            const id = permissions.get?.('id');
            if (id) {
                return await axios.put(`${apiPath}/contaspagar/${id}`, permissions, config);
            }
            return await axios.post(`${apiPath}/contaspagar`, permissions, config);
        }
        if (undefined === permissions.id) return await axios.post(`${apiPath}/contaspagar`, permissions);
        return await axios.put(`${apiPath}/contaspagar/${permissions.id}`, permissions);
    };

}
