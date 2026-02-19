import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class SimulacaoEmprestimoService {
    getAll = async (params = {}) => {
        return await axios.get(`${apiPath}/simulacoes-emprestimo`, { params });
    };

    get = async (id) => {
        return await axios.get(`${apiPath}/simulacoes-emprestimo/${id}`);
    };

    store = async (payload) => {
        return await axios.post(`${apiPath}/simulacoes-emprestimo`, payload);
    };

    efetivar = async (id) => {
        return await axios.patch(`${apiPath}/simulacoes-emprestimo/${id}/efetivar`);
    };
}
