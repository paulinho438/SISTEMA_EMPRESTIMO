import AsyncStorage from '@react-native-async-storage/async-storage';
import { ACCESS_TOKEN, ON_BOARDING } from '../common/constant';

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
  const stringData = JSON.stringify(value);
  await AsyncStorage.setItem(ACCESS_TOKEN, stringData);
  return;
};

export {OnBoardingToken, authToken, StorageValue};
