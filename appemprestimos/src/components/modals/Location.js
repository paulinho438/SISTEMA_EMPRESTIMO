import {StyleSheet, View, Image, Text, Linking, Alert} from 'react-native';
import React, {useRef, useState} from 'react';
import ActionSheet, {FlatList} from 'react-native-actions-sheet';
import Fonisto from 'react-native-vector-icons/Fontisto';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import {LocationData} from '../../api/constants';
import CButton from '../common/CButton';
import {TouchableOpacity} from 'react-native-gesture-handler';
import {useNavigation} from '@react-navigation/native';
import {StackNav, TabNav} from '../../navigation/navigationKeys';
import Saldo from '../modals/Saldo';
import api from '../../services/api';

export default function Location(props) {
  let {sheetRef, cliente, parcelas} = props;
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);

  const renderData = ({item}) => {
    return (
      <TouchableOpacity>
        <View style={localStyles.mainComponent}>
          <Image style={localStyles.imageStyle} source={item.image} />
          <CText align={'center'} type={'M12'} color={colors.black}>
            {item.reviews}
          </CText>
        </View>
      </TouchableOpacity>
    );
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

  const baixaManual = async () => {
    let req = await api.baixaManual(cliente.id, obterDataAtual());

    Alert.alert('Baixa realizada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(cliente.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const infoParcelas = async () => {
    let req = await api.cobrarAmanha(cliente.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const openWhatsApp = () => {



    let telefone = cliente.telefone_celular_1;

    telefone = telefone.replace(/\)\s*/, ') 9');

    let url = `whatsapp://send?phone=${cliente.telefone_celular_1}`;
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
    const url = `https://www.google.com/maps/search/?api=1&query=${cliente.latitude},${cliente.longitude}`;
    Linking.openURL(url)
      .then((data) => {
        console.log('Google Maps abierto:', data);
      })
      .catch(() => {
        console.log('Error al abrir Google Maps');
      });
  };

  const cancelModel = () => {
    sheetRef.current?.hide();
  };

  const formatDate = (dateString) => {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-BR', options);
  };
  

  const montarStringParcelas = (parcelas) => {
    const fraseInicial = `
  Relatório de Parcelas:
  
  Segue link para acessar todo o histórico de parcelas.

  https://sistema.agecontrole.com.br/#/parcela/${parcelas[0].id}

  Beneficiario: ${parcelas[0].beneficiario} pix:${parcelas[0].chave_pix}

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
        Valor: R$ ${item.valor.toFixed(2)}
        Multa: R$ ${item.multa}
        Pago: R$ ${item.total_pago_parcela}
        PIX: ${item.chave_pix || 'Não Contém'}
        Status: Pendente
        RESTANTE: R$ ${item.saldo.toFixed(2)}`
            })
          .join('\n\n');

        return fraseInicial + parcelasString;
  };

  const arrowRightTopIcon = () => (
    <Community size={24} name={'arrow-u-right-top'} color={colors.white} />
  );

  const whatsapp = () => (
    <Community size={24} name={'whatsapp'} color={colors.white} />
  );

  const check = () => (
    <Community size={24} name={'check'} color={colors.white} />
  );

  const waze = () => (
    <Community size={24} name={'waze'} color={colors.white} />
  );

  const onPressClose = () => {
    setVisible(!visible);
  };

  return (
    <View>
      <ActionSheet containerStyle={localStyles.actionSheet} ref={sheetRef}>
        <View style={localStyles.mainContainer}>
        <TouchableOpacity style={localStyles.parentDepEnd}  onPress={cancelModel}>
          <Community size={40} name={'close'} color={colors.black} />
        </TouchableOpacity>

          <View style={localStyles.outerComponent}>
            <View style={{gap: moderateScale(7)}}>
              <CText
                color={colors.black}
                style={localStyles.BOATxt}
                type={'B24'}>
                {cliente.nome_cliente}
              </CText>
              <CText color={colors.black} type={'M12'}>
                {cliente.endereco}
              </CText>
            </View>

            <Fonisto name={'bookmark'} size={24} color={colors.black} />
          </View>

          <CButton
            onPress={openGoogleMaps}
            text={'Abrir no waze'}
            containerStyle={localStyles.buttonContainer}
            RightIcon={waze}
          />
          <CButton
            onPress={openWhatsApp}
            text={'Ir para o Whatsapp'}
            containerStyle={localStyles.buttonContainer}
            RightIcon={whatsapp}
          />
          <CButton
            onPress={cobrarAmanha}
            text={'Cobrar Amanha'}
            containerStyle={localStyles.buttonContainer}
            RightIcon={arrowRightTopIcon}
          />
          
          
        </View>
        <Saldo
            visible={visible}
            onPressClose={onPressClose}
            cliente={cliente}
            //valores={valores}
            //feriados={feriados}
          />
      </ActionSheet>
      
    </View>
  );
}

const localStyles = StyleSheet.create({
  imgSty: {
    width: moderateScale(330),
    height: moderateScale(100),
    ...styles.selfCenter,
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
  },
  mainContainer: {
    ...styles.m20,
  },
  BOATxt: {
    ...styles.mt30,
  },
  outerComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  imageStyle: {
    width: moderateScale(40),
    height: moderateScale(40),
  },
  mainComponent: {
    gap: moderateScale(10),
    ...styles.justifyEvenly,
    ...styles.alignCenter,
    ...styles.p15,
    ...styles.mh5,
    width: moderateScale(101),
    height: moderateScale(106),
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.bottomBorder,
  },
  outerContainer: {
    ...styles.mt25,
  },
  buttonContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
  },
  parentDepEnd: {
    ...styles.alignEnd,
  },
});


