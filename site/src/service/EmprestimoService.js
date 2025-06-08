import store from '@/store';
import { useRouter } from 'vue-router';
import { mapGetters, mapMutations } from 'vuex';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class EmprestimoService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/emprestimo/${id}`);
	};

    getAll = async (params) => {
		return await axios.get(`${apiPath}/emprestimo`, { params });
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/emprestimo/${id}/delete`);
	};

	protestarEmprestimo = async (id) => {
		return await axios.post(`${apiPath}/emprestimo/setar_protesto_emprestimo/${id}`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/emprestimo`, permissions);
		else return await axios.put(`${apiPath}/emprestimo/${permissions.id}`, permissions);
	};

	saveRefinanciamento = async (permissions) => {
        return await axios.post(`${apiPath}/emprestimorefinanciamento`, permissions);
	};

	saveRenovacao = async (permissions) => {
        return await axios.post(`${apiPath}/emprestimorenovacao`, permissions);
	};

	refinanciamento = async (id, saldo) => {
		return await axios.post(`${apiPath}/emprestimo/refinanciamento/${id}`, { saldo: saldo });
	};

	renovacao = async (id, valor, valor_deposito) => {
		return await axios.post(`${apiPath}/emprestimo/renovacao/${id}`, { valor: valor, valor_deposito: valor_deposito });
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

	searchbancofechamento= async (value) => {
		return await axios.post(`${apiPath}/emprestimo/search/bancofechamento`, { name: value });
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

	efetuarPagamentoEmprestimoConsulta = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/transferenciaconsultar/${id}`);
		transferenciaconsultar
	};

	efetuarPagamentoTitulo = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/transferenciatitulo/${id}`);
	};

	efetuarPagamentoTituloConsulta = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/transferenciatituloconsultar/${id}`);
	};
	

	reprovarEmprestimo = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/reprovaremprestimo/${id}`);
	};

	reprovarPagamentoContasAPagar = async (id) => {
		return await axios.post(`${apiPath}/contaspagar/pagamentos/reprovarcontasapagar/${id}`);
	};
	
	infoEmprestimoFront = async (id) => {
		return await axios.post(`${apiPath}/parcela/${id}/infoemprestimofront`);
	};

	personalizarPagamento = async (id, valor) => {
		return await axios.post(`${apiPath}/parcela/${id}/personalizarpagamento`, { valor: valor });
	};

	gerarPixPagamentoParcela = async (id) => {
		return await axios.post(`${apiPath}/parcela/${id}/gerarpixpagamentoparcela`, {});
	};

	gerarPixPagamentoSaldoPendente = async (id) => {
		return await axios.post(`${apiPath}/parcela/${id}/gerarpixpagamentosaldopendente`, {});
	};
	
	gerarPixPagamentoQuitacao = async (id) => {
		return await axios.post(`${apiPath}/parcela/${id}/gerarpixpagamentoquitacao`, {});
	};

	


}
