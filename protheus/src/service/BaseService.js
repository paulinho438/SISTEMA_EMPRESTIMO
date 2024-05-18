const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class BaseService {
	domain = '';

	constructor(domain) {
		this.domain = domain;
	}

	execFetch = async (url, params = null) => {
		const jsonParams = {
			...params,
			headers: {
				'Content-Type': 'application/json',
				Accept: 'application/json'
			}
		};

		const data = await fetch(`${apiPath}/${url}`, jsonParams)
			.then((r) => r.json())
			.then((d) => d.data);

		return data;
	};

	execInsertAndUpdate = async (url, params = null) => {
		const jsonParams = {
			...params,
			headers: { 'Content-Type': 'application/json' }
		};

		return await fetch(`${apiPath}/${url}`, jsonParams);
	};

	getAll = async () => {
		const url = `${this.domain}`;
		return await this.execFetch(url);
	};

	show = async (id) => {
		const url = `${this.domain}/${id}`;
		return await this.execFetch(url);
	};

	save = async (data) => {
		return data.id ? this.update(data) : this.insert(data);
	};

	insert = async (data) => {
		try {
			const jsonData = JSON.stringify(data);

			const url = `${this.domain}`;
			const params = {
				body: jsonData,
				method: 'POST'
			};

			const response = await this.execInsertAndUpdate(url, params);
			const responseData = await response.json();

			return responseData;
		} catch (e) {
			console.log('ERROR: ', e);
		}
	};

	update = async (data) => {
		const jsonData = JSON.stringify(data);

		const url = `${this.domain}/${data.id}`;
		const params = {
			body: jsonData,
			method: 'PUT'
		};

		return await this.execInsertAndUpdate(url, params);
	};

	vai = async (data) => {
		const requestOptions = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Accept: 'application/json'
			},
			body: JSON.stringify(data)
		};

		return await fetch(`${apiPath}/users`, requestOptions).then(async (response) => {
			const data = await response.json();

			if (!response.ok) {
				return data; // .errors || (data && data.message) || response.status;
			}

			return data;
		});
	};
}
