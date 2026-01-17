import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class RelatorioFiscalService {
	constructor() {
	}

	async relatorioMensal(mes, tipo = 'presumido') {
		return await axios.get(`${apiPath}/relatorio-fiscal/mensal`, {
			params: { mes, tipo }
		});
	}

	async relatorioAnual(ano, tipo = 'presumido') {
		return await axios.get(`${apiPath}/relatorio-fiscal/anual`, {
			params: { ano, tipo }
		});
	}

	async relatorioPeriodo(dataInicio, dataFim, tipo = 'presumido') {
		return await axios.get(`${apiPath}/relatorio-fiscal/periodo`, {
			params: { data_inicio: dataInicio, data_fim: dataFim, tipo }
		});
	}

	async exportarExcel(params) {
		return await axios.get(`${apiPath}/relatorio-fiscal/excel`, {
			params,
			responseType: 'blob'
		});
	}

	async exportarPDF(params) {
		return await axios.get(`${apiPath}/relatorio-fiscal/pdf`, {
			params,
			responseType: 'blob'
		});
	}

	async configurarPercentualPresuncao(dados) {
		return await axios.post(`${apiPath}/relatorio-fiscal/configurar`, dados);
	}
}

