import {
  SafeAreaView,
  StyleSheet,
  View,
  Image,
  TouchableOpacity,
} from 'react-native';
import React from 'react';

// Local imports
import CBackButton from '../../components/common/CBackButton';
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {AuthNav} from '../../navigation/navigationKeys';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {colors} from '../../themes/colors';

export default function CardStyle({navigation}) {
  const moveToNew = () => {
    navigation.navigate(AuthNav.NewCard);
  };

  const backToOnBoarding = () => {
    navigation.navigate(AuthNav.CardOnBoarding);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={styles.ph20}>
        <View style={localStyles.mainView}>
          <CBackButton onPress={backToOnBoarding} />
          <CText
            color={colors.black}
            type={'B18'}
            style={localStyles.ChooseStyleTxt}>
            {strings.ChooseStyle}
          </CText>
          <View></View>
          <View></View>
        </View>
        <TouchableOpacity onPress={moveToNew}>
          <Image style={localStyles.card1} source={images.card1} />
        </TouchableOpacity>
        <TouchableOpacity onPress={moveToNew}>
          <Image style={localStyles.card1} source={images.card3} />
        </TouchableOpacity>
        <TouchableOpacity onPress={moveToNew}>
          <Image style={localStyles.card1} source={images.card2} />
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.justifyBetween,
    ...styles.flex,
  },
  card1: {
    width: moderateScale(333),
    height: moderateScale(190),
    ...styles.mt10,
  },
  parentChoose: {
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  ChooseStyleTxt: {
    ...styles.alignCenter,
  },
  mainView: {
    ...styles.rowSpaceBetween,
    ...styles.alignCenter,
  },
});
