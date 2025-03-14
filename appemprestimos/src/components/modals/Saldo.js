import {
  Modal,
  StyleSheet,
  View,
  TouchableOpacity,
  ImageBackground,
  Platform,
  Alert,
} from 'react-native';
import React, {useEffect, useState} from 'react';
import {useNavigation} from '@react-navigation/native';

// Local imports
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import typography from '../../themes/typography';
import {getHeight, moderateScale} from '../../common/constant';

import CTextInput from '../common/CTextInput';

import api from '../../services/api';

export default function Saldo(props) {
  let {visible, onPressClose, cliente, pendenteHoje, feriados, tela} = props;

  const navigation = useNavigation();

  const [valores, setValores] = useState(cliente.saldo);

  useEffect(() => {
    console.log(tela);
    if (typeof cliente.saldo !== 'string') {
      handleSaldoNovo(pendenteHoje.saldoatrasado > 0 ? pendenteHoje.saldoatrasado : cliente.saldo);
    }

    function handleSaldoNovo() {
      let number = parseFloat(pendenteHoje.saldoatrasado > 0 ? pendenteHoje.saldoatrasado : cliente.saldo); // Divide por 100 para obter o decimal correto
      console.log('cliente', cliente)

      let currency = number.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
      }); // Formata o número para o formato monetário brasileiro

      setValores(currency);
    }
  }, [visible]);

  function converterParaNumero(valor) {
    return parseFloat(
      valor.replace('R$', '').replace(/\./g, '').replace(',', '.'),
    );
  }

  const obterDataAtual = () => {
    const data = new Date();
    const ano = data.getFullYear();
    let mes = data.getMonth() + 1; // Os meses vão de 0 a 11 em JavaScript, então adicionamos 1
    let dia = data.getDate();

    // Adicionar um zero à esquerda se o mês ou o dia for menor que 10
    mes = mes < 10 ? '0' + mes : mes;
    dia = dia < 10 ? '0' + dia : dia;

    return `${ano}-${mes}-${dia}`;
  };

  const moveToHome = async () => {
    if (typeof cliente.saldo !== 'string') {
      // if(converterParaNumero(valores) > cliente.saldo){
      //   Alert.alert(`Valor da Baixa de ${valores} não pode ser maior que o saldo de ${cliente.saldo}`);
      //   return
      // }

      if (tela == 'baixa_pendentes_hoje') {
        let req = await api.baixaManual(
          cliente.id,
          obterDataAtual(),
          converterParaNumero(valores),
        );
      } else {
        let req = await api.baixaManualCobrador(
          cliente.id,
          obterDataAtual(),
          converterParaNumero(valores),
        );
      }

      Alert.alert('Baixa realizada com sucesso!');

      onPressClose();
    } else {
      if (converterParaNumero(valores) > converterParaNumero(cliente.saldo)) {
        Alert.alert(
          `Valor da Baixa de ${valores} não pode ser maior que o saldo de ${cliente.saldo}`,
        );
        return;
      }

      let req = await api.baixaManual(
        cliente.id,
        obterDataAtual(),
        converterParaNumero(valores),
      );

      Alert.alert('Baixa realizada com sucesso!');

      navigation.navigate(StackNav.TabNavigation);
    }
  };

  const cancelarBaixaManual = async () => {
    let req = await api.cancelarBaixaManual(cliente.id);

    Alert.alert('Baixa cancelada com sucesso!');

    onPressClose();
  };

  const save = async () => {
    this.changeLoading();
    this.errors = [];

    const client = {};

    client.cliente = valores?.cliente;
    client.banco = {id: valores?.banco};
    client.costcenter = {id: valores?.costcenter};
    client.consultor = valores?.consultor;
    client.parcelas = newParcelas;

    if (res?.data) {
      setFeriados(res.data);
    }

    this.emprestimoService
      .save(this.client)
      .then(response => {
        if (undefined != response.data.data) {
          this.client = response.data.data;
        }

        this.toast.add({
          severity: ToastSeverity.SUCCESS,
          detail: this.client?.id
            ? 'Dados alterados com sucesso!'
            : 'Dados inseridos com sucesso!',
          life: 3000,
        });

        setTimeout(() => {
          this.router.push({name: 'emprestimosList'});
        }, 1200);
      })
      .catch(error => {
        this.changeLoading();
        this.errors = error?.response?.data?.errors;

        if (error?.response?.status != 422) {
          this.toast.add({
            severity: ToastSeverity.ERROR,
            detail: UtilService.message(error.response.data),
            life: 3000,
          });
        }

        this.changeLoading();
      })
      .finally(() => {
        this.changeLoading();
      });
  };

  const formatarDataParaString = data => {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    return `${dia}/${mes}/${ano}`;
  };

  const isFeriado = data => {
    const dataFormatada = formatarDataParaString(data);

    return feriados.some(feriado => feriado.data_feriado === dataFormatada);
  };

  const handleSaldo = text => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto

    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro

    setValores(currency);
  };

  const Detail = ({
    header,
    bankName,
    name,
    cardNumber,
    total,
    prize,
    isTotal = false,
  }) => {
    return (
      <View style={localStyles.parentFromBOA}>
        {!isTotal ? (
          <View>
            <View style={localStyles.detailHeaderStyle}>
              <CText color={colors.tabColor} type={'R12'}>
                {header}
              </CText>
              <CText color={colors.tabColor} type={'R12'}>
                {bankName}
              </CText>
            </View>
            <View style={localStyles.detailStyle}>
              <CText color={colors.black} type={'B16'}>
                {name}
              </CText>
              <CText color={colors.black} type={'B16'}>
                {cardNumber}
              </CText>
            </View>
          </View>
        ) : (
          <View style={localStyles.totalPrize}>
            <CText color={colors.tabColor} type={'M16'}>
              {total}
            </CText>
            <CText color={colors.black} type={'B16'}>
              {prize}
            </CText>
          </View>
        )}
      </View>
    );
  };

  return (
    <View style={localStyles.flex}>
      <Modal animationType={'fade'} transparent={false} visible={visible}>
        <TouchableOpacity
          style={localStyles.modalMainContainer}
          onPress={onPressClose}>
          <TouchableOpacity activeOpacity={1}>
            <ImageBackground source={images.Saldo} style={localStyles.imgStyle}>
              <View style={localStyles.innerContainer}>
                <View>
                  <View style={localStyles.parentAmt}>
                    <CText type={'M20'} color={colors.tabColor}>
                      Valor da Baixa:
                    </CText>
                  </View>

                  <View style={localStyles.parentTxtInp}>
                    <CTextInput
                      mainTxtInp={localStyles.CTxtInp}
                      textInputStyle={localStyles.ChildTxtInp}
                      keyboardType={'numeric'}
                      value={valores}
                      onChangeText={handleSaldo}
                    />
                  </View>
                </View>
                <CButton
                  text={'Realizar Baixa'}
                  containerStyle={[
                    localStyles.ParentLgnBtn,
                    {
                      bottom:
                        Platform.OS === 'ios'
                          ? moderateScale(0)
                          : moderateScale(40),
                    },
                  ]}
                  onPress={moveToHome}
                />
                {cliente.valor_recebido && (
                  <CButton
                    text={`Cancelar Baixa Manual\nValor ${cliente.valor_recebido.toLocaleString('pt-BR', {
                      style: 'currency',
                      currency: 'BRL',
                    })}`}
                    containerStyle={[
                      localStyles.ParentLgnBtnCancelarBaixa,
                      {
                        bottom:
                          Platform.OS === 'ios'
                            ? moderateScale(0)
                            : moderateScale(40),
                      },
                    ]}
                    onPress={cancelarBaixaManual}
                  />
                )}
                <CButton
                  text={'Cancelar'}
                  containerStyle={[
                    localStyles.ParentLgnBtnCancelar,
                    {
                      bottom:
                        Platform.OS === 'ios'
                          ? moderateScale(0)
                          : moderateScale(40),
                    },
                  ]}
                  onPress={onPressClose}
                />
              </View>
            </ImageBackground>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

const localStyles = StyleSheet.create({
  modalMainContainer: {
    ...styles.flex,
    ...styles.center,
    backgroundColor: colors.white,
  },
  imgStyle: {
    width: moderateScale(327),
    height: moderateScale(464),
    ...styles.justifyCenter,
    ...styles.ph20,
  },
  innerContainer: {
    marginTop: moderateScale(120),
    ...styles.flex,
    ...styles.mv15,
  },
  ParentLgnBtnCancelar: {
    backgroundColor: '#f00',
    // bottom: moderateScale(40),
  },
  ParentLgnBtnCancelarBaixa: {
    backgroundColor: '#00f',
    // bottom: moderateScale(40),
  },
  ParentLgnBtn: {
    // bottom: moderateScale(40),
  },
  parentFromBOA: {
    ...styles.mt15,
    ...styles.pb15,
    borderBottomColor: colors.bottomBorder,
    borderBottomWidth: moderateScale(1),
  },
  detailHeaderStyle: {
    ...styles.rowSpaceBetween,
    ...styles.pv5,
  },
  detailStyle: {
    ...styles.rowSpaceBetween,
  },
  totalPrize: {
    ...styles.rowSpaceBetween,
    ...styles.mv5,
  },
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
    ...styles.justifyCenter,
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
    ...styles.justifyCenter,
    marginBottom: moderateScale(30),
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
