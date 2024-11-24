import {StyleSheet, SafeAreaView, Image, View, Alert} from 'react-native';
import React, {useState} from 'react';

import { useFocusEffect } from '@react-navigation/native';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {getHeight, moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import CButton from '../common/CButton';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import TransferPopUp from '../modals/TransferPopUp';
import {Dropdown} from 'react-native-element-dropdown';
import {CurrencyList} from '../../api/constants';
import api from '../../services/api';
import {getAuthCompany, getUser} from '../../utils/asyncStorage';


export default function SendMoney({navigation, route}) {
  const { cliente } = route.params;

  useFocusEffect(
    React.useCallback( () => {

      async function fetchData() {
        setValores({
          ...valores, 
          dt_lancamento: formatDate(new Date()),
          cliente: cliente,
          consultor: await getUser() 
        });
      }
  
      fetchData();
      getFeriados();
      getBancos()
      getCostCenter()

      return () => {
      };

    }, [])
  );

  const [visible, setVisible] = useState(false);
  const [amount, setAmount] = useState('');
  const [valores, setValores] = useState({});

  const [feriados, setFeriados] = useState([]);

  const [bancos, setBancos] = useState([]);
  const [costCenter, setCostCenter] = useState([]);

  const [currency, setCurrency] = useState('');

  const getFeriados =  async () => {
    const res = await api.getFeriados();
    if(res?.data){
      setFeriados(res.data);
    }
}

  const onPressClose = () => {

    if(!valores?.valor){
      Alert.alert('Preencha o campo Valor ');
      return false;
    }

    if(!valores?.parcela){
      Alert.alert('Preencha o campo Parcelas ');
      return false;
    }

    if(!valores?.mensalidade){
      Alert.alert('Preencha o campo Mensalidade ');
      return false;
    }

    if(!valores?.intervalo){
      Alert.alert('Preencha o campo Intervalo entre as parcelas ');
      return false;
    }

    if(!valores?.cobranca){
      Alert.alert('Selecione uma opção de cobrança');
      return false;
    }

    if(!valores?.banco){
      Alert.alert('Selecione uma opção de banco');
      return false;
    }

    if(!valores?.costcenter){
      Alert.alert('Selecione uma opção de centro de custo');
      return false;
    }

    setVisible(!visible);
  };

  const formatDate = (date) => {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Meses começam do zero
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
  };

  const getBancos = async () =>  {
    try {
      let response = await api.searchbanco();
      const bancosData = response.data.map(item => ({
          ...item,
          value: item.id
      }));
      // Define o estado com os dados atualizados
      setBancos(bancosData);
    } catch (e) {
      console.log(e);
    }
  }

  const getCostCenter = async () =>  {
    try {
      let response = await api.searchCostcenter();
      const costCenterData = response.data.map(item => ({
          ...item,
          value: item.id
      }));
      // Define o estado com os dados atualizados
      setCostCenter(costCenterData);

    } catch (e) {
      console.log(e);
    }
  }

  const onChangeAmount = txt => {
    setAmount(parseFloat(txt));
  };

  const onChangeCurrency = ({value}) => {
    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      cobranca: value // Atualiza apenas 'valor'
    });

  };

  const onChangeBancos = ({value}) => {

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      banco: bancos.find(item => item.id === value) // Atualiza apenas 'valor'
    });

  };

  const onChangeCostCenter = ({value}) => {

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      costcenter: costCenter.find(item => item.id === value) // Atualiza apenas 'valor'
    });

  };

  const handleValueChange = (text) => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto

    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      valor: currency // Atualiza apenas 'valor'
    });

  };

  const handleMensalidadeChange = (text) => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto

    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      mensalidade: currency // Atualiza apenas 'valor'
    });

  };

  const handleLucroChange = (text) => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto

    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      lucro: currency // Atualiza apenas 'valor'
    });

  };
  

  const handleParcelaChange = (text) => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      parcela: cleaned // Atualiza apenas 'valor'
    });
  };

  const handleIntervaloChange = (text) => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número

    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      intervalo: cleaned // Atualiza apenas 'valor'
    });
  };

  const convertFloat = (value) => {
    const formattedValue = value.replace("R$", "").trim().replace(/\./g, "").replace(",", ".");
    return formattedValue;
  }


  const handleParcela = () => {

    if(valores?.valor && valores?.mensalidade){

      let val = (valores?.parcela * convertFloat(valores?.mensalidade)) - convertFloat(valores?.valor);

      let cleaned = val;
      let number = parseFloat(cleaned); // Divide por 100 para obter o decimal correto

      let currency = number.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      }); // Formata o número para o formato monetário brasileiro

      const porcentagem = (((valores?.parcela * convertFloat(valores?.mensalidade)) - convertFloat(valores?.valor)) / convertFloat(valores?.valor)) * 100;

      setValores({
        ...valores, // Mantém outras propriedades de 'valores' intactas
        porcentagem: 1,
        lucro: currency, // Atualiza apenas 'valor'
        juros: `%${porcentagem.toFixed(2)}`,
        valortotal: (valores?.parcela * convertFloat(valores?.mensalidade)).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL',
        })
      });
    }

  }

  const handleLucro = () => {
		
    if(valores?.valor && valores?.mensalidade && valores?.parcela){

      const porcentagem = (((parseFloat(convertFloat(valores?.lucro)) + parseFloat(convertFloat(valores?.valor))) - convertFloat(valores?.valor)) / convertFloat(valores?.valor)) * 100;

      setValores({
        ...valores, // Mantém outras propriedades de 'valores' intactas
        juros: `% ${porcentagem.toFixed(2)}`,
        mensalidade: ((parseFloat(convertFloat(valores?.lucro)) + parseFloat(convertFloat(valores?.valor))) / valores?.parcela).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL',
        }),
        valortotal: (parseFloat(convertFloat(valores?.lucro)) + parseFloat(convertFloat(valores?.valor))).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL',
        })
      });

    }

  }

  const HandleValorMensalidade = () => {
		
    if(valores?.valor && valores?.parcela && valores?.mensalidade){

      const porcentagem = (((valores?.parcela * convertFloat(valores?.mensalidade)) - convertFloat(valores?.valor)) / convertFloat(valores?.valor)) * 100;

      setValores({
        ...valores, // Mantém outras propriedades de 'valores' intactas
        lucro: ((valores?.parcela * convertFloat(valores?.mensalidade)) - convertFloat(valores?.valor)).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL',
        }), // Atualiza apenas 'valor'
        juros: `% ${porcentagem.toFixed(2)}`,
        valortotal: (valores?.parcela * convertFloat(valores?.mensalidade)).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL',
        })
      });

    }

  }

  const formatDateString = (text) => {
    // Remove todos os caracteres não numéricos
    const cleaned = ('' + text).replace(/\D/g, '');

    // Formatação no formato dd/mm/yyyy
    const match = cleaned.match(/(\d{0,2})(\d{0,2})(\d{0,4})/);
    if (match) {
      const part1 = match[1];
      const part2 = match[2] ? '/' + match[2] : '';
      const part3 = match[3] ? '/' + match[3] : '';
      return part1 + part2 + part3;
    }
    return text;
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper containerStyle={localStyles.keyBoardSty}>
        <View>
          <CHeader color={colors.black} title={'Empréstimo'} />
          <View style={localStyles.mainImg}>
            <Image source={images.AGE} style={localStyles.girlImg} />
          </View>
          <CText
            color={colors.black}
            align={'center'}
            type={'M14'}
            style={localStyles.mariaTxt}>
            {cliente?.nome_completo_cpf}
          </CText>

          <View style={localStyles.mainBorder}>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
                Data Lançamento:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.dt_lancamento}
                onChangeText={(text) => {setValores({
                  ...valores, // Mantém outras propriedades de 'valores' intactas
                  dt_lancamento: formatDateString(text) // Atualiza apenas 'valor'
                })}}
              />
            </View>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
                Digite o valor:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.valor}
                onChangeText={handleValueChange}
              />
            </View>

            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
                  Parcelas:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.parcela}
                onBlur={handleParcela}
                onChangeText={handleParcelaChange}
              />
            </View>

            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Valor da Mensalidade:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.mensalidade}
                onBlur={HandleValorMensalidade}
                onChangeText={handleMensalidadeChange}
              />
            </View>

            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Juros:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.juros}
              />
            </View>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Lucro:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.lucro}
                onBlur={handleLucro}
                onChangeText={handleLucroChange}
              />
            </View>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Valor Total:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.valortotal}
              />
            </View>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Intervalo entre as parcelas:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={valores?.intervalo}
                onChangeText={handleIntervaloChange}
              />
            </View>
            
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Opção de cobrança:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <Dropdown
                style={localStyles.dropdownStyle}
                data={CurrencyList}
                value={valores?.cobranca}
                maxHeight={moderateScale(200)}
                labelField="label"
                valueField="value"
                placeholder="Selecione uma opção"
                onChange={onChangeCurrency}
                selectedTextStyle={localStyles.miniContainer}
                itemTextStyle={localStyles.miniContainer}
                itemContainerStyle={{
                  backgroundColor: colors.GreyScale,
                  width: 'auto',
                }}
              />
            </View>

            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Banco:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <Dropdown
                style={localStyles.dropdownStyle}
                data={bancos}
                value={valores?.banco}
                maxHeight={moderateScale(200)}
                labelField="name_agencia_conta"
                valueField="value"
                placeholder="Selecione uma opção"
                onChange={onChangeBancos}
                selectedTextStyle={localStyles.miniContainer}
                itemTextStyle={localStyles.miniContainer}
                itemContainerStyle={{
                  backgroundColor: colors.GreyScale,
                  width: 'auto',
                }}
              />
            </View>

            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
              Centro de Custo:
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <Dropdown
                style={localStyles.dropdownStyle}
                data={costCenter}
                value={valores?.costcenter}
                maxHeight={moderateScale(200)}
                labelField="description"
                valueField="value"
                placeholder="Selecione uma opção"
                onChange={onChangeCostCenter}
                selectedTextStyle={localStyles.miniContainer}
                itemTextStyle={localStyles.miniContainer}
                itemContainerStyle={{
                  backgroundColor: colors.GreyScale,
                  width: 'auto',
                }}
              />
            </View>
            
          </View>
          
        </View>
      </KeyBoardAvoidWrapper>
      <CButton
        containerStyle={localStyles.mainCButton}
        text={'Seguir'}
        onPress={onPressClose}
        disabled={false}
      />
      <TransferPopUp
        visible={visible}
        onPressClose={onPressClose}
        amount={amount}
        valores={valores}
        feriados={feriados}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.flex,
    backgroundColor: colors.white,
    ...styles.justifyBetween,
  },
  mainImg: {
    borderWidth: moderateScale(1.5),
    borderRadius: moderateScale(60),
    borderColor: colors.numbersColor,
    ...styles.selfCenter,
    ...styles.p10,
  },
  girlImg: {
    width: moderateScale(88),
    height: moderateScale(88),
  },
  parentImg: {
    ...styles.center,
  },
  mariaTxt: {
    ...styles.mv30,
  },
  parentAmt: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.mt10,
  },
  mainBorder: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
    ...styles.ph20,
  },
  parentUsd: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(8),
    width: moderateScale(67),
    backgroundColor: colors.GreyScale,
    ...styles.rowCenter,
    ...styles.mv15,
  },
  UsdTxt: {
    ...styles.p5,
  },
  CTxtInp: {
    width: moderateScale(210),
    borderRadius: moderateScale(15),
    height: moderateScale(35),
    ...styles.mv15,
    backgroundColor: colors.GreyScale,
  },
  parentTxtInp: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  ChildTxtInp: {
    ...typography.fontSizes.f24,
    ...typography.fontWeights.SemiBold,
  },
  mainCButton: {
    ...styles.mv30,
    width: '90%',
  },
  keyBoardSty: {
    ...styles.ph20,
    ...styles.flexGrow1,
    ...styles.mainContainerSurface,
  },
  dropdownStyle: {
    backgroundColor: colors.GreyScale,
    height: getHeight(52),
    borderRadius: moderateScale(15),
    borderWidth: moderateScale(1),
    ...styles.ph20,
    width: '100%',
    ...styles.mv10,
  },
  miniContainer: {
    color: colors.black,
  },
});
