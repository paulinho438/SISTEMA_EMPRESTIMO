import {
  SafeAreaView,
  StyleSheet,
  Image,
  View,
  TouchableOpacity,
} from 'react-native';
import React from 'react';
import Fonisto from 'react-native-vector-icons/Fontisto';

// Local imports
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import strings from '../../i18n/strings';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import CHeader from '../common/CHeader';

export default function ReferralCode() {
  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper>
        <View style={localStyles.mainView}>
          <CHeader color={colors.black} />
          <Image source={images.DollarsBank} style={localStyles.imgSty} />

          <View style={localStyles.parentComponent}>
            <CText color={colors.black} type={'B24'} align={'center'}>
              {strings.BonusTxt}
              <CText color={colors.numbersColor}>{strings.Free}</CText>
              <CText color={colors.black}>{strings.Onus}</CText>
            </CText>
            <CText type={'R16'} color={colors.tabColor} align={'center'}>
              {strings.ShareLink}
            </CText>
          </View>

          <CTextInput
            LeftIcon={() => (
              <TouchableOpacity>
                <Fonisto
                  name={'paste'}
                  size={24}
                  color={colors.tabColor}
                  style={localStyles.pasteSty}
                />
              </TouchableOpacity>
            )}
            RightIcon={() => (
              <TouchableOpacity>
                <CText
                  style={localStyles.pasteSty}
                  type={'B14'}
                  color={colors.numbersColor}>
                  {strings.Share}
                </CText>
              </TouchableOpacity>
            )}
            align={'center'}
            text={'FGX4456R'}
            mainTxtInp={localStyles.TxtInpSty}
            textInputStyle={localStyles.childCompo}
          />

          <View style={localStyles.bottomBorder} />

          <View style={{gap: moderateScale(40)}}>
            <View style={{gap: moderateScale(10)}}>
              <CText color={colors.black} type={'B24'} align={'center'}>
                {strings.GetFree}
                <CText color={colors.numbersColor}>{strings.Free}</CText>
              </CText>

              <CText type={'R16'} align={'center'} color={colors.tabColor}>
                {strings.ForAnyAcc}
              </CText>
            </View>

            <View style={localStyles.mainButton}>
              <TouchableOpacity style={localStyles.mainGoogle}>
                <Image style={localStyles.GoogleStyle} source={images.Google} />
              </TouchableOpacity>

              <TouchableOpacity style={localStyles.mainGoogle}>
                <Image style={localStyles.AppleStyle} source={images.Apple} />
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </KeyBoardAvoidWrapper>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  imgSty: {
    width: moderateScale(246),
    height: moderateScale(205),
    ...styles.selfCenter,
  },
  parentComponent: {
    ...styles.mt50,
    gap: moderateScale(10),
  },
  TxtInpSty: {
    ...typography.fontSizes.f14,
    ...typography.fontWeights.Medium,
    ...styles.mt40,
    backgroundColor: colors.GreyScale,
  },
  childCompo: {
    ...typography.fontSizes.f14,
    ...typography.fontWeights.Medium,
  },
  pasteSty: {
    ...styles.mh20,
  },
  bottomBorder: {
    ...styles.mv20,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  mainButton: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  mainGoogle: {
    width: moderateScale(155),
    height: moderateScale(56),
    backgroundColor: colors.GreyScale,
    borderRadius: moderateScale(16),
    borderWidth: moderateScale(1),
    borderColor: colors.google,
    ...styles.center,
  },
  GoogleStyle: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  AppleStyle: {
    width: moderateScale(20),
    height: moderateScale(24),
  },
  mainView: {
    ...styles.mh20,
  },
});
