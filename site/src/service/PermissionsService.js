import store from '@/store';
import { useRouter } from 'vue-router';
import axios from 'axios';
const apiPath = import.meta.env.VITE_APP_BASE_URL;
export default class PermissionsService {

    constructor() {
		this.router = useRouter();
	}

    hasPermissions = (permission) => {
        return store.getters?.permissions.includes(permission)
    };

    hasPermissionsView = (permission) => {
        if(!store.getters?.permissions.includes(permission)){
            this.router.push({ name: 'accessDenied' });
        }
    };

    isMaster = () => {
        console.log(store.getters?.usuario.login);
        return store.getters?.usuario.login === "MASTERGERAL";
    };

    get = async (id) => {
		return await axios.get(`${apiPath}/permission_groups/${id}`);
	};

    getAll = async () => {
		return await axios.get(`${apiPath}/permission_groups`);
	};

    getAllUsers = async () => {
		return await axios.get(`${apiPath}/usuariocompanies`);
	};

    getItems = async () => {
		return await axios.get(`${apiPath}/permission_items`);
	};

    getItemsGroup = async (id) => {
		return await axios.get(`${apiPath}/permission_groups/items/${id}`);
	};

    deletePermission = async (id) => {
		return await axios.get(`${apiPath}/permission_groups/${id}/delete`);
	};

    save = async (permissions) => {
        if (undefined === permissions.id) return await axios.post(`${apiPath}/permission_groups`, permissions);
		else return await axios.put(`${apiPath}/permission_groups/${permissions.id}`, permissions);
        

	};

}
