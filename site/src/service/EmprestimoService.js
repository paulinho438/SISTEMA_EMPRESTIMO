import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class EmprestimoService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/emprestimo/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/emprestimo`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/emprestimo/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/emprestimo`, permissions);
		else return await axios.put(`${apiPath}/emprestimo/${permissions.id}`, permissions);
        

	};

	baixaDesconto = async (id, valor, saldo) => {
		return await axios.post(`${apiPath}/emprestimo/baixadesconto/${id}`, { valor: valor, saldo: saldo });
	};

	searchFornecedor = async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/fornecedor`, { name: value });
	};

	searchClient = async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/cliente`, { name: value });
	};

	searchbanco = async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/banco`, { name: value });
	};
	
	searchCostcenter = async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/costcenter`, { name: value });
	};

	searchConsultor = async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/consultor`, { name: value });
	};

	feriados = async (id) => {
		return await axios.get(`${apiPath}/feriados`);
	};

	baixaParcela = async (id, dt_baixa, valor) => {
		return await axios.post(`${apiPath}/parcela/${id}/baixamanual`, { dt_baixa: dt_baixa, valor: valor });
	};

	cancelarBaixaParcela = async (id) => {
		return await axios.get(`${apiPath}/parcela/${id}/cancelarbaixamanual`);
	};

	efetuarPagamentoEmprestimo = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/transferencia/${id}`);
	};

	reprovarEmprestimo = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/reprovaremprestimo/${id}`);
	};

	infoEmprestimoFront = async (id) => {
		return await axios.post(`${apiPath}/parcela/${id}/infoemprestimofront`);
	};


}
