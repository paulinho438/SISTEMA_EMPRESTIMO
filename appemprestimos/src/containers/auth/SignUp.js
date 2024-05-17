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
import CBackButton from '../../components/common/CBackButton';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {colors} from '../../themes/colors';
import CTextInput from '../../components/common/CTextInput';
import CButton from '../../components/common/CButton';
import {moderateScale} from '../../common/constant';
import images from '../../assets/images/index';
import {AuthNav} from '../../navigation/navigationKeys';
import KeyBoardAvoidWrapper from '../../components/common/KeyBoardAvoidWrapper';
import {
  validateEmail,
  validateName,
  validatePassword,
} from '../../utils/validation';

const BlurStyle = {
  borderColor: colors.white,
};

const FocusStyle = {
  borderColor: colors.numbersColor,
};

export default function SignUp({navigation}) {
  const [focus, setFocus] = useState(BlurStyle);
  const [focus2, setFocus2] = useState(BlurStyle);
  const [focus3, setFocus3] = useState(BlurStyle);

  const [name, setName] = useState('');
  const [message, setMessage] = useState(false);

  const [email, setEmail] = useState('');
  const [message2, setMessage2] = useState(false);

  const [pass, setPass] = useState('');
  const [message3, setMessage3] = useState(false);

  const onPressSignUp = () => {
    // if (name === '' || message2 || message3) {
    //   Alert.alert(strings.PleaseFill);
    // } else {
    //   navigation.navigate(AuthNav.CountryRes);
    // }
    navigation.navigate(AuthNav.CountryRes);
  };

  const nameValidation = itm => {
    const {msg} = validateName(itm);
    setName(itm);
    setMessage(msg);
  };

  const emailValidation = itm => {
    const {msg} = validateEmail(itm);
    setEmail(itm);
    setMessage2(msg);
  };

  const passValidation = itm => {
    const {msg} = validatePassword(itm);
    setPass(itm);
    setMessage3(msg);
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

  const onFocus3 = () => {
    onFocusInput(setFocus3);
  };

  const onBlur3 = () => {
    onBlurInput(setFocus3);
  };

  const onFocusInput = onHighlight => {
    onHighlight(FocusStyle);
  };
  const onBlurInput = onHighlight => {
    onHighlight(BlurStyle);
  };

  const backToSignIn = () => {
    navigation.navigate(AuthNav.SignIn);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerMainContainer}>
        <KeyBoardAvoidWrapper>
          <View>
            <CBackButton onPress={backToSignIn} />
            <CText
              color={colors.black}
              type={'B24'}
              style={localStyles.mainTxt}>
              Cadastrar 
              <CText color={colors.black} style={localStyles.bulkpay}>
                 Cliente
              </CText>
            </CText>
          

            <View style={localStyles.threeEle}>
              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'Nome Completo'}
              />

              {message ? <CText color={colors.red}>{message}</CText> : null}

              <CTextInput
                value={email}
                onChangeText={emailValidation}
                mainTxtInp={[localStyles.border, focus2]}
                onFocus={onFocus2}
                onBlur={onBlur2}
                text={'E-mail'}
              />

              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'Telefone Principal'}
              />

              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'Telefone Secundario'}
              />    

              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'CPF'}
              /> 

              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'RG'}
              /> 

              {message2 ? <CText color={colors.red}>{message2}</CText> : null}

              <CTextInput
                mainTxtInp={[localStyles.border, focus3]}
                onFocus={onFocus3}
                onBlur={onBlur3}
                text={'password'}
                value={pass}
                onChangeText={passValidation}
                isSecure={true}
              />

              {message3 ? <CText color={colors.red}>{message3}</CText> : null}

            </View>

            <View style={localStyles.threeEle}>
              <CTextInput
                value={name}
                onChangeText={nameValidation}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'Full name'}
              />

              {message ? <CText color={colors.red}>{message}</CText> : null}

              <CTextInput
                value={email}
                onChangeText={emailValidation}
                mainTxtInp={[localStyles.border, focus2]}
                onFocus={onFocus2}
                onBlur={onBlur2}
                text={'email'}
              />

              {message2 ? <CText color={colors.red}>{message2}</CText> : null}

              <CTextInput
                mainTxtInp={[localStyles.border, focus3]}
                onFocus={onFocus3}
                onBlur={onBlur3}
                text={'password'}
                value={pass}
                onChangeText={passValidation}
                isSecure={true}
              />

              {message3 ? <CText color={colors.red}>{message3}</CText> : null}

            </View>
            

            <CButton text={'Cadastrar'} onPress={onPressSignUp} />

            <View style={localStyles.parentOr}>
              <View style={localStyles.firstLine} />
              <CText color={colors.black} style={localStyles.OrTxt}>
              </CText>
              <View style={localStyles.firstLine} />
            </View>

            
          </View>
        </KeyBoardAvoidWrapper>
        
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  bulkpay: {
    color: colors.SignUpTxt,
  },
  mainTxt: {
    ...styles.mt30,
  },
  parentOr: {
    ...styles.center,
    ...styles.flexRow,
  },
  firstLine: {
    width: moderateScale(133),
    height: moderateScale(1),
    backgroundColor: colors.google,
  },
  OrTxt: {
    ...styles.ph20,
    ...styles.pv40,
  },
  mainSocial: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  parentGoogle: {
    width: moderateScale(155),
    height: moderateScale(56),
    borderRadius: moderateScale(16),
    borderWidth: moderateScale(1),
    borderColor: colors.google,
    ...styles.center,
  },
  google: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  Apple: {
    width: moderateScale(20),
    height: moderateScale(24),
  },
  AlreadyTxt: {
    ...styles.mb40,
  },
  SignInTxt: {
    color: colors.SignUpTxt,
  },
  threeEle: {
    ...styles.mv10,
    gap: moderateScale(15),
  },
  border: {
    backgroundColor: colors.GreyScale,
    borderWidth: moderateScale(1),
  },
  outerMainContainer: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
});
