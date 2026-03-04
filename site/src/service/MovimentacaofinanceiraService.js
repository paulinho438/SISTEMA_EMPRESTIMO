import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class MovimentacaofinanceiraService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/movimentacaofinanceira/${id}`);
	};

    getAll = async (dt_inicio, dt_final, banco_id = null) => {
		const params = { dt_inicio, dt_final };
		if (banco_id) params.banco_id = banco_id;
		return await axios.get(`${apiPath}/movimentacaofinanceira`, { params });
	};

	getAllControleBcodex = async (dt_inicio, dt_final) => {
		return await axios.get(`${apiPath}/controlebcodex`, {
            params: {
                dt_inicio: dt_inicio,
                dt_final: dt_final
            }
        });
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/movimentacaofinanceira/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/movimentacaofinanceira`, permissions);
		else return await axios.put(`${apiPath}/movimentacaofinanceira/${permissions.id}`, permissions);
        

	};

}
