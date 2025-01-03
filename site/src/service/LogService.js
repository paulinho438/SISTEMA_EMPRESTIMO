import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class LogService {

    constructor() {
		this.router = useRouter();
	}
    getAll = async () => {
		return await axios.get(`${apiPath}/log`);
	};

}
