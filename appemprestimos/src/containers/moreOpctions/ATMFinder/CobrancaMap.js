import React, {useRef, useState, useCallback} from 'react';
import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  Linking,
  Alert,
} from 'react-native';
import Material from 'react-native-vector-icons/MaterialIcons';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';
import {useFocusEffect} from '@react-navigation/native';
import {colors} from '../../../themes/colors';
import {styles} from '../../../themes/index';
import {moderateScale} from '../../../common/constant';
import CText from '../../../components/common/CText';
import images from '../../../assets/images/index';
import CButton from '../../../components/common/CButton';
import KeyBoardAvoidWrapper from '../../../components/common/KeyBoardAvoidWrapper';
import Location from '../../../components/modals/Location';
import InfoParcelas from '../../../components/modals/InfoParcelas';
import api from '../../../services/api';
import FullScreenLoader from '../../../components/FullScreenLoader';
import {StackNav, TabNav} from '../../../navigation/navigationKeys';

export default function ATMDetails({navigation, route}) {
  const {clientes} = route.params;
  const [empty, nonEmpty] = useState('');
  const [parcelas, setParcelas] = useState([]);
  const [loading, setLoading] = useState(false);
  const Search = useRef(null);
  const Info = useRef(null);

  useFocusEffect(
    useCallback(() => {
      getInfo();
    }, [])
  );

  const getInfo = async () => {
    setLoading(true);
    try {
      const reqClientes = await api.getParcelasInfoEmprestimo(clientes.id);
      setParcelas(reqClientes.data);
    } catch (error) {
      console.error('Erro ao obter informações:', error);
    } finally {
      setLoading(false);
    }
  };

  const moveToInfoModel = () => Info.current.show();
  const backToMore = () => navigation.navigate(TabNav.HomeScreen);

  const obterDataAtual = () => {
    const data = new Date();
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
  };

  const cobrarAmanha = async () => {
    try {
      await api.cobrarAmanha(clientes.id, obterDataAtual());
      Alert.alert('Cobrança alterada com sucesso!');
      navigation.navigate(StackNav.TabNavigation);
    } catch (error) {
      console.error('Erro ao cobrar:', error);
    }
  };

  const openWhatsApp = () => {
    let telefone = clientes.telefone_celular_1.replace(/\D/g, '');
    if (!telefone.startsWith('55')) telefone = `55${telefone}`;
    if (telefone.length < 12) {
      Alert.alert('Número de telefone inválido', 'Verifique o formato do número.');
      return;
    }
    const url = `whatsapp://send?phone=${telefone}&text=${encodeURIComponent(montarStringParcelas(parcelas))}`;
    Linking.openURL(url).catch(() => Alert.alert('Erro ao abrir WhatsApp'));
  };

  const openGoogleMaps = () => {
    const url = `https://www.google.com/maps/search/?api=1&query=${clientes.latitude},${clientes.longitude}`;
    Linking.openURL(url).catch(() => Alert.alert('Erro ao abrir Google Maps'));
  };

  const montarStringParcelas = (parcelas) => {
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
      .map(item => `
Data: ${item.venc}
Parcela: ${item.parcela}
Atrasos: ${item.atrasadas}
Valor: R$ ${item.valor}
Multa: R$ ${item.multa}
Pago: R$ ${item.total_pago_parcela}
PIX: ${item.chave_pix || 'Não Contém'}
Status: Pendente
RESTANTE: R$ ${item.saldo.toFixed(2)}
`).join('\n\n');

    return fraseInicial + parcelasString;
  };

  return (
    <KeyBoardAvoidWrapper contentContainerStyle={styles.flexGrow1}>
      <FullScreenLoader visible={loading} />
      <SafeAreaView style={localStyles.main}>
        <View style={styles.ph20}>
          <View style={localStyles.parentComponent}>
            <TouchableOpacity style={localStyles.parentMaterial} onPress={backToMore}>
              <Material name={'arrow-back-ios'} size={24} color={colors.white} style={localStyles.vectorSty} />
            </TouchableOpacity>
            <CText type={'B18'} color={colors.white}>Cobrança</CText>
          </View>
        </View>
        <View>
          <Image source={images.Map} style={localStyles.imgSty} />
          <View style={localStyles.outerComponent}>
            <View style={localStyles.outerContainer}>
              <Image style={localStyles.iconSty} source={images.AGE} />
              <View style={{gap: moderateScale(4)}}>
                <CText color={colors.black} type={'B16'}>{clientes.nome_cliente}</CText>
                <CText color={colors.black} type={'M12'}>{clientes.endereco}</CText>
              </View>
            </View>
            <CButton onPress={openGoogleMaps} text={'Abrir no waze'} containerStyle={localStyles.buttonContainer} RightIcon={() => <Community size={24} name={'waze'} color={colors.white} />} />
            <CButton onPress={openWhatsApp} text={'Ir para o Whatsapp'} containerStyle={localStyles.buttonContainer} RightIcon={() => <Community size={24} name={'whatsapp'} color={colors.white} />} />
            <CButton onPress={cobrarAmanha} text={'Cobrar Amanha'} containerStyle={localStyles.buttonContainer} RightIcon={() => <Community size={24} name={'arrow-u-right-top'} color={colors.white} />} />
            <CButton onPress={moveToInfoModel} text={'Informações das Parcelas'} containerStyle={localStyles.buttonContainer} RightIcon={() => <Community size={24} name={'account-cash-outline'} color={colors.white} />} />
          </View>
        </View>
        <Location sheetRef={Search} cliente={clientes} parcelas={parcelas} />
        <InfoParcelas sheetRef={Info} parcelas={parcelas} clientes={clientes} getInfo={getInfo} />
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