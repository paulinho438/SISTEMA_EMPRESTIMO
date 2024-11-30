import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class BancoService {

    constructor() {
		this.router = useRouter();
	}

    get = async (id) => {
		return await axios.get(`${apiPath}/bancos/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/bancos`);
	};

    delete = async (id) => {
		return await axios.get(`${apiPath}/bancos/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/bancos`, permissions);
		else return await axios.put(`${apiPath}/bancos/${permissions.id}`, permissions);
	};

	fechamentoCaixa = async (id) => {
		return await axios.post(`${apiPath}/fechamentocaixa/${id}`, {});
	};

	alterarcaixa = async (id, saldobanco, saldocaixa, saldocaixapix) => {
		return await axios.post(`${apiPath}/alterarcaixa/${id}`, { saldobanco: saldobanco, saldocaixa: saldocaixa, saldocaixapix: saldocaixapix  });
	};

	depositar = async (id, valor) => {
		return await axios.post(`${apiPath}/depositar/${id}`, { valor: valor  });
	};

	saqueConsulta = async (id, valor) => {
		return await axios.post(`${apiPath}/saqueconsulta/${id}`, { valor: valor });
	};
	efetuarSaque = async (id, valor) => {
		return await axios.post(`${apiPath}/efetuarsaque/${id}`, { valor: valor });
	};

	saveComCertificado = async (data) => {
		try {
			// Objeto contendo todos os dados, incluindo a foto
			const formData = new FormData();

			// Adiciona cada item do objeto aos dados do FormData
			for (const key in data) {
				// Se a chave for 'foto', trata como um arquivo e adiciona ao FormData
				if (key === 'certificado') {
					const file = data[key];
					formData.append('certificado', file);
				} else {
					// Para outros campos, adiciona ao FormData como campos de texto
					if(data[key] != null){
						formData.append(key, data[key]);
					}
				}
			}

			// Verifica se o objeto de dados possui um ID
			if (undefined === data.id) {
				return await this.insertComCertificado(formData);
			} else {
				return await this.updateComCertificado(formData, data.id);
			}
		} catch (error) {
			// Lide com erros aqui
			console.error('Erro ao salvar com certificado:', error);
			throw error; // Rejeita a promessa com o erro
		}
	};

	insertComCertificado = async (data) => {
		try {
		  return await axios.post(`${apiPath}/bancos`, data, {
			headers: {
			  'Content-Type': 'multipart/form-data',
			},
		  });
		} catch (error) {
		  // Lide com erros aqui
		  console.error('Erro ao inserir com foto:', error);
		  throw error; // Rejeita a promessa com o erro
		}
	  };

	updateComCertificado = async (data, id) => {
		try {
		  return await axios.post(`${apiPath}/bancos/${id}`, data, {
			headers: {
			  'Content-Type': 'multipart/form-data',
			},
		  });
		} catch (error) {
		  // Lide com erros aqui
		  console.error('Erro ao atualizar com certificado:', error);
		  throw error; // Rejeita a promessa com o erro
		}
	};

}
