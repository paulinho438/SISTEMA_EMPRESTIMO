import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class CostcenterService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/costcenter/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/costcenter`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/costcenter/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/costcenter`, permissions);
		else return await axios.put(`${apiPath}/costcenter/${permissions.id}`, permissions);
        

	};

}
