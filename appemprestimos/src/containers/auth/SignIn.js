import {
  StyleSheet,
  View,
  SafeAreaView,
  TouchableOpacity,
  Image,
  Alert,
} from 'react-native';
import React, {useState} from 'react';

// Local imports
import CText from '../../components/common/CText';
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import {AuthNav, StackNav} from '../../navigation/navigationKeys';
import strings from '../../i18n/strings';
import CTextInput from '../../components/common/CTextInput';
import CButton from '../../components/common/CButton';
import {moderateScale} from '../../common/constant';
import images from '../../assets/images/index';
import {authToken} from '../../utils/asyncStorage';
import KeyBoardAvoidWrapper from '../../components/common/KeyBoardAvoidWrapper';
import {validateEmail, validatePassword} from '../../utils/validation';

const BlurStyle = {
  borderColor: colors.white,
};

const FocusStyle = {
  borderColor: colors.numbersColor,
};

export default function SignIn({navigation}) {
  const [focus, setFocus] = useState(BlurStyle);
  const [focus2, setFocus2] = useState(BlurStyle);

  const [email, setEmail] = useState('');
  const [showMessage, setShowMessage] = useState(false);

  const [changeValue, setChangeValue] = useState('');
  const [pass, setPass] = useState(false);

  const onPressSignIn = async () => {
    // if (email === '' || showMessage || changeValue === '') {
    //   Alert.alert(strings.PleaseFill);
    // } else {
    //   await authToken(true);
    //   navigation.reset({
    //     index: 0,
    //     routes: [{name: StackNav.TabNavigation}],
    //   });
    // }
    await authToken(true);
    navigation.reset({
      index: 0,
      routes: [{name: StackNav.TabNavigation}],
    });
  };

  const onFocus = () => {
    onFocusInput(setFocus);
  };

  const onBlur = () => {
    onBlurInput(setFocus);
  };

  const onFocus2 = () => {
    onFocusInput(setFocus2);
  };

  const onBlur2 = () => {
    onBlurInput(setFocus2);
  };

  const onFocusInput = onHighlight => {
    onHighlight(FocusStyle);
  };
  const onBlurInput = onHighlight => {
    onHighlight(BlurStyle);
  };

  const changeText = txt => {
    const {msg} = validatePassword(txt);
    setChangeValue(txt);
    setPass(msg);
  };

  const moveToPassRec = () => {
    navigation.navigate(AuthNav.PassRecovery);
  };

  const moveToSignUp = () => {
    navigation.navigate(AuthNav.SignUp);
  };

  const setEmailFunction = item => {
    const {msg} = validateEmail(item);
    setEmail(item);
    setShowMessage(msg);
  };

  return (
    <SafeAreaView style={localStyles.mainParent}>
      <View style={localStyles.outerMainContainer}>
        <KeyBoardAvoidWrapper>
          <View>
            <SafeAreaView>
              
              <CText
                color={colors.black}
                style={localStyles.hiText}
                type={'B24'}>
                {strings.Hi}
              </CText>

              <CText
                color={colors.black}
                style={localStyles.welcomeText}
                type={'R16'}>
                {strings.WelcomeBack}
              </CText>

              <CTextInput
                value={email}
                onChangeText={setEmailFunction}
                onFocus={onFocus2}
                onBlur={onBlur2}
                mainTxtInp={[localStyles.PassTxt, focus2]}
                text={'Email'}
              />

              {showMessage ? (
                <CText color={colors.red}>{showMessage}</CText>
              ) : null}

              <CTextInput
                onFocus={onFocus}
                onBlur={onBlur}
                onChangeText={changeText}
                value={changeValue}
                mainTxtInp={[localStyles.PassTxt, focus]}
                text={'Password'}
                isSecure={true}
              />

              <TouchableOpacity
                style={localStyles.mainContainer}
                onPress={moveToPassRec}>
                <CText
                  type={'B16'}
                  color={colors.forgot}
                  style={localStyles.forgotPassTxt}>
                  {strings.forgotPass}
                </CText>
              </TouchableOpacity>
            </SafeAreaView>

            <CButton
              text={'Entrar'}
              ParentLoginBtn={localStyles.ParentSignIn}
              onPress={onPressSignIn}
            />

            
          </View>
        </KeyBoardAvoidWrapper>
        <View style={localStyles.NoHaveAcc}>
          <CText color={colors.black} type={'B16'}>
            {strings.NoHaveAcc}
          </CText>

          <TouchableOpacity onPress={moveToSignUp}>
            <CText
              color={colors.black}
              type={'B16'}
              style={localStyles.SignUpTxt}>
              {strings.SignUp}
            </CText>
          </TouchableOpacity>
        </View>
        <View style={localStyles.rjemprestimos}>
          <CText color={colors.black} type={'B14'}>
            RJ EMPRESTIMOS
          </CText>
        </View>
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  mainParent: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  hiText: {
    ...styles.mt25,
  },
  welcomeText: {
    ...styles.mv15,
  },
  PassTxt: {
    backgroundColor: colors.GreyScale,
    ...styles.mt20,
    borderWidth: moderateScale(1),
  },
  forgotPassTxt: {
    ...styles.mt30,
  },
  ParentSignIn: {
    ...styles.center,
    borderRadius: moderateScale(16),
    width: moderateScale(334),
  },
  mainOr: {
    ...styles.center,
    ...styles.flexRow,
  },
  firstLine: {
    width: moderateScale(133),
    height: moderateScale(1),
    backgroundColor: colors.google,
  },
  orTxt: {
    ...styles.ph20,
    ...styles.pv40,
  },
  GoogleStyle: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  mainGoogle: {
    width: moderateScale(155),
    height: moderateScale(56),
    borderRadius: moderateScale(16),
    borderWidth: moderateScale(1),
    borderColor: colors.google,
    ...styles.center,
  },
  AppleStyle: {
    width: moderateScale(20),
    height: moderateScale(24),
  },
  mainButton: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  NoHaveAcc: {
    ...styles.center,
    ...styles.mb40,
    ...styles.flexRow,
    
  },
  rjemprestimos: {
    ...styles.center,
    ...styles.flexRow,
    top: moderateScale(-20),
  },
  SignUpTxt: {
    color: colors.SignUpTxt,
    ...styles.ml5,
  },
  mainContainer: {
    width: '43%',
  },
  outerMainContainer: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
});
