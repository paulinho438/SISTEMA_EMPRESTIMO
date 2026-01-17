import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class RelatorioFiscalService {
	constructor() {
	}

	async relatorioMensal(mes) {
		return await axios.get(`${apiPath}/relatorio-fiscal/mensal`, {
			params: { mes }
		});
	}

	async relatorioAnual(ano) {
		return await axios.get(`${apiPath}/relatorio-fiscal/anual`, {
			params: { ano }
		});
	}

	async relatorioPeriodo(dataInicio, dataFim) {
		return await axios.get(`${apiPath}/relatorio-fiscal/periodo`, {
			params: { data_inicio: dataInicio, data_fim: dataFim }
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

