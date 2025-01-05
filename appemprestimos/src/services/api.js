import AsyncStorage from '@react-native-async-storage/async-storage';

import axios from 'axios';


import { baseUrl, MapsApi } from './Config';

import {getAuthToken, removeAuthToken, getAuthCompany} from '../utils/asyncStorage';

const request = async (method, endpoint, params, token = null) => {
    method = method.toLowerCase();
    let fullUrl = `${baseUrl}${endpoint}`;
    let body = null;

    switch(method) {
        case 'get':
            let queryString = new URLSearchParams(params).toString();
            fullUrl += `?${queryString}`;
            break;
        case 'post':
        case 'put':
        case 'delete':
            body = JSON.stringify(params);
            break;
    }

    let headers = {'Content-Type': 'application/json'};
    if(token) {
        headers.Authorization = `Bearer ${token}`;
        let authCompany = await getAuthCompany();
        headers['company-id'] = authCompany?.id;
    }

    // Gerar o comando curl
    let curlCommand = `curl -X ${method.toUpperCase()} "${fullUrl}" \\\n`;
    for (let [key, value] of Object.entries(headers)) {
        curlCommand += `-H "${key}: ${value}" \\\n`;
    }
    if (body) {
        curlCommand += `-d '${body}'`;
    }

    // Imprimir o comando curl no console
    console.log(curlCommand);

    // Fazer a requisição
    let req = await fetch(fullUrl, { method, headers, body });
    let json = await req.json();
    return json;
}

export default {
    primeiroAcesso: async () => {
        let a = await AsyncStorage.getItem('primeiroacesso'); 
        let r = false;
        if(a == 'true'){
            r = true;
        }
        return r;
    },
    setPrimeiroAcesso: async () => {
        await AsyncStorage.setItem('primeiroacesso', 'true');
        return true;
    },
    getToken: async () => {
        return await getAuthToken();
    },
    validateToken: async () => {
        let token = await getAuthToken();
        let json = await request('post', '/auth/validate', {}, token);
        return json;
    },
    updatetokenpush: async (mobile) => {
        let token = await getAuthToken();
        let json = await request('post', '/auth/updatetokenpush', {mobile}, token);
        return json;
    },
    getMyInfo: async () => {
        let token = await getAuthToken();
        let json = await request('post', '/auth/myinfo', {}, token);
        return json;
    },
    buscarClientes: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/cliente', {}, token);
        return json;
    },
    recuperarsenha: async (cpf) => {
        let json = await request('post', '/auth/esquecisenha', {cpf});
        return json;
    },
    getLocationGeocode: async (latitude, longitude) => {
        let req = await fetch(`https://maps.google.com/maps/api/geocode/json?key=${MapsApi}&address=${latitude},${longitude}&sensor=false`);
        let json = await req.json();
        return json;
    },
    getGeocodeSearch: async (endereco) => {
        let req = await fetch(`https://maps.googleapis.com/maps/api/place/autocomplete/json?input=${encodeURIComponent(endereco)}&key=${MapsApi}&region=BA&language=pt`);
        let json = await req.json();
        return json;
    },
    getGeocodeSearchPlaceid: async (placeid) => {
        let req = await fetch(`https://maps.googleapis.com/maps/api/place/details/json?placeid=${placeid}&key=${MapsApi}`);
        let json = await req.json();
        return json;
    },
    login: async (usuario, password) => {
        
        try {
      
            let json = await request('post', '/auth/login', {usuario, password});
            console.log(json)
            return json;
        } catch (error) {
            console.error('Erro na requisição:', error);
            throw error; // Se desejar tratar o erro posteriormente no chamador da função
        }
    },
    logout: async () => {
        let token = await getAuthToken();
        let json = await request('post', '/auth/logout', {}, token);
        await removeAuthToken();
        return json;
    },
    baixaManualCobrador: async (id, dt, valor) => {
        let token = await getAuthToken();
        let json = await request('post', `/parcela/${id}/baixamanualcobrador`, { dt_baixa:  dt, valor: valor}, token);
        return json;
    },

    baixaManual: async (id, dt, valor) => {
        let token = await getAuthToken();
        let json = await request('post', `/parcela/${id}/baixamanual`, { dt_baixa:  dt, valor: valor}, token);
        return json;
    },

    cancelarBaixaManual: async (id) => {
        let token = await getAuthToken();
        let json = await request('get', `/parcela/${id}/cancelarbaixamanual`, {}, token);
        return json;
    },

    cadastroCliente: async (name, email, cellphone, cellphone2, cpf, rg, nascimento, sexo, localizacao, pix) => {
        let token = await getAuthToken();
        console.log('cadasro', {
            nome_completo       : name,
            cpf                 : cpf,
            rg                  : rg,
            data_nascimento     : nascimento,
            sexo                : sexo,
            telefone_celular_1  : cellphone,
            telefone_celular_2  : cellphone2,
            email               : email,
            observation         : '',
            limit               : 1000,
            password            : 1234,
            address             : [localizacao]
        })
        let json = await request('post', `/cliente`, {
            nome_completo       : name,
            cpf                 : cpf,
            rg                  : rg,
            data_nascimento     : nascimento,
            sexo                : sexo,
            telefone_celular_1  : cellphone,
            telefone_celular_2  : cellphone2,
            email               : email,
            observation         : '',
            limit               : 1000,
            password            : 1234,
            address             : [localizacao],
            pix_cliente         : pix
        }, token);
        return json;
    },
    cobrarAmanha: async (id, dt) => {
        let token = await getAuthToken();
        let json = await request('post', `/parcela/${id}/cobraramanha`, { dt_ult_cobranca:  dt}, token);
        return json;
    },
    getParcelasInfoEmprestimo: async (id, dt) => {
        let token = await getAuthToken();
        let json = await request('post', `/parcela/${id}/infoemprestimo`, {}, token);
        return json;
    },
    getAllClientes: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/cliente', {}, token);
        return json;
    },
    getClientesPendentes: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/cobranca/atrasadas', {}, token);
        return json;
    },
    pendentesParaHoje: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/baixa/pendentesparahoje', {}, token);
        return json;
    },
    transferenciaConsultar: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/transferenciaconsultar/${id}`, {}, token);
        return json;
    },
    transferenciaEfetivar: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/transferencia/${id}`, {}, token);
        return json;
    },
    transferenciaTituloConsultar: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/transferenciatituloconsultar/${id}`, {}, token);
        return json;
    },
    reprovarPagamentoContasAPagar: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/reprovarcontasapagar/${id}`, {}, token);
        return json;
    },
    reprovarEmprestimo: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/reprovaremprestimo/${id}`, {}, token);
        return json;
    },
    transferenciaTituloEfetivar: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/contaspagar/pagamentos/transferenciatitulo/${id}`, {}, token);
        return json;
    },
    emprestimosPendentesParaHoje: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/contaspagar/pagamentos/pendentesaplicativo', {}, token);
        console.log(json);
        return json;
    },
    parcelasParaExtorno: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/baixa/parcelasparaextorno', {}, token);
        return json;
    },
    getFeriados: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/feriados', {}, token);
        return json;
    },
    searchbanco: async () => {
        let token = await getAuthToken();
        let json = await request('post', '/emprestimo/search/banco', { name: '' }, token);
        return json;
	},
    getLocationGeocode: async (latitude, longitude) => {
        let req = await fetch(`https://maps.google.com/maps/api/geocode/json?key=AIzaSyDvNypCJVAfgPJ1nmrqZvz25wSbW3JOjUc&address=${latitude},${longitude}&sensor=false`);
        let json = await req.json();
        return json;
    },
    getEnderecoLatLong: async (cep) => {
        let req = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        let json = await req.json();
        return json;
    },
	searchCostcenter: async () => {
        let token = await getAuthToken();
        let json = await request('post', '/emprestimo/search/costcenter', { name: `` }, token);
        return json;
	},
    saveEmprestimo: async (permissions) => {
        let token = await getAuthToken();
        let json = await request('post', '/emprestimo', permissions, token);

        return json;
	},
    register: async (nome_completo, nome_social,  cpf,  password,  telefone_celular, email, data_nascimento, sexo, pcd, rg, usar_social, pcd_tipo) => {
        let json = await request('post', '/auth/register', {
            nome_completo, nome_social,  cpf,  password,  telefone_celular, email, data_nascimento, sexo, pcd, rg, usar_social, pcd_tipo
        });
        return json;
    },
    alterUsuario: async (nome_social,  password,  telefone_celular, data_nascimento, sexo, pcd, rg, usar_social, pcd_tipo ) => {
        let token = await getAuthToken();
        let json = await request('post', '/auth/alterusuario', {
            nome_social,  password,  telefone_celular, data_nascimento, sexo, pcd, rg, usar_social, pcd_tipo
        }, token);
        return json;
    },
    getMyAllEnderecos: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/endereco/getmyall', {}, token);
        return json;
    },
    getMyNotificacoes: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/notificacao', {}, token);
        return json;
    },
    updateNotificacao: async () => {
        let token = await getAuthToken();
        let json = await request('post', `/notificacao/update`, {}, token);
        return json;
    },
    getUltimasOcorrencias: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/ocorrencia/getminhasultimasocorrencias', {}, token);
        return json;
    },
    postExtornarParcela: async (id) => {
        let token = await getAuthToken();
        let json = await request('get', `/parcela/${id}/cancelarbaixamanual`, {}, token);
        return json;
    },
    
    getOcorrenciasApi: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/ocorrencias', {}, token);
        return json;
    },
    getCentralDeServicosApi: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/centralservicos', {}, token);
        return json;
    },
    hasOccurrence2Hours: async (ocorrencia_tipo) => {
        let token = await getAuthToken();
        let json = await request('get', '/ocorrencia/has2hours', {
            "ocorrencia_tipo": [
              "DISPARO DE ARMA DE FOGO COM VÍTIMA",
              "DISPARO DE ARMA DE FOGO SEM VÍTIMA"
            ]
          }, token);

        console.log('ASADS', json)
        return json;
    },
    hasOccurrence2Hours: async (ocorrencia_tipo) => {
        const token = await getAuthToken();

        try {
      
        const response = await axios.get(`${baseUrl}/ocorrencia/has2hours`, {
            headers: {
                Authorization: `Bearer ${token}`
            },
            params: {
                ocorrencia_tipo: ocorrencia_tipo
            }
            });
      
          return response.data;
        } catch (error) {
          console.error('Erro na requisição:', error);
          throw error; // Se desejar tratar o erro posteriormente no chamador da função
        }
    },
    addCadastroLocalFavorito: async (location_tipo, location_desc, location_referencia, location_geolocation) => {
        let token = await getAuthToken();
        let json = await request('post', '/endereco/add', {location_tipo, location_desc, location_referencia, location_geolocation}, token);
        return json;
    },
    saveOcorrencia: async (ocorrencia_tipo, ocorrencia_perguntas, ocorrencia_endereco, ocorrencia_pontoreferencia, ocorrencia_geolocation) => {
        let token = await getAuthToken();
        let json = await request('post', '/ocorrencia/add', {ocorrencia_tipo, ocorrencia_perguntas, ocorrencia_endereco, ocorrencia_pontoreferencia, ocorrencia_geolocation}, token);
        return json;
    },
    getWall: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/walls', {}, token);
        return json;
    },
    likeWallPost: async (id) => {
        let token = await getAuthToken();
        let json = await request('post', `/wall/${id}/like`, {}, token);
        return json;
    },
    getDocs: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/docs', {}, token);
        return json;
    },
    getBillets: async () => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('get', '/billets', {
            property: property.id
        }, token);
        return json;
    },
    getWarnings: async () => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('get', '/warnings', {
            property: property.id
        }, token);
        return json;
    },
    addWarningFile: async (file) => {
        let token = await getAuthToken();
        let formData = new FormData();
        formData.append('photo', {
            uri: file.uri,
            type: file.type,
            name: file.fileName
        });
        let req = await fetch(`${baseUrl}/warning/file`, {
            method: 'POST',
            headers: {
                'Content-Type': 'multipart/form-data',
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        let json = await req.json();
        return json;
    },
    addWarning: async (title, list) => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('post', '/warning', {
            title,
            list,
            property: property.id
        }, token);
        return json;
    },
    getReservations: async () => {
        let token = await getAuthToken();
        let json = await request('get', '/reservations', {}, token);
        return json;
    },
    getDisabledDates: async (id) => {
        let token = await getAuthToken();
        let json = await request('get', `/reservation/${id}/disableddates`, {}, token);
        return json;
    },
    getReservationTimes: async (id, date) => {
        let token = await getAuthToken();
        let json = await request('get', `/reservation/${id}/times`, {date}, token);
        return json;
    },
    setReservation: async (id, date, time) => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('post', `/reservation/${id}`, {
            property: property.id,
            date,
            time
        }, token);
        return json;
    },
    getMyReservations: async () => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('get', '/myreservations', {
            property: property.id
        }, token);
        return json;
    },
    removeReservation: async (id) => {
        let token = await getAuthToken();
        let json = await request('delete', `/myreservations/${id}`, {}, token);
        return json;
    },
    getFoundAndLost: async () => {
        let token = await getAuthToken();
        let json = await request('get', `/foundandlost`, {}, token);
        return json;
    },
    setRecovered: async (id) => {
        let token = await getAuthToken();
        let json = await request('put', `/foundandlost/${id}`, {
            status: 'recovered'
        }, token);
        return json;
    },
    addLostItem: async (photo, description, where) => {
        let token = await getAuthToken();
        let formData = new FormData();
        formData.append('description', description);
        formData.append('where', where);
        formData.append('photo', {
            uri: photo.uri,
            type: photo.type,
            name: photo.fileName
        });
        let req = await fetch(`${baseUrl}/foundandlost`, {
            method: 'POST',
            headers: {
                'Content-Type': 'multipart/form-data',
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        let json = await req.json();
        return json;
    },
    getUnitInfo: async () => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);
        let json = await request('get', `/unit/${property.id}`, {}, token);
        return json;
    },
    removeUnitItem: async (type, id) => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);

        let json = await request('post', `/unit/${property.id}/remove${type}`, {
            id
        }, token);
        return json;
    },
    addUnitItem: async (type, body) => {
        let token = await getAuthToken();
        let property = await AsyncStorage.getItem('property');
        property = JSON.parse(property);

        let json = await request('post', `/unit/${property.id}/add${type}`, body, token);
        return json;
    }
};