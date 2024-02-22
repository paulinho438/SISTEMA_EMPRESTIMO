import {LogBox, StatusBar, View} from 'react-native';
import React, {useEffect} from 'react';
import AppNavigator from './navigation';
import {styles} from './themes';

const App = () => {
  useEffect(() => {
    LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
    LogBox.ignoreAllLogs(); //Ignore all log notifications
  }, []);

  return (
    <View style={styles.flex}>
      <StatusBar />
      <AppNavigator />
    </View>
  );
};

export default App;
