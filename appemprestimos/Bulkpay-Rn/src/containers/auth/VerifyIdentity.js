import {StyleSheet, View, SafeAreaView} from 'react-native';
import React from 'react';
import Ionicons from 'react-native-vector-icons/Ionicons';
import Material from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import {styles} from '../../themes';
import {Identity} from '../../assets/svgs';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CButton from '../../components/common/CButton';
import {AuthNav} from '../../navigation/navigationKeys';
import CHeader from '../../components/common/CHeader';

export default function VerifyIdentity({navigation}) {
  const moveToItsYou = () => {
    navigation.navigate(AuthNav.CreatePass);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={styles.ph20}>
        <View>
          <CHeader />
          <Identity style={localStyles.imgStyle} />
          <CText
            color={colors.black}
            type={'B24'}
            style={localStyles.verIdeTxt}>
            {strings.VerifyIdentity}
          </CText>
          <CText
            color={colors.black}
            type={'R16'}
            style={localStyles.whereCode}>
            {strings.WhereCode}
            <CText type={'B16'} color={colors.SignUpTxt}>
              {strings.WhereCode2}
            </CText>
            <CText color={colors.black}>{strings.WhereCode3}</CText>
          </CText>
          <View style={localStyles.Email}>
            <View style={localStyles.mainView}>
              <Ionicons
                name={'checkmark-circle'}
                size={moderateScale(24)}
                color={colors.SignUpTxt}
              />
              <View style={localStyles.EmailTxt}>
                <CText color={colors.black} type={'B18'}>
                  {strings.Email}
                </CText>
                <CText
                  color={colors.black}
                  type={'R16'}
                  style={localStyles.RealEmail}>
                  {strings.RealEmail}
                </CText>
              </View>
            </View>
            <Material
              name={'email-outline'}
              size={moderateScale(24)}
              color={colors.google}
            />
          </View>
        </View>
      </View>
      <CButton
        ParentLoginBtn={localStyles.ParentButton}
        onPress={moveToItsYou}
        text={'Continue'}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  imgStyle: {
    ...styles.mt35,
  },
  verIdeTxt: {
    ...styles.mt40,
  },
  whereCode: {
    ...styles.mt20,
  },
  mainView: {
    ...styles.rowCenter,
  },
  Email: {
    backgroundColor: colors.GreyScale,
    height: moderateScale(88),
    borderRadius: moderateScale(16),
    ...styles.mt40,
    ...styles.ph20,
    ...styles.rowSpaceBetween,
  },
  EmailTxt: {
    ...styles.pl20,
  },
  RealEmail: {
    ...styles.pt6,
  },
  ParentButton: {
    borderRadius: moderateScale(16),
    ...styles.mb30,
    width: '90%'
  },
});
