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

import FullScreenLoader from '../FullScreenLoader';

import CTextInput from '../common/CTextInput';

import api from '../../services/api';

export default function TelaAprovacaoTitulo(props) {
  let {visible, onPressClose, cliente, feriados, tela} = props;

  const navigation = useNavigation();

  const [valores, setValores] = useState(cliente.saldo);

  const [res, setRes] = useState(null);

  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      if (visible) {
        setRes(null);
        setLoading(true);
        if (cliente?.emprestimo) {
          let req = await api.transferenciaConsultar(cliente.emprestimo.id);
          if (req?.error) {
            alert(req.error);
            onPressClose();
            setLoading(false);

            return;
          }
          setRes(req);
          setLoading(false);
        } else {
          let req = await api.transferenciaTituloConsultar(cliente.id);
          if (req?.error) {
            alert(req.error);
            onPressClose();
            setLoading(false);

            return;
          }
          setRes(req);
          setLoading(false);
        }
      }
    };

    fetchData();
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
    setLoading(true);
    if(cliente.emprestimo) {
      let req = await api.transferenciaEfetivar(cliente.emprestimo.id);
      if (req?.error) {
        alert(req.error);
        onPressClose();
        setLoading(false);
        return;
      }
  
      Alert.alert('Pagamento realizado com sucesso!');
  
      onPressClose();
      setLoading(false);
    }else {
      let req = await api.transferenciaTituloEfetivar(cliente.id);
      if (req?.error) {
        alert(req.error);
        onPressClose();
        setLoading(false);
        return;
      }
  
      Alert.alert('Pagamento realizado com sucesso!');
  
      onPressClose();
      setLoading(false);
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

  return (
    <View style={localStyles.flex}>
      <FullScreenLoader visible={loading} />

      <Modal animationType={'fade'} transparent={false} visible={visible}>
        <TouchableOpacity
          style={localStyles.modalMainContainer}
          onPress={onPressClose}>
          <TouchableOpacity activeOpacity={1}>
            <ImageBackground source={images.Saldo} style={localStyles.imgStyle}>
              <View style={localStyles.innerContainer}>
                <View>
                  <View style={localStyles.parentTxtInp}>
                    <CText type={'M20'} color={colors.black}>
                      {cliente?.descricao}
                    </CText>
                  </View>
                </View>
                {cliente.fornecedor && (
                  <View>
                    <View style={localStyles.parentTxtInp}>
                      <CText type={'M20'} color={colors.black}>
                        Nome Fornecedor: {cliente?.fornecedor.nome_completo}
                      </CText>
                    </View>
                  </View>
                )}

                <View>
                  {res && (
                    <View style={localStyles.parentTxtInp}>
                      <CText type={'M20'} color={colors.black}>
                        Tem certeza que deseja realizar o paramento de{' '}
                        {cliente?.valor?.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}{' '}
                        para {res?.creditParty?.name}?
                      </CText>
                    </View>
                  )}
                </View>
                <CButton
                  text={'Realizar Pagamento'}
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
    // bottom: moderateScale(40),
  },
  ParentLgnBtnCancelarBaixa: {
    backgroundColor: '#00f',
    // bottom: moderateScale(40),
  },
  ParentLgnBtn: {
    backgroundColor: '#17a2b8',
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
