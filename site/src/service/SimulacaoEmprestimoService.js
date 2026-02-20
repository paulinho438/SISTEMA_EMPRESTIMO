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

    iniciarAssinatura = async (id, formData) => {
        return await axios.post(`${apiPath}/contratos/${id}/assinatura/iniciar`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    };

    getAssinaturaDetalhes = async (id) => {
        return await axios.get(`${apiPath}/contratos/${id}/assinatura/detalhes`);
    };

    revisarAssinatura = async (id, payload) => {
        return await axios.patch(`${apiPath}/contratos/${id}/assinatura/revisao`, payload);
    };
}
