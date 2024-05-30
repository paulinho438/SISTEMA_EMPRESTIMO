import axios from 'axios';

export default class CrudService {
	constructor(domain) {
		this.domain = domain;
	}

	getAll = async () => {
		return await axios.get(`${this.domain}`);
	};

	get = async (id) => {
		return await axios.get(`${this.domain}/${id}`);
	};

	save = async (data) => {
		if (undefined === data.id) return await this.insert(data);
		else return await this.update(data, data.id);
	};

	insert = async (data) => {
		return await axios.post(`${this.domain}`, data);
	};

	update = async (data, id) => {
		return await axios.put(`${this.domain}/${id}`, data);
	};
}
