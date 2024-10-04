import {
  Modal,
  StyleSheet,
  View,
  TouchableOpacity,
  ImageBackground,
  Platform,
} from 'react-native';
import React from 'react';
import {useNavigation} from '@react-navigation/native';

// Local imports
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CButton from '../common/CButton';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';
import CText from '../common/CText';
import strings from '../../i18n/strings';

import api from '../../services/api';

export default function TransferPopUp(props) {
  let {visible, onPressClose, amount, valores, feriados} = props;

  const navigation = useNavigation();

  const moveToHome = () => {
    navigation.navigate(StackNav.TransferProof, {valores: valores, feriados: feriados});
    gerarParcelas();
  };

  

  const gerarParcelas = async () => {

    let newParcelas = [];
    // Defina a data inicial
    const dataLanc = new Date();

    const dataInicial = new Date();

    // Array para armazenar as parcelas
    const parcelas = [];

    // Loop para gerar 25 parcelas
    for (let i = 0; i < valores?.parcela; i++) {
      parcela = {};
      parcela.parcela = 1 + i;
      parcela.parcela = parcela.parcela.toString().padStart(3, '0')
      parcela.valor = parseFloat(valores?.mensalidade.replace(/[^\d,-]/g, '').replace(',', '.'));
      parcela.saldo = parseFloat(valores?.mensalidade.replace(/[^\d,-]/g, '').replace(',', '.'));
      parcela.dt_lancamento = formatarDataParaString(new Date(dataLanc));


      dataInicial.setDate(dataInicial.getDate() + +valores?.intervalo);

      // Verifica se o dia da semana é sábado (6) ou domingo (0)
      // Se for, adiciona mais um dia até encontrar um dia útil (segunda a sexta)
      if(valores?.cobranca == '1'){
        while (dataInicial.getDay() === 0 || dataInicial.getDay() === 6) {
          dataInicial.setDate(dataInicial.getDate() + 1);
        }
      }else if(valores?.cobranca == '2'  ){
        while (dataInicial.getDay() === 0) {
          dataInicial.setDate(dataInicial.getDate() + 1);
        }
      }

      parcela.venc = formatarDataParaString(new Date(dataInicial));


      if(isFeriado(dataInicial)){
        dataInicial.setDate(dataInicial.getDate() + 1);
      }

      parcela.venc_real = formatarDataParaString(new Date(dataInicial));

      parcelas.push(formatarDataParaString(new Date(dataInicial)));

      newParcelas.push(parcela);

    }

    const client = {};

    client.dt_lancamento = valores?.dt_lancamento;
    client.valor = parseFloat(valores?.valor.replace(/[^\d,-]/g, '').replace(',', '.'));
		client.lucro = parseFloat(valores?.lucro.replace(/[^\d,-]/g, '').replace(',', '.'));
		client.juros = parseFloat(valores?.juros.replace(/[^\d,-]/g, '').replace(',', '.'));
    client.cliente = {id: valores?.cliente.id, nome_completo: valores?.cliente.nome_completo, cpf: valores?.cliente.cpf}
    client.banco = {id: valores?.banco.id, certificado: valores?.banco.certificado, clienteid: valores?.banco.clienteid, clientesecret: valores?.banco.clientesecret, chavepix: valores?.banco.chavepix, efibank: valores?.banco.efibank}
    client.costcenter = {id: valores?.costcenter.id}
    client.consultor = {id: valores?.consultor.id}
    client.parcelas = newParcelas;

    onPressClose();
    const res = await api.saveEmprestimo(client);





  }

  const save = async () =>  {
    this.changeLoading();
    this.errors = [];

    const client = {};

    
    client.cliente = valores?.cliente;
    client.banco = {id: valores?.banco}
    client.costcenter = {id: valores?.costcenter}
    client.consultor = valores?.consultor;
    client.parcelas = newParcelas;

    if(res?.data){
      setFeriados(res.data);
    }

    this.emprestimoService.save(this.client)
    .then((response) => {
      if (undefined != response.data.data) {
        this.client = response.data.data;
        
      }

      this.toast.add({
        severity: ToastSeverity.SUCCESS,
        detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
        life: 3000
      });

      setTimeout(() => {
        this.router.push({ name: 'emprestimosList'})
      }, 1200)

    })
    .catch((error) => {
      this.changeLoading();
      this.errors = error?.response?.data?.errors;

      if (error?.response?.status != 422) {
        this.toast.add({
          severity: ToastSeverity.ERROR,
          detail: UtilService.message(error.response.data),
          life: 3000
        });
      }

      this.changeLoading();
    })
    .finally(() => {
      this.changeLoading();
    });
  }


  const formatarDataParaString = (data) => {
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    return `${dia}/${mes}/${ano}`;
  }

  const isFeriado = (data) => {
    const dataFormatada = formatarDataParaString(data);
    
    return feriados.some(feriado => feriado.data_feriado === dataFormatada);
  }

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
      <Modal animationType={'fade'} transparent={true} visible={visible}>
        <TouchableOpacity
          style={localStyles.modalMainContainer}
          onPress={onPressClose}>
          <TouchableOpacity activeOpacity={1} onPress={onPressClose}>
            <ImageBackground
              source={images.TransferPopUp}
              style={localStyles.imgStyle}>
              <View style={localStyles.innerContainer}>
                <View>
                  <CText color={colors.black} type={'S18'} align="center">
                    Confirmar Empréstimo
                  </CText>

                  <Detail
                    isTotal={false}
                    header={'Valor do Empréstimo'}
                    bankName={`Qt. de Parcelas`}
                    name={valores?.valor}
                    cardNumber={valores?.parcela}
                  />

                  <Detail
                    isTotal={false}
                    header={'Juros'}
                    bankName={`Vl. das Parcelas`}
                    name={valores?.juros}
                    cardNumber={valores?.mensalidade}
                  />

                  <Detail
                    isTotal={false}
                    header={'Lucro'}
                    bankName={`Total a pagar`}
                    name={valores?.lucro}
                    cardNumber={valores?.valortotal}
                  />

                </View>
                <CButton
                  text={'Aprovar'}
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
    backgroundColor: colors.transparent,
  },
  imgStyle: {
    width: moderateScale(327),
    height: moderateScale(464),
    ...styles.justifyCenter,
    ...styles.ph20,
  },
  innerContainer: {
    ...styles.justifyBetween,
    marginTop: moderateScale(120),
    ...styles.flex,
    ...styles.mv15,
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
});
