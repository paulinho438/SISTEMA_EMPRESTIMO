import React, {useEffect, useRef} from 'react';
import {SafeAreaView, View, Platform, StyleSheet} from 'react-native';
import {request, PERMISSIONS, RESULTS} from 'react-native-permissions';
import {Camera, CameraType} from 'react-native-camera-kit';
import {colors} from '../../themes/colors';
import CText from '../../components/common/CText';

const App = ({navigation}) => {
  const cameraRef = useRef(null);

  useEffect(() => {
    checkOpenScanner();
  }, []);

  const checkOpenScanner = async () => {
    console.log('checkOpenScanner');
    if (Platform.OS === 'android') {
      await request(PERMISSIONS.ANDROID.CAMERA)
        .then(result => {
          switch (result) {
            case RESULTS.DENIED:
              console.log('The permission has not been requested / is denied');
              break;
            case RESULTS.GRANTED:
              console.log('The permission is granted');
              break;
            case RESULTS.BLOCKED:
              console.log(
                'The permission is denied and not requestable anymore',
              );
              break;
          }
        })
        .catch(error => {
          console.log('error', error);
        });
    } else {
      await request(PERMISSIONS.IOS.CAMERA)
        .then(result => {
          switch (result) {
            case RESULTS.DENIED:
              console.log('The permission has not been requested / is denied');
              break;
            case RESULTS.GRANTED:
              console.log('The permission is granted');
              break;
            case RESULTS.BLOCKED:
              console.log(
                'The permission is denied and not requestable anymore',
              );
              break;
          }
        })
        .catch(error => {
          console.log('error', error);
        });
    }
  };

  return (
    <SafeAreaView style={localStyles.mainContainer}>
      <View style={localStyles.innerContainer}>
        <CText
          type={'R20'}
          color={colors.white}
          align={'center'}
          style={{
            marginTop: 30,
          }}>
          {'Scan QR Code'}
        </CText>
        <Camera
          ref={cameraRef}
          resetFocusWhenMotionDetected
          cameraType={CameraType.Back}
          flashMode="auto"
          style={localStyles.cameraView}
        />
      </View>
    </SafeAreaView>
  );
};

export default App;

const localStyles = StyleSheet.create({
  mainContainer: {
    flex: 1,
    paddingHorizontal: 25,
    backgroundColor: '#111827',
  },
  cameraView: {
    height: 367,
    width: '100%',
    marginTop: 45,
    alingItems: 'center',
    justiftyContent: 'center',
  },
  innerContainer: {
    alingItems: 'center',
    justiftyContent: 'center',
  },
});
