import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class ContasreceberService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/contasreceber/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/contasreceber`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/contasreceber/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/contasreceber`, permissions);
		else return await axios.put(`${apiPath}/contasreceber/${permissions.id}`, permissions);
        

	};

}
