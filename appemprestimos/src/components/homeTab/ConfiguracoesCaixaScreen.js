import {
  StyleSheet,
  SafeAreaView,
  Image,
  View,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import React, {useState, useRef} from 'react';

import {useFocusEffect} from '@react-navigation/native';
import Feathers from 'react-native-vector-icons/FontAwesome';
import {Dropdown} from 'react-native-element-dropdown';

import TopUpAlterarCaixa from '../modals/TopUpAlterarCaixa';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes/index';
import images from '../../assets/images/index';
import {getHeight, moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import {CurrencyList, DollarsData} from '../../api/constants';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import CDropdownInput from '../common/CDropdownInput';
import strings from '../../i18n/strings';
import padding from '../../themes/padding';

import api from '../../services/api';

export default function ConfiguracoesCaixaScreen({navigation}) {
  const [amount, setAmount] = useState('');
  const [Data, setData] = useState('');
  const [currency, setCurrency] = useState();

  const [bancos, setBancos] = useState([]);

  const [valores, setValores] = useState({});

  const onPress = () => {
    successRef.current.show();
  };

  let successRef = useRef(null);
  const moveToConfirm = async () => {
    successRef.current.show();
    // navigation.navigate(StackNav.Confirmation, {amount: amount});
    // try {
    //   let response = await api.fechamentoCaixa(valores.banco.id);
    //   alert('Fechamento de Caixa realizada com sucesso!');
    //   navigation.navigate(StackNav.TabNavigation);
    // } catch (e) {
    //   console.log(e);
    // }
  };

  const onChangeColor = item => {
    setData(item);
  };

  const onChangeBancos = ({value}) => {
    setValores({
      ...valores, // Mantém outras propriedades de 'valores' intactas
      banco: bancos.find(item => item.id === value), // Atualiza apenas 'valor'
    });

    console.log(valores.banco);
  };

  const onChangeAmount = txt => {
    setAmount(parseFloat(txt));
  };

  const onChangeCurrency = value => {
    setCurrency(value);
  };

  useFocusEffect(
    React.useCallback(() => {
      getBancos();

      return () => {};
    }, []),
  );

  const getBancos = async () => {
    try {
      let response = await api.searchbanco();
      const bancosData = response.data.map(item => ({
        ...item,
        value: item.id,
      }));
      // Define o estado com os dados atualizados
      setBancos(bancosData);
    } catch (e) {
      console.log(e);
    }
  };

  const dollarsData = ({item}) => {
    return (
      <TouchableOpacity
        style={[
          localStyles.mainDollarsData,
          {
            backgroundColor: Data === item ? colors.Primary : colors.GreyScale,
          },
        ]}
        onPress={() => onChangeColor(item)}>
        <CText
          type={'M14'}
          style={[
            localStyles.itemTxt,
            {
              color: Data === item ? colors.white : colors.black,
            },
          ]}>
          {item}
        </CText>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper contentContainerStyle={localStyles.keyboardType}>
        <View>
          <CHeader color={colors.black} title={'Alterar Caixa'} />
          <TouchableOpacity style={localStyles.parentDebit}>
            <Image source={images.card3} style={localStyles.ImageSty} />
            <View style={localStyles.innerContainer}>
              <CText
                color={colors.tabColor}
                type={'B18'}
                style={localStyles.debitTxt}>
                Banco
              </CText>
            </View>
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
          </TouchableOpacity>
          {valores?.banco && (
            <View style={localStyles.mainBorder}>
              {valores.banco.wallet && (
                <View style={localStyles.parentAmt}>
                  <CText type={'M14'} color={colors.tabColor}>
                    Saldo Banco wallet
                  </CText>
                  <CText type={'M14'} color={colors.tabColor}>
                    {valores.banco.saldo_banco.toLocaleString('pt-BR', {
                      style: 'currency',
                      currency: 'BRL',
                    })}
                  </CText>
                </View>
              )}

              <View style={localStyles.parentAmt}>
                <CText type={'M14'} color={colors.tabColor}>
                  Saldo Banco Sistema
                </CText>
                <CText type={'M14'} color={colors.tabColor}>
                  {valores.banco.saldo.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </CText>
              </View>

              <View style={localStyles.parentAmt}>
                <CText type={'M14'} color={colors.tabColor}>
                  Saldo Banco Sistema + Saldo Caixa Pix
                </CText>
                <CText type={'M14'} color={colors.tabColor}>
                  {(
                    valores.banco.saldo + valores.banco.caixa_pix
                  ).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </CText>
              </View>
              {valores.banco.wallet && (
                <View style={localStyles.parentAmt}>
                  <CText type={'M14'} color={colors.tabColor}>
                    Diferença Entre Bancos
                  </CText>
                  <CText type={'M14'} color={colors.tabColor}>
                    {(
                      valores.banco.saldo - valores.banco.saldo_banco
                    ).toLocaleString('pt-BR', {
                      style: 'currency',
                      currency: 'BRL',
                    })}
                  </CText>
                </View>
              )}

              <View style={localStyles.parentAmt}>
                <CText type={'M14'} color={colors.tabColor}>
                  Saldo Caixa
                </CText>
                <CText type={'M14'} color={colors.tabColor}>
                  {valores.banco.caixa_empresa.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </CText>
              </View>

              <View style={localStyles.parentAmt}>
                <CText type={'M14'} color={colors.tabColor}>
                  Saldo Caixa Pix
                </CText>
                <CText type={'M14'} color={colors.tabColor}>
                  {valores.banco.caixa_pix.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </CText>
              </View>
            </View>
          )}
        </View>
      </KeyBoardAvoidWrapper>

      {valores?.banco && (
        <CButton
          disabled={!valores?.banco}
          containerStyle={localStyles.CButton}
          onPress={moveToConfirm}
        />
      )}
      <TopUpAlterarCaixa sheetRef={successRef} dados={valores.banco} />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.mainContainerSurface,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  ImageSty: {
    width: moderateScale(42),
    height: moderateScale(24),
  },
  parentDebit: {
    ...styles.mv30,
    backgroundColor: colors.GreyScale,
    ...styles.flexRow,
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
    ...styles.alignCenter,
    ...styles.p15,
  },
  debitTxt: {
    ...styles.pl15,
  },
  innerContainer: {
    ...styles.flex,
    ...styles.rowSpaceBetween,
  },
  angleButton: {
    ...styles.pl10,
  },
  parentAmt: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    paddingVertical: moderateScale(4),
  },
  mainBorder: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
    ...styles.ph20,
    paddingVertical: moderateScale(10),
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
  Dollars1txt: {
    ...styles.ph16,
    ...styles.p8,
  },
  mainDollarsData: {
    ...styles.mv25,
    ...styles.ph15,
    ...styles.center,
    ...styles.mr22,
    backgroundColor: colors.red,
    height: moderateScale(40),
    borderRadius: moderateScale(12),
  },
  keyboardType: {
    ...styles.ph20,
  },
  CButton: {
    width: '90%',
    ...styles.mv25,
  },
  miniContainer: {
    color: colors.black,
  },
  dropdownStyle: {
    backgroundColor: colors.GreyScale,
    height: getHeight(50),
    borderRadius: moderateScale(15),
    borderWidth: moderateScale(1),
    ...styles.ph10,
    width: '60%',
    ...styles.mv10,
  },
});
