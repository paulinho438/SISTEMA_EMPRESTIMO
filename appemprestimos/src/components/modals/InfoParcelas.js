import {StyleSheet, View, Image, Text, Linking, Alert, ScrollView} from 'react-native';
import React, {useState} from 'react';
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
import api from '../../services/api';
import Saldo from '../modals/Saldo';
import margin from '../../themes/margin';

export default function InfoParcelas(props) {
  let {sheetRef, parcelas, clientes, getInfo} = props;
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});
  

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
    let req = await api.baixaManual(parcelas.id, obterDataAtual());

    Alert.alert('Baixa realizada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const infoParcelas = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const openWhatsApp = () => {
    let url = `whatsapp://send?phone=${parcelas.telefone_celular_1}`;
    url += `&text=${encodeURIComponent('message')}`;

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
    const url = `https://www.google.com/maps/search/?api=1&query=${parcelas.latitude},${parcelas.longitude}`;
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

  const arrowParcelaIcon = () => (
    <Community size={24} name={'account-cash-outline'} color={colors.white} />
  );

  const arrowRightTopIcon = () => (
    <Community size={24} name={'arrow-u-right-top'} color={colors.white} />
  );

  const whatsapp = () => (
    <Community size={24} name={'whatsapp'} color={colors.white} />
  );

  const close = () => (
    <Community size={24} name={'close-outline'} color={colors.white} />
  );

  const check = () => (
    <Community size={24} name={'check'} color={colors.white} />
  );

  const timer = () => (
    <Community size={24} name={'timer-sand-empty'} color={colors.white} />
  );

  const formatCurrency = value => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  const onPressClose = (item) => {
    if(item?.id){
      setCliente(item)
    }
    getInfo();
    setVisible(!visible);
  };

  const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR', { year: 'numeric', month: '2-digit', day: '2-digit' });
  };


  return (
    <View>
      <ActionSheet containerStyle={localStyles.actionSheet} ref={sheetRef}>
      <TouchableOpacity style={localStyles.parentDepEnd}  onPress={cancelModel}>
        <Community size={40} name={'close'} color={colors.black} />
      </TouchableOpacity>
      <ScrollView showsVerticalScrollIndicator={false}>
      <View style={localStyles.mainContainer}>
      
      
      <View style={localStyles.outerComponent}>
        <View style={{gap: moderateScale(7)}}>
          <CText
            color={colors.black}
            type={'B24'}>
            Informações do Empréstimo
          </CText>
          <CText color={colors.black} type={'M16'}>
            Clique na parcela para efetuar a baixa!
          </CText>
         
        </View>
      </View>
      <View style={localStyles.outerComponent2}>
        <View style={{gap: moderateScale(7)}}>
          <CText color={colors.Green} type={'M16'}>
            Total Pago {formatCurrency(clientes.total_pago_emprestimo)}
          </CText>
          <CText color={colors.red} type={'M16'}>
            Saldo a Pagar {formatCurrency(clientes.total_pendente)}
          </CText>
        </View>
      </View>

      {parcelas.map(item => (
        <CButton
          key={item.id}
          onPress={() => onPressClose(item)}
          text={
            !item.dt_baixa && !item.valor_recebido ? `Venc. ${item.venc} ${item.saldo.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL',
            })}` :
            item.dt_baixa ? `Dt. Baixa ${item.dt_baixa} R$ ${item.total_pago_parcela.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL',
            })}` :
            `Venc. ${item.venc} R$ ${item.saldo.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL',
            })}
Baixa Manual ${item.valor_recebido.toLocaleString('pt-BR', {
  style: 'currency',
  currency: 'BRL',
})}` 
          }
          containerStyle={
            item.atrasadas > 0 && !item.dt_baixa ? localStyles.buttonContainerRed :
            item.dt_baixa ? localStyles.buttonContainerGreen :
            localStyles.buttonContainerPrimary
          }
          RightIcon={
            item.atrasadas > 0 && !item.dt_baixa ? close :
            item.dt_baixa ? check :
            timer
          }
          disabled={
            item.dt_baixa ? true :
            false
          }
        />
      ))}

      </View>
        <Saldo
            visible={visible}
            onPressClose={onPressClose}
            cliente={cliente}
            pendenteHoje={cliente}
            //valores={valores}
            //feriados={feriados}
          />
      </ScrollView>
        
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

  outerComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  outerComponent2: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt50
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
    backgroundColor: colors.red,
  },
  buttonContainerRed: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.red,
  },
  buttonContainerGreen: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Green,
  },
  buttonContainerPrimary: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Primary,
  },
  parentDepEnd: {
    ...styles.alignEnd,
    ...styles.mr25,
    ...styles.mt30,
    ...styles.mb20
  },
});


