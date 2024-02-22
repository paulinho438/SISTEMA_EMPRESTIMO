import {SafeAreaView, StyleSheet, View, Image} from 'react-native';
import React from 'react';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import strings from '../../i18n/strings';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import CButton from '../../components/common/CButton';
import {AuthNav} from '../../navigation/navigationKeys';
import {colors} from '../../themes/colors';

export default function CardOnBoarding({navigation}) {
  const moveToStyle = () => {
    navigation.navigate(AuthNav.CardStyle);
  };
  return (
    <SafeAreaView style={{backgroundColor: colors.white}}>
      <View style={localStyles.main}>
        <View>
          <Image style={localStyles.imgStyle} source={images.Cards} />
          <CText color={colors.black} type={'B32'} style={localStyles.cardTxt}>
            {strings.CreateCard}
          </CText>
          <CText color={colors.black} style={localStyles.detailsTxt}>
            {strings.CardDetail}
          </CText>
        </View>
        <CButton
          text={'Get Free Card'}
          ParentLoginBtn={localStyles.CButtonTxt}
          onPress={moveToStyle}
        />
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.mh20,
    ...styles.justifyBetween,
    height: '100%',
  },
  imgStyle: {
    width: moderateScale(288),
    height: moderateScale(351),
    ...styles.mt50,
    ...styles.mh20,
  },
  cardTxt: {
    ...styles.mt40,
  },
  detailsTxt: {
    ...styles.mt20,
  },
  CButtonTxt: {
    ...styles.mb30,
  },
});
