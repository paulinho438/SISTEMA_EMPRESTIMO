import AsyncStorage from '@react-native-async-storage/async-storage';
import {ACCESS_TOKEN, ON_BOARDING, AUTH_COMPANY, USER, PERMISSION, TIPOCLIENTE, COMPANIES} from '../common/constant';

const StorageValue = async () => {
  let asyncData = await AsyncStorage.multiGet([ON_BOARDING, ACCESS_TOKEN]);
  const OnBoardingDataValue = !!asyncData[0][1] ? asyncData[0][1] : false;
  const authDataValue = !!asyncData[1][1] ? JSON.parse(asyncData[1][1]) : false;
  return {OnBoardingDataValue, authDataValue};
};

const OnBoardingToken = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(ON_BOARDING, stringData);
  return;
};

const authToken = async value => {
  await AsyncStorage.setItem(ACCESS_TOKEN, value);
  return;
};

const getAuthToken = async () => {
  return await AsyncStorage.getItem(ACCESS_TOKEN);
};

const authCompany = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(AUTH_COMPANY, stringData);
  return;
};

const getAuthCompany = async () => {
  let req = await AsyncStorage.getItem(AUTH_COMPANY);
  return JSON.parse(req);
};

const user = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(USER, stringData);
  return;
};

const tipoCliente = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(TIPOCLIENTE, stringData);
  return;
};

const getTipoCliente = async () => {
  let req = await AsyncStorage.getItem(TIPOCLIENTE);
  try {
    return JSON.parse(req); // transforma '"cliente"' → 'cliente'
  } catch {
    return req?.toLowerCase().trim(); // fallback se não for JSON
  }
};


const getUser = async () => {
  let req = await AsyncStorage.getItem(USER);
  return JSON.parse(req);
};

const permissions = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(PERMISSION, stringData);
  return;
};

const companies = async value => {
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(COMPANIES, stringData);
  return;
};

const getCompanies = async () => {
  let req = await AsyncStorage.getItem(COMPANIES);
  return JSON.parse(req);
};

const getPermissions = async () => {
  let req = await AsyncStorage.getItem(PERMISSION);
  return JSON.parse(req);
};

const removeAuthToken = async () => {
  await AsyncStorage.removeItem(ACCESS_TOKEN);
  return;
};

const removeTipoCliente = async () => {
  await AsyncStorage.removeItem(TIPOCLIENTE);
  return;
};

const clearAuthSession = async () => {
  await AsyncStorage.multiRemove([
    ACCESS_TOKEN,
    AUTH_COMPANY,
    USER,
    PERMISSION,
    TIPOCLIENTE,
    COMPANIES,
  ]);
  return;
};

export {OnBoardingToken, authToken, StorageValue, getAuthToken, removeAuthToken, authCompany, getAuthCompany, user, getUser, permissions, getPermissions, tipoCliente, getTipoCliente, removeTipoCliente, companies, getCompanies, clearAuthSession};
