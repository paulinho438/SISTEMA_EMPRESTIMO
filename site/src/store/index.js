import { createStore } from 'vuex';
import createPersistedState from 'vuex-persistedstate';

const store = createStore({
	state() {
		return {
			isAutenticated: false,
			isCompany: null,
			usuario: null,
			permissions: [],
		};
	},
	mutations: {
		setAuthenticated(state) {
			state.isAutenticated = !state.isAutenticated;
		},
		setCompany(state, company){
			state.isCompany = company;
		},
		setUsuario(state, usuario){
			state.usuario = usuario;
		},
		setPermissions(state, newPermissions){
			state.permissions = newPermissions;
		}
		
	},
	actions: {
		// Adicione uma ação que executa a função e retorna true ou false
		hasPermissions(context, payload) {
			return context.state.permissions.includes(payload)
		},
	},
	getters: {
		isAutenticated(state) {
			return state.isAutenticated;
		},
		isCompany(state) {
			return state.isCompany;
		},
		permissions(state) {
			return state.permissions;
		},
		usuario(state) {
			return state.usuario;
		},
	},
  	plugins: [createPersistedState()],
});

export default store;
