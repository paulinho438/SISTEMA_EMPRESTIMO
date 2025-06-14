import {Dimensions, Platform} from 'react-native';

//Device dimensions
const {width: viewportWidth, height: viewportHeight} = Dimensions.get('screen');
export const deviceWidth = viewportWidth;
export const deviceHeight = viewportHeight;

let sampleHeight = 926;
let sampleWidth = 428;

const scale = viewportWidth / 375;

//Device type check
export const isIOS = Platform.OS === 'ios';
export const isAndroid = Platform.OS === 'android';
export const isTablet = viewportHeight / viewportWidth < 1.6;

//Get Width of Screen
export function getWidth(value) {
  return (value / sampleWidth) * deviceWidth;
}

//Get Height of Screen
export function getHeight(value) {
  return (value / sampleHeight) * deviceHeight;
}

// Responsive size function
export function moderateScale(size) {
  const newSize = size * scale;
  return Math.round(newSize);
}

export const checkPlatform = () => {
  if (Platform.OS === 'android') {
    return 'android';
  } else {
    return 'ios';
  }
};

export const ON_BOARDING = 'ON_BOARDING';
export const ACCESS_TOKEN = 'ACCESS_TOKEN';
export const AUTH_COMPANY = 'AUTH_COMPANY';
export const USER = 'USER';
export const PERMISSION = 'PERMISSION';
export const TIPOCLIENTE = 'TIPOCLIENTE';

