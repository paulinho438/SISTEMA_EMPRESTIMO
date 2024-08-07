import {View, Image, StyleSheet} from 'react-native';
import React, {useEffect} from 'react';

// Local imports
import images from '../assets/images/index';
import {moderateScale} from '../common/constant';
import {styles} from '../themes';
import {StorageValue} from '../utils/asyncStorage';
import {StackNav} from '../navigation/navigationKeys';
import {colors} from '../themes/colors';
import SplashScreen from 'react-native-splash-screen';

const Splash = ({navigation}) => {
  useEffect(() => {
    SplashScreen.hide();
    asyncProcess();
  }, []);

  const asyncProcess = async () => {
    try {
      let Data = await StorageValue();
      if (Data) {
        let {OnBoardingDataValue, authDataValue} = Data;
        if (!!authDataValue) {
          // navigation.replace(StackNav.TabNavigation);
          navigation.replace(StackNav.AuthNavigation);
        } else if (!!OnBoardingDataValue) {
          navigation.replace(StackNav.AuthNavigation);
        } else {
          // navigation.replace(StackNav.OnBoarding);
          navigation.replace(StackNav.AuthNavigation);
        }
      }
    } catch (e) {
      navigation.replace(StackNav.TabNavigation);
    }
  };

  return (
    <View style={localStyles.ParentSplash}>
      <Image style={localStyles.SplashStyle} source={images.logo} />
    </View>
  );
};

const localStyles = StyleSheet.create({
  ParentSplash: {
    backgroundColor: colors.white,
    ...styles.flex,
    ...styles.center,
  },
  SplashStyle: {
    width: moderateScale(148),
    height: moderateScale(130),
  },
});
export default Splash;
