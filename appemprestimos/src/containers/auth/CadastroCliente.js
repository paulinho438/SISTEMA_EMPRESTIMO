import {
  StyleSheet,
  View,
  SafeAreaView,
  TouchableOpacity,
  Image,
  Alert,
  Text
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
import {AuthNav, StackNav} from '../../navigation/navigationKeys';
import KeyBoardAvoidWrapper from '../../components/common/KeyBoardAvoidWrapper';
import CDropdownInput from '../../components/common/CDropdownInput';

import api from '../../services/api';

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

export default function CadastroCliente({navigation}) {
  const [focus, setFocus] = useState(BlurStyle);
  const [focus2, setFocus2] = useState(BlurStyle);
  const [focus3, setFocus3] = useState(BlurStyle);
  const [focus4, setFocus4] = useState(BlurStyle);
  const [focus5, setFocus5] = useState(BlurStyle);
  const [focus6, setFocus6] = useState(BlurStyle);

  const [name, setName] = useState('');
  const [message, setMessage] = useState(false);

  const [email, setEmail] = useState('');
  const [message2, setMessage2] = useState(false);

  const [pass, setPass] = useState('');
  const [message3, setMessage3] = useState(false);

  const [cpf, setCpf] = useState('');
  const [rg, setRg] = useState('');
  const [cnpj, setCnpj] = useState('');
  const [cellphone, setCellphone] = useState('');
  const [cellphone2, setCellphone2] = useState('');
  const [nascimento, setNascimento] = useState('');
  const [sexo, setSexo] = useState('M');
  const [pix, setPix] = useState('');

  const data = [
    { label: 'Masculino', value: 'M' },
    { label: 'Feminino', value: 'F' },
  ];

  const handleChange = (item) => {
    setSexo(item.value);
  };

  const handleCpfChange = (text) => {
    // Remove qualquer caractere que não seja número
    let cleaned = text.replace(/\D/g, '');

    // Limita o número de caracteres a 11
    if (cleaned.length > 11) {
      cleaned = cleaned.substring(0, 11);
    }

    // Formata o CPF
    let formatted = cleaned;
    if (cleaned.length > 3 && cleaned.length <= 6) {
      formatted = cleaned.replace(/(\d{3})(\d+)/, '$1.$2');
    } else if (cleaned.length > 6 && cleaned.length <= 9) {
      formatted = cleaned.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
    } else if (cleaned.length > 9) {
      formatted = cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4');
    }

    setCpf(formatted);
  };


  const handleRgChange = (text) => {
    // Remove qualquer caractere que não seja número
    let cleaned = text.replace(/\D/g, '');

    // Limita o número de caracteres a 9 (XX.XXX.XXX-X)
    if (cleaned.length > 9) {
      cleaned = cleaned.substring(0, 9);
    }

    // Formata o RG
    let formatted = cleaned;
    if (cleaned.length > 2 && cleaned.length <= 5) {
      formatted = cleaned.replace(/(\d{2})(\d+)/, '$1.$2');
    } else if (cleaned.length > 5 && cleaned.length <= 8) {
      formatted = cleaned.replace(/(\d{1})(\d{3})(\d+)/, '$1.$2.$3');
    } else if (cleaned.length > 8) {
      formatted = cleaned.replace(/(\d{2})(\d{3})(\d{4})(\d+)/, '$1.$2.$3-$4');
    }

    setRg(formatted);
  };

  const handleCnpjChange = (text) => {
    let cleaned = text.replace(/\D/g, '');
    if (cleaned.length > 14) {
      cleaned = cleaned.substring(0, 14);
    }
    let formatted = cleaned;
    if (cleaned.length > 2 && cleaned.length <= 5) {
      formatted = cleaned.replace(/(\d{2})(\d+)/, '$1.$2');
    } else if (cleaned.length > 5 && cleaned.length <= 8) {
      formatted = cleaned.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
    } else if (cleaned.length > 8 && cleaned.length <= 12) {
      formatted = cleaned.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
    } else if (cleaned.length > 12) {
      formatted = cleaned.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d+)/, '$1.$2.$3/$4-$5');
    }
    setCnpj(formatted);
  };

  const handleCellphoneChange = (text) => {
    // Remove qualquer caractere que não seja número
    let cleaned = text.replace(/\D/g, '');

    // Limita o número de caracteres a 10 (2 dígitos de DDD + 8 dígitos do número)
    if (cleaned.length > 10) {
      cleaned = cleaned.substring(0, 10);
    }

    // Formata o número de celular
    let formatted = cleaned;
    if (cleaned.length > 2 && cleaned.length <= 6) {
      formatted = cleaned.replace(/(\d{2})(\d{4})/, '($1) $2');
    } else if (cleaned.length > 6) {
      formatted = cleaned.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
    }

    setCellphone(formatted);
  };

  const handleCellphone2Change = (text) => {
    // Remove qualquer caractere que não seja número
    let cleaned = text.replace(/\D/g, '');

    // Limita o número de caracteres a 10 (2 dígitos de DDD + 8 dígitos do número)
    if (cleaned.length > 10) {
      cleaned = cleaned.substring(0, 10);
    }

    // Formata o número de celular
    let formatted = cleaned;
    if (cleaned.length > 2 && cleaned.length <= 6) {
      formatted = cleaned.replace(/(\d{2})(\d{4})/, '($1) $2');
    } else if (cleaned.length > 6) {
      formatted = cleaned.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
    }

    setCellphone2(formatted);
  };


  const onPressCadastroCliente = async () => {
    
    if(!name || !email || !cellphone || !cellphone2 || !cpf || !rg || !nascimento){
      Alert.alert(`Preencha todos os campos!`);
      return
    }

    navigation.navigate(StackNav.ClientMap, {
      clientes : {parcelas: [], name, email, cellphone, cellphone2, cpf, rg, cnpj: cnpj || null, nascimento, sexo, pix}
    });
  };

  const nameValidation = itm => {
    setName(itm);
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

  const onFocus4 = () => {
    onFocusInput(setFocus4);
  };

  const onBlur4 = () => {
    onBlurInput(setFocus4);
  };

  const onFocus5 = () => {
    onFocusInput(setFocus5);
  };

  const onBlur5 = () => {
    onBlurInput(setFocus5);
  };

  const onFocus6 = () => {
    onFocusInput(setFocus6);
  };

  const onBlur6 = () => {
    onBlurInput(setFocus6);
  };

  const onFocusInput = onHighlight => {
    onHighlight(FocusStyle);
  };
  const onBlurInput = onHighlight => {
    onHighlight(BlurStyle);
  };

  const backToSignIn = () => {
    navigation.navigate(StackNav.Clientes);
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
              Cadastrar Cliente
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
                value={pix}
                onChangeText={(text) => setPix(text)}
                mainTxtInp={[localStyles.border, focus]}
                onFocus={onFocus}
                onBlur={onBlur}
                text={'Chave PIX'}
              />

              <CTextInput
                value={cellphone}
                onChangeText={handleCellphoneChange}
                mainTxtInp={[localStyles.border, focus3]}
                onFocus={onFocus3}
                onBlur={onBlur3}
                text={'Telefone Principal'}
              />

              <CTextInput
                value={cellphone2}
                onChangeText={handleCellphone2Change}
                mainTxtInp={[localStyles.border, focus4]}
                onFocus={onFocus4}
                onBlur={onBlur4}
                text={'Telefone Secundario'}
              />    

              <CTextInput
                text="Data de Nascimento"
                maskType="datetime"
                textInputStyle={{}}
                mainTxtInp={[localStyles.border, focus4]}
                isSecure={false}
                RightIcon={null}
                LeftIcon={null}
                onChangeText={(text) => setNascimento(text)}
                value={nascimento}
                keyboardType="default"
                align="left"
              />

              

              

              <CTextInput
                value={cpf}
                onChangeText={handleCpfChange}
                mainTxtInp={[localStyles.border, focus5]}
                onFocus={onFocus5}
                onBlur={onBlur5}
                text={'CPF'}
              /> 

              <CTextInput
                value={rg}
                onChangeText={handleRgChange}
                mainTxtInp={[localStyles.border, focus6]}
                onFocus={onFocus6}
                onBlur={onBlur6}
                text={'RG'}
              />

              <CTextInput
                value={cnpj}
                onChangeText={handleCnpjChange}
                mainTxtInp={[localStyles.border, focus6]}
                onFocus={onFocus6}
                onBlur={onBlur6}
                text={'CNPJ (opcional)'}
              />

              <View style={localStyles.container}>
                <Text style={localStyles.title}>Selecione o Sexo</Text>
                <CDropdownInput
                  data={data}
                  placeholder="Sexo"
                  value={sexo}
                  onChange={handleChange}
                />
              </View>

             

            </View>
            

            <CButton text={'Cadastrar'} onPress={onPressCadastroCliente} />

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
    color: colors.CadastroClienteTxt,
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
  container: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: moderateScale(20),
    backgroundColor: colors.GreyScale,
    padding: moderateScale(10)
  },
  title: {
    fontSize: moderateScale(14),
    marginBottom: moderateScale(10),
    color: colors.black,
  },
  selectedText: {
    fontSize: moderateScale(16),
    marginTop: moderateScale(10),
    color: colors.numbersColor,
  }
});
