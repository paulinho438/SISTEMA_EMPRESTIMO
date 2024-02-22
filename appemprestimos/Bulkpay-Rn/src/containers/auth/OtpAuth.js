import {StyleSheet, SafeAreaView, View, TouchableOpacity} from 'react-native';
import React, {useState} from 'react';

// Local imports
import CBackButton from '../../components/common/CBackButton';
import {styles} from '../../themes';
import {AuthNav} from '../../navigation/navigationKeys';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import OTPInputView from '@twotalltotems/react-native-otp-input';
import {moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import typography from '../../themes/typography';
import CButton from '../../components/common/CButton';

export default function ItsYou({navigation}) {
  const [otp, setOtp] = useState('');

  const onOtpChange = otp => setOtp(otp);

  const moveToBack = () => navigation.navigate(AuthNav.SignIn);

  const moveToCreatePass = () => navigation.navigate(AuthNav.CreatePass);

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerComponent}>
        <View>
          <CBackButton onPress={moveToBack} />
          <CText
            color={colors.black}
            style={localStyles.VerifyTxt}
            type={'B24'}>
            {strings.VerifyItsYou}
          </CText>
          <CText color={colors.black} style={localStyles.EnterEmailTxt}>
            {strings.EnterEmail}
          </CText>
          <View style={localStyles.ParenOtp}>
            <OTPInputView
              style={localStyles.otpInputStyle}
              pinCount={5}
              code={otp}
              onCodeChanged={onOtpChange}
              autoFocusOnLoad={false}
              codeInputFieldStyle={localStyles.underlineStyleBase}
            />
          </View>

          <TouchableOpacity style={localStyles.mainReset}>
            <CText type={'B16'} color={colors.SignUpTxt}>
              {strings.ResetCode}
            </CText>
          </TouchableOpacity>
        </View>

        <CButton
          ParentLoginBtn={localStyles.continue}
          onPress={moveToCreatePass}
        />
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  VerifyTxt: {
    ...styles.mt30,
  },
  EnterEmailTxt: {
    ...styles.mt20,
  },
  otpInputStyle: {
    height: moderateScale(50),
  },
  mainOtp: {
    ...styles.mt35,
  },
  underlineStyleBase: {
    width: moderateScale(56),
    height: moderateScale(56),
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(12),
    borderColor: colors.bottomBorder,
    ...typography.fontWeights.Bold,
    ...typography.fontSizes.f24,
    color: colors.black,
    borderColor: colors.SignUpTxt,
  },
  ParenOtp: {
    ...styles.mt35,
  },
  mainReset: {
    ...styles.center,
    ...styles.mt25,
  },
  continue: {
    ...styles.mb30,
  },
  outerComponent: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
});
