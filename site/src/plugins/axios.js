import axios from 'axios';
import router from '../router';

import store from '@/store';

const baseURL = import.meta.env.VITE_APP_BASE_URL;

axios.defaults.baseURL = baseURL;
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use(
	(config) => {
		if (localStorage.getItem('app.emp.token')) {
			config.headers['Authorization'] = `Bearer ${localStorage.getItem('app.emp.token')}`
			config.headers['company-id'] = `${store?.getters?.isCompany?.id}`
		}
		// FormData precisa que o navegador defina Content-Type com boundary
		if (config.data instanceof FormData) {
			delete config.headers['Content-Type'];
		}
		return config;
	},
	(error) => {
		return Promise.reject(error);
	}
)
axios.interceptors.response.use(
	(response) => {
		return response;
	},
	(error) => {
		if (401 == error?.response?.status) {
			if ('login' != router?.currentRoute?.value?.name) {
				if (localStorage.getItem('app.emp.token')) {
					localStorage.removeItem('app.emp.token');
					router.push({ name: 'login' });
				}
			}
		}
		
		return Promise.reject(error);
	}
);

export default axios;
