import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  Linking,
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
import InfoParcelas from '../../../components/modals/InfoParcelas';
import {useFocusEffect} from '@react-navigation/native';
import api from '../../../services/api';
import FullScreenLoader from '../../../components/FullScreenLoader';

import {StackNav, TabNav} from '../../../navigation/navigationKeys';

export default function ATMDetails({navigation, route}) {
  const {clientes} = route.params;

  const [empty, nonEmpty] = useState('');
  const [parcelas, setParcelas] = useState([]);
  const [loading, setLoading] = useState(false);
  useFocusEffect(
    React.useCallback(() => {
      getInfo();
    }, []),
  );

  const getInfo = async position => {
    setLoading(true);
    console.log('aquiclientte', clientes)
    let reqClientes = await api.getParcelasInfoEmprestimo(clientes.id);
    setParcelas(reqClientes.data);
    setLoading(false);
  };

  const Search = useRef(null);
  const Info = useRef(null);

  const moveToInfoModel = () => {
    Info.current.show();
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

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(clientes.id, obterDataAtual());

    alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const formatPhoneNumber = phone => {
    // Remove todos os caracteres não numéricos
    const cleaned = phone.replace(/[^\d]/g, '');

    // Verifica se o número tem 10 dígitos (incluindo o DDD)
    if (cleaned.length === 10) {
      const ddd = cleaned.slice(0, 2); // Extrai o DDD
      const restOfNumber = cleaned.slice(2); // Extrai o restante do número
      return ddd + '9' + restOfNumber; // Adiciona o "9" após o DDD
    }

    // Retorna o número como está se já tiver 11 dígitos
    return cleaned;
  };

  const openWhatsApp = () => {
    console.log('clientes', clientes);
    let telefone = clientes.telefone_celular_1;
  
    // Remove todos os caracteres não numéricos
    telefone = telefone.replace(/\D/g, '');
  
    // Adiciona o código do país, se necessário
    if (!telefone.startsWith('55')) {
      telefone = `55${telefone}`;
    }
  
    // Verifica se o número tem pelo menos 11 dígitos (código de área + número)
    if (telefone.length < 12) {
      Alert.alert('Número de telefone inválido', 'Verifique o formato do número.');
      return;
    }
  
    let url = `whatsapp://send?phone=${telefone}`;
    url += `&text=${encodeURIComponent(montarStringParcelas(parcelas))}`;
  
    Linking.openURL(url)
      .then((data) => {
        console.log('WhatsApp aberto:', data);
      })
      .catch(() => {
        console.log('Erro ao abrir WhatsApp');
        Alert.alert('Erro ao abrir WhatsApp');
      });
  };

  const openGoogleMaps = () => {
    const url = `https://www.google.com/maps/search/?api=1&query=${clientes.latitude},${clientes.longitude}`;
    Linking.openURL(url)
      .then(data => {
        console.log('Google Maps abierto:', data);
      })
      .catch(() => {
        console.log('Error al abrir Google Maps');
      });
  };

  const formatDate = dateString => {
    const options = {day: '2-digit', month: '2-digit', year: 'numeric'};
    return new Date(dateString).toLocaleDateString('pt-BR', options);
  };

  const montarStringParcelas = parcelas => {
    const fraseInicial = `
Relatório de Parcelas:
  
Segue link para acessar todo o histórico de parcelas.

https://sistema.agecontrole.com.br/#/parcela/${parcelas[0].id}

Beneficiario: ${parcelas[0].beneficiario} PIX: ${parcelas[0].chave_pix}
Saldo para quitação: ${parcelas[0].total_pendente}
Saldo pendente para hoje: ${parcelas[0].total_pendente_hoje}

Segue abaixo as parcelas pendentes.

  
`;

    const parcelasString = parcelas
      .filter(item => item.atrasadas > 0 && !item.dt_baixa)
      .map(item => {
        return `Data: ${item.venc}
        Parcela: ${item.parcela}
        Atrasos: ${item.atrasadas}
        Valor: R$ ${item.valor}
        Multa: R$ ${item.multa}
        Pago: R$ ${item.total_pago_parcela}
        PIX: ${item.chave_pix || 'Não Contém'}
        Status: Pendente
        RESTANTE: R$ ${item.saldo.toFixed(2)}`;
      })
      .join('\n\n');

    return fraseInicial + parcelasString;
  };

  return (
    <KeyBoardAvoidWrapper contentContainerStyle={styles.flexGrow1}>
      <FullScreenLoader visible={loading} />
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
              Cobrança
            </CText>
          </View>
        </View>

        <View>
          <Image source={images.Map} style={localStyles.imgSty} />

          <View style={localStyles.outerComponent}>
            <View style={localStyles.outerContainer}>
              <Image style={localStyles.iconSty} source={images.AGE} />

              <View style={{gap: moderateScale(4)}}>
                <CText color={colors.black} type={'B16'}>
                  {clientes.nome_cliente}
                </CText>
                <CText color={colors.black} type={'M12'}>
                  {clientes.endereco}
                </CText>
              </View>
            </View>

            <CButton
              onPress={openGoogleMaps}
              text={'Abrir no waze'}
              containerStyle={localStyles.buttonContainer}
              RightIcon={() => (
                <Community size={24} name={'waze'} color={colors.white} />
              )}
            />

            <CButton
              onPress={openWhatsApp}
              text={'Ir para o Whatsapp'}
              containerStyle={localStyles.buttonContainer}
              RightIcon={() => (
                <Community size={24} name={'whatsapp'} color={colors.white} />
              )}
            />

            <CButton
              onPress={cobrarAmanha}
              text={'Cobrar Amanha'}
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
              text={'Informações das Parcelas'}
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

        <Location sheetRef={Search} cliente={clientes} parcelas={parcelas} />
        <InfoParcelas
          sheetRef={Info}
          parcelas={parcelas}
          clientes={clientes}
          getInfo={getInfo}
        />
      </SafeAreaView>
    </KeyBoardAvoidWrapper>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.black,
    ...styles.flex,
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
