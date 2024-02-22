/**
 * @format
 */

import {AppRegistry} from 'react-native';
import App from './src';
import {name as appName} from './app.json';

const RNRoot = () => {
  return <App />;
};

AppRegistry.registerComponent(appName, () => RNRoot);
