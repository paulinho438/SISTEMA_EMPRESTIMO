import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export default class DaytradeService {
    get = async () => {
        return await axios.get(`${apiPath}/daytrade`);
    };

    save = async (dados) => {
        return await axios.post(`${apiPath}/daytrade`, dados);
    };
}
