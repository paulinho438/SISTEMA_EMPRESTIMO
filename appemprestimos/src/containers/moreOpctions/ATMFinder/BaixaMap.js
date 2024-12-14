import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  Linking,
  Alert
} from 'react-native';
import React, {useRef, useState} from 'react';
import Material from 'react-native-vector-icons/MaterialIcons';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';
import Ionicons from 'react-native-vector-icons/Ionicons';
import AntDesign from 'react-native-vector-icons/AntDesign';

// Local imports
import {colors} from '../../../themes/colors';
import {styles} from '../../../themes/index';
import {moderateScale} from '../../../common/constant';
import CText from '../../../components/common/CText';
import images from '../../../assets/images/index';
import CTextInput from '../../../components/common/CTextInput';
import strings from '../../../i18n/strings';
import CButton from '../../../components/common/CButton';
import KeyBoardAvoidWrapper from '../../../components/common/KeyBoardAvoidWrapper';
import Location from '../../../components/modals/Location';
import ParcelasPendentesHoje from '../../../components/modals/ParcelasPendentesHoje';
import ParcelasExtorno from '../../../components/modals/ParcelasExtorno';
import { useFocusEffect } from '@react-navigation/native';
import api from '../../../services/api';

import {StackNav, TabNav} from '../../../navigation/navigationKeys';

export default function ATMDetails({navigation, route}) {
  const { clientes } = route.params;
  
  const [empty, nonEmpty] = useState('');
  const [parcelas, setParcelas] = useState([]);

  const [parcelasPendentes, setParcelasPendentes] = useState([]);
  const [parcelasExtorno, setParcelasParaExtorno] = useState([]);


  useFocusEffect(
    React.useCallback(() => {
      getInfo();
      getPendentesParaHoje();
    }, [])
  );

  const getInfo =  async (position) => {

    let reqClientes = await api.getParcelasInfoEmprestimo(clientes.id);
    setParcelas(reqClientes.data)

  }

  const Search = useRef(null);
  const Info = useRef(null);
  const Extorno = useRef(null);

  const moveToInfoModel = () => {
    Info.current.show();
  };

  const moveToExtornoModel = () => {
    if(parcelasExtorno.length == 0){
      return Alert.alert('Não existem parcelas para extorno');
    }
    Extorno.current.show();
  };

  const onPress = () => {
    nonEmpty('');
  };

  const moveToModel = () => {
    Search.current.show();
  };

  const backToMore = () => {
    navigation.navigate(TabNav.HomeScreen);
  };

  const getPendentesParaHoje = async () => {
    let req = await api.pendentesParaHoje();
    setParcelasPendentes(req.data);

    let req2 = await api.parcelasParaExtorno();
    setParcelasParaExtorno(req2);

  }

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(clientes.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const openWhatsApp = () => {

    console.log(montarStringParcelas(parcelas))
    let url = `whatsapp://send?phone=${clientes.telefone_celular_1}`;
    url += `&text=${encodeURIComponent(montarStringParcelas(parcelas))}`;

    Linking.openURL(url)
      .then((data) => {
        console.log('WhatsApp abierto:', data);
      })
      .catch(() => {
        console.log('Error al abrir WhatsApp');
        Alert.alert('Error ao abrir WhatsApp');
      });
  };

  const openGoogleMaps = () => {
    const url = `https://www.google.com/maps/search/?api=1&query=${clientes.latitude},${clientes.longitude}`;
    Linking.openURL(url)
      .then((data) => {
        console.log('Google Maps abierto:', data);
      })
      .catch(() => {
        console.log('Error al abrir Google Maps');
      });
  };

  const formatDate = (dateString) => {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-BR', options);
  };
  

  const montarStringParcelas = (parcelas) => {
    const fraseInicial = `
  Relatório de Parcelas Pendentes:
  
  Segue link para acessar todo o histórico de parcelas.

  https://sistema.agecontrole.com.br/#/parcela/${parcelas[0].id}


  Segue abaixo as parcelas pendentes.
  
`;

    const parcelasString = parcelas
      .filter(item => item.atrasadas > 0 && !item.dt_baixa)
      .map(item => {
              return `Data: ${formatDate(item.venc)}
        Parcela: ${item.parcela}
        Atrasos: ${item.atrasadas}
        Valor: R$ ${item.valor.toFixed(2)}
        Juros: R$ ${((item.saldo - item.valor) || 0).toFixed(2)}
        Multa: R$ ${(item.multa || 0).toFixed(2)}
        Pago: R$ ${(item.pago || 0).toFixed(2)}
        PIX: ${item.chave_pix || 'Não Contém'}
        Status: Pendente
        RESTANTE: R$ ${item.saldo.toFixed(2)}`
            })
          .join('\n\n');

        return fraseInicial + parcelasString;
  };
  
  return (
    <KeyBoardAvoidWrapper contentContainerStyle={styles.flexGrow1}>
      <SafeAreaView style={localStyles.main}>
        <View style={styles.ph20}>
          <View style={localStyles.parentComponent}>
            <TouchableOpacity
              style={localStyles.parentMaterial}
              onPress={backToMore}>
              <Material
                name={'arrow-back-ios'}
                size={24}
                color={colors.white}
                style={localStyles.vectorSty}
              />
            </TouchableOpacity>
            <CText type={'B18'} color={colors.white}>
              Baixas
            </CText>
          </View>
        </View>

        <View>
          <Image source={images.Map} style={localStyles.imgSty} />

          <View style={localStyles.outerComponent}>
            {/* <View style={localStyles.outerContainer}>
              <Image style={localStyles.iconSty} source={images.Boy} />

              <View style={{gap: moderateScale(4)}}>
                <CText color={colors.black} type={'B16'}>
                  {clientes.nome_cliente}
                </CText>
                <CText color={colors.black} type={'M12'}>
                  {clientes.endereco}
                </CText>
              </View>
            </View> */}

            {/* <CButton
              onPress={openGoogleMaps}
              text={'Abrir no waze'}
              containerStyle={localStyles.buttonContainer}
              RightIcon={() => (
                <Community
                  size={24}
                  name={'waze'}
                  color={colors.white}
                />
              )}
            />

            <CButton
              onPress={openWhatsApp}
              text={'Ir para o Whatsapp'}
              containerStyle={localStyles.buttonContainer}
              RightIcon={() => (
                <Community
                  size={24}
                  name={'whatsapp'}
                  color={colors.white}
                />
              )}
            /> */}

            <CButton
              onPress={moveToExtornoModel}
              text={'Baixas Efetuadas'}
              containerStyle={localStyles.buttonContainer}
              RightIcon={() => (
                <Community
                  size={24}
                  name={'arrow-u-right-top'}
                  color={colors.white}
                />
              )}
            />

            <CButton
            onPress={moveToInfoModel}
            text={'Baixas Pendentes para Hoje'}
            containerStyle={localStyles.buttonContainer}
            RightIcon={() => (
                <Community
                  size={24}
                  name={'account-cash-outline'}
                  color={colors.white}
                />
              )}
            />
          </View>
        </View>

        {/* <View style={localStyles.mainContainer}>
          <CTextInput
            value={empty}
            onChangeText={nonEmpty}
            LeftIcon={() => (
              <Ionicons
                color={colors.black}
                name={'search-outline'}
                size={24}
              />
            )}
            RightIcon={() => (
              <TouchableOpacity onPress={onPress}>
                <AntDesign name={'close'} size={24} color={colors.SignUpTxt} />
              </TouchableOpacity>
            )}
            text={'Enter the name of ATM'}
            mainTxtInp={localStyles.TxtInpSty}
          />
        </View> */}

        {/* <Location sheetRef={Search} cliente={clientes} parcelas={parcelas} /> */}
        <ParcelasPendentesHoje sheetRef={Info} parcelas={parcelas} clientes={clientes} parcelasPendentes={parcelasPendentes}  onAtualizarClientes={getPendentesParaHoje} />

        <ParcelasExtorno sheetRef={Extorno} parcelas={parcelas} clientes={clientes} parcelasExtorno={parcelasExtorno}  onAtualizarClientes={getPendentesParaHoje} />

        
      </SafeAreaView>
    </KeyBoardAvoidWrapper> 
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.black,
    ...styles.flex
  },
  parentMaterial: {
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(12),
    borderColor: colors.google,
    width: moderateScale(40),
    height: moderateScale(40),
    ...styles.pl10,
    ...styles.center,
    ...styles.mv10,
  },
  parentComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(70),
  },
  imgSty: {
    backgroundColor: colors.white,
    width: moderateScale(375),
    height: moderateScale(600),
  },
  TxtInpSty: {
    ...styles.ph15,
    ...styles.mt20,
    backgroundColor: colors.GreyScale,
    width: '89.5%',
    ...styles.mh20,
  },
  mainContainer: {
    backgroundColor: colors.white,
    height: '100%',
  },
  outerComponent: {
    ...styles.p10,
    ...styles.mh20,
    ...styles.p20,
    ...styles.alignCenter,
    gap: moderateScale(20),
    position: 'absolute',
    bottom: moderateScale(20),
    width: '90%',
    backgroundColor: colors.white,
    borderRadius: moderateScale(20),
  },
  iconSty: {
    width: moderateScale(56),
    height: moderateScale(56),
  },
  outerContainer: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.mr20,
    gap: moderateScale(10),
  },
  buttonContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    ...styles.mt0,
    width: moderateScale(295),
  },
});
