import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class RelatorioLucroRealService {
	constructor() {
	}

	async relatorioMensal(mes) {
		return await axios.get(`${apiPath}/relatorio-lucro-real/mensal`, {
			params: { mes }
		});
	}

	async relatorioAnual(ano) {
		return await axios.get(`${apiPath}/relatorio-lucro-real/anual`, {
			params: { ano }
		});
	}

	async relatorioPeriodo(dataInicio, dataFim) {
		return await axios.get(`${apiPath}/relatorio-lucro-real/periodo`, {
			params: { data_inicio: dataInicio, data_fim: dataFim }
		});
	}

	async exportarExcel(params) {
		return await axios.get(`${apiPath}/relatorio-lucro-real/excel`, {
			params,
			responseType: 'blob'
		});
	}

	async exportarPDF(params) {
		return await axios.get(`${apiPath}/relatorio-lucro-real/pdf`, {
			params,
			responseType: 'blob'
		});
	}
}

