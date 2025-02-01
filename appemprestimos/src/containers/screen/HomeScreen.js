import {
  StyleSheet,
  View,
  Text,
  SafeAreaView,
  Image,
  TouchableOpacity,
  FlatList,
  ScrollView,
  Platform,
  PermissionsAndroid,
  RefreshControl,
} from 'react-native';
import React, {useEffect, useState} from 'react';

// Local imports
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CText from '../../components/common/CText';
import CNotification from '../../components/common/CNotification';
import CTextInput from '../../components/common/CTextInput';
import images from '../../assets/images/index';
import {HomeData} from '../../api/constants';
import {StackNav} from '../../navigation/navigationKeys';
import {Dropdown} from 'react-native-element-dropdown';
import {ListClient} from '../../api/constants';
import {getHeight, moderateScale} from '../../common/constant';

import {PieChart, BarChart} from 'react-native-svg-charts';
import {G, Text as SVGText} from 'react-native-svg';

import {
  getAuthCompany,
  getUser,
  getPermissions,
} from '../../utils/asyncStorage';
import {useFocusEffect} from '@react-navigation/native';

import Geolocation from 'react-native-geolocation-service';

import api from '../../services/api';

export default function HomeScreen({navigation}) {
  const [company, setCompany] = useState(null);
  const [user, setUser] = useState(null);
  const [clientes, setClientes] = useState([]);
  const [clientesOrig, setClientesOrig] = useState([]);
  const [location, setLocation] = useState(null);
  const [tipoCliente, setTipoCliente] = useState('');
  const [search, setSearch] = useState('');
  const [permissoesHoje, setPermissoesHoje] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [data, setData] = useState({
    verde: 0,
    amarelo: 0,
    vermelho: 0,
  });

  const formatCurrency = value => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  const onRefresh = async () => {
    setRefreshing(true);

    try {
      async function fetchData() {
        let a = await getPermissions();
        setPermissoesHoje(a);
      }
      fetchData();

      const requestLocationPermission = async () => {
        setTipoCliente('');
        if (Platform.OS === 'ios') {
          Geolocation.requestAuthorization('whenInUse');
          getLocation();
        } else {
          const granted = await PermissionsAndroid.request(
            PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
            {
              title: 'Location Access Permission',
              message: 'We need access to your location',
              buttonNeutral: 'Ask Me Later',
              buttonNegative: 'Cancel',
              buttonPositive: 'OK',
            },
          );

          if (granted === PermissionsAndroid.RESULTS.GRANTED) {
            getLocation();
          } else {
            console.log('Location permission denied');
          }
        }
      };

      const getLocation = () => {
        Geolocation.getCurrentPosition(
          position => {
            setLocation(position);
            getInfo(position);
          },
          error => {
            console.log(error.code, error.message);
          },
          {enableHighAccuracy: true, timeout: 15000, maximumAge: 10000},
        );
      };

      requestLocationPermission();
    } catch (error) {
      console.error('Erro ao atualizar clientes:', error);
    }
  };

  useFocusEffect(
    React.useCallback(() => {
      let azul = 0;
      let verde = 0;
      let amarelo = 0;
      let vermelho = 0;

      // Processa os dados de atraso das parcelas
      clientes.forEach(clientes => {
        const diasAtraso = clientes.atrasadas;

        if (diasAtraso == 0) {
          azul += 1; // Sem atraso
        } else if (diasAtraso >= 1 && diasAtraso <= 2) {
          verde += 1; // Entre 1 e 5 dias de atraso
        } else if (diasAtraso >= 3 && diasAtraso <= 10) {
          amarelo += 1; // Entre 1 e 5 dias de atraso
        } else if (diasAtraso > 10) {
          vermelho += 1; // Mais de 5 dias de atraso
        }
      });

      setData({azul, verde, amarelo, vermelho});
    }, [clientes]),
  );

  useFocusEffect(
    React.useCallback(() => {
      setFilterSearch();
    }, [search]),
  );

  useFocusEffect(
    React.useCallback(() => {
      async function fetchData() {
        let a = await getPermissions();
        setPermissoesHoje(a);
      }
      fetchData();

      const requestLocationPermission = async () => {
        setTipoCliente('');
        if (Platform.OS === 'ios') {
          Geolocation.requestAuthorization('whenInUse');
          getLocation();
        } else {
          const granted = await PermissionsAndroid.request(
            PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
            {
              title: 'Location Access Permission',
              message: 'We need access to your location',
              buttonNeutral: 'Ask Me Later',
              buttonNegative: 'Cancel',
              buttonPositive: 'OK',
            },
          );

          if (granted === PermissionsAndroid.RESULTS.GRANTED) {
            getLocation();
          } else {
            console.log('Location permission denied');
          }
        }
      };

      const getLocation = () => {
        Geolocation.getCurrentPosition(
          position => {
            setLocation(position);
            getInfo(position);
          },
          error => {
            console.log(error.code, error.message);
          },
          {enableHighAccuracy: true, timeout: 15000, maximumAge: 10000},
        );
      };

      requestLocationPermission();

      return () => {};
    }, []),
  );

  useEffect(() => {
    if (tipoCliente.value == 1) {
      setClientes(clientesOrig);
    } else if (tipoCliente.value == 2) {
      const filteredData = clientesOrig.filter(item => item.atrasadas > 10);

      setClientes(filteredData);
    } else if (tipoCliente.value == 3) {
      const filteredData = clientesOrig.filter(
        item => item.atrasadas >= 3 && item.atrasadas <= 10,
      );

      setClientes(filteredData);
    } else if (tipoCliente.value == 4) {
      const filteredData = clientesOrig.filter(
        item => item.atrasadas >= 1 && item.atrasadas <= 2,
      );

      setClientes(filteredData);
    } else {
      const filteredData = clientesOrig.filter(item => item.atrasadas == 0);

      setClientes(filteredData);
    }
  }, [tipoCliente]);

  const pieData = [
    {
      key: 1,
      value: 60,
      svg: {fill: '#4A90E2'}, // Azul para "Investimento"
    },
    {
      key: 2,
      value: 40,
      svg: {fill: '#4CAF50'}, // Verde para "Lucro"
    },
  ];

  // Dados para o BarChart
  const barData = [
    {
      value: 50,
      svg: {fill: '#4A90E2'},
      label: 'Nov',
    },
    {
      value: 20,
      svg: {fill: '#4A90E2'},
      label: 'Dez',
    },
  ];

  const havePermissionsFunction = permission => {
    if (permissoesHoje.includes(permission)) {
      return true;
    } else {
      return false;
    }
  };

  const setFilterSearch = () => {
    if (search) {
      const newData = clientesOrig.filter(item => {
        return (
          item.nome_completo
            .toLocaleLowerCase()
            .indexOf(search.toLocaleLowerCase()) > -1
        );
      });
      setClientes(newData);
    } else {
      setClientes(clientesOrig);
    }
  };

  const getInfo = async position => {
    let companyReq = await getAuthCompany();
    setCompany(companyReq);

    let userReq = await getUser();
    setUser(userReq);

    let reqClientes = await api.getClientesPendentes();

    setClientes(reqClientes);
    setClientesOrig(reqClientes);
    setRefreshing(false);
  };

  const haversineDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // Raio da Terra em Km
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;

    lat1 = (lat1 * Math.PI) / 180;
    lat2 = (lat2 * Math.PI) / 180;

    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c; // Distância em km

    return distance;
  };

  const cobrancaMap = item => {
    navigation.navigate(StackNav.CobrancaMap, {
      clientes: item,
    });
  };

  const baixaMap = item => {
    navigation.navigate(StackNav.BaixaMap, {
      clientes: clientes[0],
    });
  };

  const moveToTrans = () => {
    navigation.navigate(StackNav.TransferMoney);
  };

  const moveToDeposit = () => {
    navigation.navigate(StackNav.TopUpScreen);
  };

  const moveToWith = () => {
    navigation.navigate(StackNav.Clientes);
  };

  const moveToAll = () => {
    navigation.navigate(StackNav.HistoryTrans);
  };

  const moveToNot = async () => {
    navigation.navigate(StackNav.Notification);
  };

  const dataAtual = () => {
    const meses = [
      'Janeiro',
      'Fevereiro',
      'Março',
      'Abril',
      'Maio',
      'Junho',
      'Julho',
      'Agosto',
      'Setembro',
      'Outubro',
      'Novembro',
      'Dezembro',
    ];
    const data = new Date();
    const dia = data.getDate();
    const mes = meses[data.getMonth()];
    return `Hoje, ${dia} de ${mes}`;
  };

  const moveToOpt = () => {
    navigation.navigate(StackNav.MoreOptions);
  };

  const BotaoComponent = () => {
    return (
      <View style={localStyles.parentTodayTxt}>
        <CTextInput
          mainTxtInp={localStyles.CTxtInp}
          value={search}
          onChangeText={text => setSearch(text)}
          text={'Pesquisar Cliente...'}
        />
      </View>
    );
  };

  const ListHeaderComponent = () => {
    return (
      <View>
        <View
          style={{
            flex: 1,
            justifyContent: 'center',
            alignItems: 'center',
          }}></View>
        <View style={localStyles.main}>
          <View style={localStyles.mainParent}>
            <View>
              <CText color={colors.white} style={localStyles.WBTxt}>
                {strings.WB}
              </CText>
              <CText
                color={colors.white}
                type={'B18'}
                style={localStyles.AnnaTxt}>
                {user?.nome_completo}
              </CText>
              <CText
                color={colors.white}
                type={'B18'}
                style={localStyles.AnnaTxt}>
                {company?.company}
              </CText>
            </View>

            {/* <CNotification onPress={moveToNot} /> */}
          </View>
        </View>
        <ChartExample parcelas={clientes} />

        {/* <View style={localStyles.ParentImg}>
            <Image source={images.cardBalance} style={localStyles.card3Style} />
            <View style={localStyles.parentNomeEmpresa}>
              <CText
                color={colors.white}
                type={'B18'}
                style={localStyles.NameEmpresa}>
                {company?.company}
              </CText>
            </View>
          </View> */}

        <View style={localStyles.mainImg}>
          {/* {havePermissionsFunction('view_emprestimos_autorizar_pagamentos') && (
            <FirstImage
              image={images.Deposit}
              text="Aprovação"
              onPress={moveToDeposit}
            />
          )} */}
          <FirstImage
            image={images.Withdraw}
            text="Emprestimo"
            onPress={moveToWith}
          />
          {havePermissionsFunction('aplicativo_baixas') && (
            <FirstImage
              image={images.Transfer}
              text="Baixas"
              onPress={baixaMap}
            />
          )}

          <FirstImage
            image={images.More}
            text={strings.More}
            onPress={moveToOpt}
          />
        </View>

        {BotaoComponent()}

        <View style={localStyles.parentTodayTxt}>
          <View style={localStyles.parentTxtInp}>
            <Dropdown
              style={localStyles.dropdownStyle}
              data={ListClient}
              value={tipoCliente}
              maxHeight={moderateScale(150)}
              labelField="label"
              valueField="value"
              placeholder="Selecione o Status"
              onChange={setTipoCliente}
              selectedTextStyle={localStyles.miniContainer}
              itemTextStyle={localStyles.miniContainer}
              itemContainerStyle={{
                backgroundColor: colors.GreyScale,
                width: 'auto',
              }}
            />
          </View>

          {/*<TouchableOpacity onPress={moveToAll}>
            <CText color={colors.black} type={'M14'}>
              Todos os pendentes
            </CText>
              </TouchableOpacity>*/}
        </View>

        <View style={localStyles.parentTodayTxt}>
          <CText type={'B14'} color={colors.tabColor}>
            {dataAtual()}
          </CText>
        </View>
      </View>
    );
  };

  const corSelect = at => {
    if (at == 0) {
      return '#194ADFFF';
    } else if (at >= 1 && at <= 2) {
      return '#07FF41FF';
    } else if (at >= 3 && at <= 10) {
      return '#EFF616FF';
    } else if (at > 10) {
      return '#F34646';
    }
  };

  const FirstImage = ({image, text, onPress}) => {
    return (
      <TouchableOpacity style={localStyles.parentDep} onPress={onPress}>
        <Image source={image} style={localStyles.childImg} />
        <CText type={'M12'} color={colors.black} style={localStyles.Txt}>
          {text}
        </CText>
      </TouchableOpacity>
    );
  };

  const ChartExample = () => {
    // Dados para o PieChart com valores dinâmicos
    const pieData = [
      {key: 1, value: data.azul, svg: {fill: '#194ADFFF'}}, // Verde
      {key: 2, value: data.verde, svg: {fill: '#4CAF50'}}, // Verde
      {key: 3, value: data.amarelo, svg: {fill: '#FFC107'}}, // Amarelo
      {key: 4, value: data.vermelho, svg: {fill: '#F34646'}}, // Vermelho
    ].filter(item => item.value !== 0);

    // Função para adicionar labels de valor no PieChart
    const Labels = ({slices}) => {
      return slices.map((slice, index) => {
        const {labelCentroid, data} = slice;
        return (
          <SVGText
            key={index}
            x={labelCentroid[0]}
            y={labelCentroid[1]}
            fill="white"
            fontSize={14}
            fontWeight="bold"
            textAnchor="middle"
            alignmentBaseline="middle">
            {data.value}
          </SVGText>
        );
      });
    };

    return (
      <View style={styles2.container}>
        {/* Título */}
        <Text style={styles2.title}>Status de Atrasos 📆</Text>

        {/* Legenda */}
        <View style={styles2.legend}>
          <Text style={[styles2.legendItem, {color: '#194ADFFF'}]}>
            ● Azul: Sem atrasos
          </Text>
          <Text style={[styles2.legendItem, {color: '#4CAF50'}]}>
            ● Verde: entre 1 e 2 dias de atraso
          </Text>
          <Text style={[styles2.legendItem, {color: '#FFC107'}]}>
            ● Amarelo: entre 3 e 10 dias de atraso
          </Text>
          <Text style={[styles2.legendItem, {color: '#F34646'}]}>
            ● Vermelho: mais de 10 dias de atraso
          </Text>
        </View>

        {/* Gráfico de Anel (PieChart) */}
        <PieChart
          style={{height: 150, width: 200, marginBottom: 16}}
          data={pieData}
          innerRadius="70%"
          outerRadius="100%">
          <Labels />
        </PieChart>
      </View>
    );
  };

  const renderHomeData = ({item}) => {
    return (
      <TouchableOpacity
        style={localStyles.parentTrans}
        onPress={() => {
          cobrancaMap(item);
        }}>
        <View style={localStyles.oneBox}>
          <Image
            source={images.Deposit2}
            resizeMode="cover"
            style={[
              localStyles.GymImg,
              {backgroundColor: corSelect(item.atrasadas)},
            ]}
          />
          <View style={localStyles.mainCText}>
            <CText
              color={colors.black}
              type={'B16'}
              style={[localStyles.name, {maxWidth: 160}]}
              numberOfLines={1}
              ellipsizeMode="tail">
              {item.nome_completo}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>
              Emprestimo Número {item.emprestimo_id}
            </CText>
            {item.distance && (
              <CText type={'M12'} color={colors.tabColor}>
                {item?.distance?.toFixed(2)} Km de distancia
              </CText>
            )}

            {!item.distance && (
              <CText type={'M12'} color={colors.tabColor}>
                Endereço não informado
              </CText>
            )}
          </View>
        </View>

        <View>
          <CText type={'B16'} color={colors.red}>
            {formatCurrency(item.valor)}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={[styles2.mainContainerSurface]}>
      <ScrollView
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh} // Atualiza toda a tela
          />
        }
        showsVerticalScrollIndicator={false}>
        {ListHeaderComponent()}
        <FlatList
          keyExtractor={(item, index) => index.toString()}
          data={clientes}
          renderItem={renderHomeData}
          scrollEnabled={false} // FlatList é fixa dentro do ScrollView
        />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles2 = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    width: '100%',
    backgroundColor: '#1E1E1E',
    padding: 16,
  },
  title: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 20,
  },
  legend: {
    flexDirection: 'column',
    marginBottom: 16,
    width: '100%',
    paddingHorizontal: 20,
  },
  legendItem: {
    fontSize: 14,
    color: '#FFFFFF',
    marginVertical: 4,
  },
});

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: '#1E1E1E',
  },
  mainParent: {
    ...styles.mh20,
    ...styles.rowSpaceBetween,
  },
  WBTxt: {
    ...styles.mt20,
  },
  AnnaTxt: {
    ...styles.mt10,
  },
  parentNomeEmpresa: {
    ...styles.flexRow,
    top: moderateScale(-50),
    width: moderateScale(300),
  },
  NameEmpresa: {
    ...styles.mt10,
  },
  parent: {
    ...styles.flexRow,
  },
  card3Style: {
    width: moderateScale(327),
    height: moderateScale(190),
  },
  ParentImg: {
    ...styles.center,
    marginTop: moderateScale(30),
  },
  mainImg: {
    ...styles.rowSpaceAround,
    backgroundColor: colors.GreyScale,
    ...styles.mt30,
    ...styles.mh25,
    ...styles.p15,
    borderRadius: moderateScale(16),
  },
  childImg: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  parentDep: {
    ...styles.alignCenter,
  },
  Txt: {
    ...styles.pt10,
  },
  parentTodayTxt: {
    ...styles.mh25,
    ...styles.mv15,
    ...styles.rowSpaceBetween,
  },
  GymImg: {
    width: moderateScale(49),
    height: moderateScale(49),
  },
  parentTrans: {
    ...styles.mh25,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.pv15,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  oneBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  mainCText: {
    ...styles.pl20,
  },
  name: {
    ...styles.pv5,
  },
  parentTxtInp: {
    ...styles.flexRow,
    ...styles.justifyEnd,
  },
  miniContainer: {
    color: colors.black,
  },
  dropdownStyle: {
    backgroundColor: colors.GreyScale,
    height: getHeight(36),
    borderRadius: moderateScale(20),
    borderWidth: moderateScale(1),
    ...styles.ph15,
    width: moderateScale(250),
    ...styles.mv0,
  },
  CTxtInp: {
    ...styles.mv0,
  },
});
