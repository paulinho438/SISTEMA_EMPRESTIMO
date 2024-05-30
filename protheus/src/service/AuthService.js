import axios from 'axios';

export default class AuthService {
	login = async (data) => {
		return await axios.post('/auth/login', data);
	};

	logout = async () => {
		return await axios.get('/auth/logout');
	};

	forgot = async (data) => {
		return await axios.post('/auth/forgot', data);
	};
}
