import {
  StyleSheet,
  View,
  Image,
  Text,
  Linking,
  Alert,
  ScrollView,
  TextInput,
} from 'react-native';
import React, {useRef, useState} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import Fonisto from 'react-native-vector-icons/Fontisto';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {TouchableOpacity} from 'react-native-gesture-handler';
import {useNavigation} from '@react-navigation/native';
import {StackNav} from '../../navigation/navigationKeys';
import api from '../../services/api';

export default function ParcelasExtorno(props) {
  let {sheetRef, parcelas, clientes, parcelasExtorno, onAtualizarClientes} = props;
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});
  const [searchText, setSearchText] = useState('');
  const textInputRef = useRef(null);

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

  const saldoTotal = () => {
    let total = 0;
    parcelasExtorno.forEach(item => {
      total += item.valor_recebido_pix;
    });
    return total;
  };

  const baixaManual = async () => {
    let req = await api.baixaManual(parcelas.id, obterDataAtual());

    Alert.alert('Baixa realizada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobrança alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const infoParcelas = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobrança alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const openWhatsApp = () => {
    let url = `whatsapp://send?phone=${parcelas.telefone_celular_1}`;
    url += `&text=${encodeURIComponent('message')}`;

    Linking.openURL(url)
      .then(data => {
        console.log('WhatsApp aberto:', data);
      })
      .catch(() => {
        console.log('Erro ao abrir WhatsApp');
        Alert.alert('Erro ao abrir WhatsApp');
      });
  };

  const openGoogleMaps = () => {
    const url = `https://www.google.com/maps/search/?api=1&query=${parcelas.latitude},${parcelas.longitude}`;
    Linking.openURL(url)
      .then(data => {
        console.log('Google Maps aberto:', data);
      })
      .catch(() => {
        console.log('Erro ao abrir Google Maps');
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

  const onPressClose = item => {
    if (item?.id) {
      setCliente(item);
    } else {
      onAtualizarClientes();
    }
    setVisible(!visible);
  };

  const extornarParcela = item => {
    Alert.alert(
      'Extornar Parcela',
      'Deseja realmente extornar a parcela?',
      [
        {
          text: 'Não',
          onPress: () => console.log('Cancel Pressed'),
          style: 'cancel',
        },
        {text: 'Sim', onPress: () => extornarParcelaSuccess(item)},
      ],
      {cancelable: false},
    );
  };

  const extornarParcelaSuccess = async item => {
    await api.postExtornarParcela(item.id);
    Alert.alert('Parcela extornada com sucesso!');
    onAtualizarClientes();
  };

  const formatDate = dateStr => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
  };

  // Filtrar parcelas com base no texto de pesquisa
  const filteredParcelasExtorno = parcelasExtorno.filter(item =>
    item.nome_cliente.toLowerCase().includes(searchText.toLowerCase()),
  );

  return (
    <View>
      <ActionSheet
        containerStyle={localStyles.actionSheet}
        ref={sheetRef}
        onOpen={() => {
          textInputRef.current?.focus();
        }}>
        <View
          style={{
            flexDirection: 'row',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}>
          <TextInput
            ref={textInputRef}
            style={localStyles.searchInput}
            placeholder="Pesquisar pelo nome do cliente"
            value={searchText}
            onChangeText={setSearchText}
          />
          <TouchableOpacity
            style={localStyles.parentDepEnd}
            onPress={cancelModel}>
            <Community size={40} name={'close'} color={colors.black} />
          </TouchableOpacity>
        </View>

        <ScrollView showsVerticalScrollIndicator={false}>
          <View style={localStyles.mainContainer}>
            <View style={localStyles.outerComponent}>
              <View style={{gap: moderateScale(7)}}>
                <CText color={colors.black} type={'B24'}>
                  Extorno de Parcelas
                </CText>
                <CText color={colors.black} type={'M16'}>
                  Saldo total{' '}
                  {saldoTotal().toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </CText>
                <CText color={colors.black} type={'M16'}>
                  Clique na parcela para efetuar o extorno!
                </CText>
              </View>
            </View>

            {filteredParcelasExtorno.map(item => (
              <View key={item.id} style={styles2.container}>
                <Text style={styles2.subTitle}>
                  {item.nome_cliente} - CPF: {item.cpf}
                </Text>
                <Text style={styles2.totalDueText}>
                  Valor da Parcela{' '}
                  {item.saldo.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                  })}
                </Text>

                {item.valor_recebido > 0 && (
                  <Text style={styles2.subTitleValor}>
                    Valor recebido em dinheiro{' '}
                    {item.valor_recebido?.toLocaleString('pt-BR', {
                      style: 'currency',
                      currency: 'BRL',
                    })}
                  </Text>
                )}

                {item.valor_recebido_pix > 0 && (
                  <Text style={styles2.subTitleValor}>
                    Valor recebido em dinheiro{' '}
                    {item.valor_recebido_pix?.toLocaleString('pt-BR', {
                      style: 'currency',
                      currency: 'BRL',
                    })}
                  </Text>
                )}

                <View style={styles2.buttonContainer}>
                  <TouchableOpacity
                    onPress={() => extornarParcela(item)}
                    style={styles2.actionButton}>
                    <Text style={styles2.buttonText}>Extornar Baixa</Text>
                  </TouchableOpacity>
                </View>
              </View>
            ))}
          </View>
        </ScrollView>
      </ActionSheet>
    </View>
  );
}

const styles2 = StyleSheet.create({
  container: {
    padding: 20,
    backgroundColor: '#F7F6F6FF',
    flex: 1,
    marginTop: 10,
    marginBottom: 10,
    borderRadius: 20,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 5,
  },
  subTitle: {
    fontSize: 14,
    color: '#888',
    marginBottom: 10,
  },
  subTitleValor: {
    fontSize: 14,
    color: '#3CA454FF',
    marginTop: -15,
    marginBottom: 20,
  },
  addressLabel: {
    fontSize: 16,
    marginBottom: 5,
  },
  phoneButton: {
    backgroundColor: '#f1f1f1',
    padding: 10,
    borderRadius: 5,
    marginBottom: 10,
    alignItems: 'center',
  },
  phoneText: {
    fontSize: 16,
    color: '#000',
  },
  infoText: {
    fontSize: 16,
    marginBottom: 5,
  },
  totalDueText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#d9534f',
    marginBottom: 20,
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    backgroundColor: '#e0e0e0',
    paddingVertical: 10,
    paddingHorizontal: 15,
    borderRadius: 5,
  },
  buttonText: {
    fontSize: 14,
  },
  valorHoje: {
    fontSize: 14,
    color: '#666',
    marginTop: 15,
  },
});

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
    ...styles.ml20,
    ...styles.mr20,
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
    ...styles.mt50,
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
    ...styles.mb20,
  },
  searchInput: {
    flex: 1,
    height: moderateScale(40),
    borderColor: '#c6c6c6',
    borderWidth: 1,
    borderRadius: 5,
    paddingHorizontal: 10,
    marginRight: moderateScale(10),
    marginLeft: moderateScale(20),
  },
});