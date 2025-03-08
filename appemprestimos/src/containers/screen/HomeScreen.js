import React, {useEffect, useState, useCallback, useMemo} from 'react';
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
import debounce from 'lodash.debounce';
import Geolocation from 'react-native-geolocation-service';
import {useFocusEffect, useRoute} from '@react-navigation/native';
import BackgroundTimer from 'react-native-background-timer';
import {PieChart} from 'react-native-svg-charts';
import {G, Text as SVGText} from 'react-native-svg';
import BackgroundGeolocation from "react-native-background-geolocation";
import {
  getAuthCompany,
  getUser,
  getPermissions,
} from '../../utils/asyncStorage';
import api from '../../services/api';
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CText from '../../components/common/CText';
import CTextInput from '../../components/common/CTextInput';
import ResumoFinanceiro from '../../components/ResumoFinanceiro';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';
import {Dropdown} from 'react-native-element-dropdown';
import {ListClient} from '../../api/constants';
import {getHeight, moderateScale} from '../../common/constant';

export default function HomeScreen({navigation}) {
  const [data, setData] = useState({
    verde: 0,
    amarelo: 0,
    vermelho: 0,
  });

  const [company, setCompany] = useState(null);
  const [user, setUser] = useState(null);
  const [clientes, setClientes] = useState([]);
  const [clientesOrig, setClientesOrig] = useState([]);
  const [location, setLocation] = useState(null);
  const [tipoCliente, setTipoCliente] = useState('');
  const [search, setSearch] = useState('');
  const [permissoesHoje, setPermissoesHoje] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [enabled, setEnabled] = useState(false);

  const [enviandoLocalizacao, setEnviandoLocalizacao] = useState(false);

  const [resumoFinanceiro, setResumoFinanceiro] = useState(null);

  const route = useRoute();

  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      requestLocationPermission();
    });

    return unsubscribe;
  }, [navigation, route.params?.onNavigateBack]);

  

  useEffect( async () => {
    console.log('entrou');

    const userReq = await getUser();
    let authCompany = await getAuthCompany();
    // 1.  Subscribe to events.
    const onLocation = BackgroundGeolocation.onLocation((location) => {
      console.log('[onLocation]', location);
      informarLocalizacao(location);
    })

    const onMotionChange = BackgroundGeolocation.onMotionChange((event) => {
      console.log('[onMotionChange]', event);
      informarLocalizacao(event);
    });

    const onActivityChange = BackgroundGeolocation.onActivityChange((event) => {
      console.log('[onActivityChange]', event);
    })

    const onProviderChange = BackgroundGeolocation.onProviderChange((event) => {
      console.log('[onProviderChange]', event);
    })

    /// 2. ready the plugin.
    BackgroundGeolocation.ready({
      // Geolocation Config
      desiredAccuracy: BackgroundGeolocation.DESIRED_ACCURACY_HIGH,
      distanceFilter: 5,
      // Activity Recognition
      stopTimeout: 5,
      // Application config
      debug: false, // <-- enable this hear sounds for background-geolocation life-cycle.
      logLevel: BackgroundGeolocation.LOG_LEVEL_VERBOSE,
      stopOnTerminate: true,   // <-- Allow the background-service to continue tracking when user closes the app.
      startOnBoot: true,        // <-- Auto start tracking when device is powered-up.
      // HTTP / SQLite config
      url: 'https://api.agecontrole.com.br/api/informar_localizacao_app',
      batchSync: false,       // <-- [Default: false] Set true to sync locations to server in a single HTTP request.
      autoSync: true,         // <-- [Default: true] Set true to sync each location to server as it arrives.
      params: {
        user_id: userReq.id,
        company_id: authCompany?.id
      }
    }).then((state) => {
      console.log("- BackgroundGeolocation is configured and ready: ", state.enabled);

      if (!state.enabled) {
         BackgroundGeolocation.start(() => {
            console.log("- Start success");
         });
      }
   }).catch((error) => {
      console.error("BackgroundGeolocation ready() failed: ", error);
   });

    return () => {
      console.log("Cleaning up BackgroundGeolocation events...");
      if (onLocation) onLocation.remove();
      if (onMotionChange) onMotionChange.remove();
      if (onActivityChange) onActivityChange.remove();
      if (onProviderChange) onProviderChange.remove();
   };
  }, []);

  // const requestLocationPermission = async () => {
  //   if (Platform.OS === 'ios') {
  //     Geolocation.requestAuthorization('whenInUse');
  //     getLocation();
  //   } else {
  //     const granted = await PermissionsAndroid.request(
  //       PermissionsAndroid.PERMISSIONS.ACCESS_BACKGROUND_LOCATION,
  //       {
  //         title: 'Permissão em background',
  //         message: 'We need access to your location',
  //         buttonNeutral: 'Ask Me Later',
  //         buttonNegative: 'Cancel',
  //         buttonPositive: 'OK',
  //       },
  //     );

  //     if (granted === PermissionsAndroid.RESULTS.GRANTED) {
  //     } else {
  //       console.log('Location permission denied');
  //     }
  //   }

  //   const granted2 = await PermissionsAndroid.request(
  //     PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
  //     {
  //       title: 'Location Access Permission',
  //       message: 'We need access to your location',
  //       buttonNeutral: 'Ask Me Later',
  //       buttonNegative: 'Cancel',
  //       buttonPositive: 'OK',
  //     },
  //   );

  //   if (granted2 === PermissionsAndroid.RESULTS.GRANTED) {
  //     getLocation();
  //   } else {
  //     console.log('Location permission denied');
  //   }

  //   if (!enviandoLocalizacao) {
  //     BackgroundTimer.runBackgroundTimer(() => {
  //       // Geolocation.getCurrentPosition(
  //       //   position => {
  //       //     console.log('position', position);
  //       //     informarLocalizacao(position);
  //       //   },
  //       //   error => {
  //       //     console.log(error.code, error.message);
  //       //   },
  //       //   {enableHighAccuracy: true, timeout: 15000, maximumAge: 10000},
  //       // );

  //       Geolocation.watchPosition(
  //         position => {
  //           informarLocalizacao(position);
  //         },
  //         error => console.log(error),
  //         {
  //           enableHighAccuracy: true,
  //           distanceFilter: 10, // Atualiza sempre que o usuário mover 1 metro
  //           interval: 5000, // Atualiza a cada 5 segundos
  //           fastestInterval: 2000, // Atualiza no menor intervalo possível
  //         },
  //       );
  //     }, 5000);
  //   }

  //   if (!enviandoLocalizacao) {
  //     setEnviandoLocalizacao(true);
  //   }
  // };

  const requestLocationPermission = async () => {
    if (Platform.OS === 'ios') {
      const auth = await Geolocation.requestAuthorization('whenInUse');
      if (auth === 'granted') {
        getLocation();
        startWatchingPosition();
      } else {
        console.log('Location permission denied on iOS');
      }
      return;
    }

    // Solicita primeiro a permissão de localização fina
    const grantedFine = await PermissionsAndroid.request(
      PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
      {
        title: 'Permissão de Localização',
        message:
          'Precisamos da sua localização para o app funcionar corretamente',
        buttonNeutral: 'Perguntar depois',
        buttonNegative: 'Cancelar',
        buttonPositive: 'OK',
      },
    );

    if (grantedFine !== PermissionsAndroid.RESULTS.GRANTED) {
      console.log('Permissão de localização negada');
      return;
    }

    // Após `ACCESS_FINE_LOCATION`, solicita `ACCESS_BACKGROUND_LOCATION`
    const grantedBackground = await PermissionsAndroid.request(
      PermissionsAndroid.PERMISSIONS.ACCESS_BACKGROUND_LOCATION,
      {
        title: 'Permissão de Localização em Background',
        message: 'Precisamos da sua localização mesmo com o app fechado',
        buttonNeutral: 'Perguntar depois',
        buttonNegative: 'Cancelar',
        buttonPositive: 'OK',
      },
    );

    if (grantedBackground !== PermissionsAndroid.RESULTS.GRANTED) {
      console.log('Permissão de localização em background negada');
    }

    getLocation();
    startWatchingPosition();
  };

  let watchId = null; // Variável para armazenar o ID do watchPosition

  const startWatchingPosition = () => {
    if (!watchId) {
      watchId = Geolocation.watchPosition(
        position => {
          // informarLocalizacao(position);
        },
        error => console.log(error),
        {
          enableHighAccuracy: true,
          distanceFilter: 5, // Atualiza apenas se o usuário se mover 10 metros
          interval: 5000, // Atualiza a cada 5 segundos
          fastestInterval: 2000, // Menor intervalo possível
        },
      );
    }
  };

  const stopWatchingPosition = () => {
    if (watchId !== null) {
      Geolocation.clearWatch(watchId);
      watchId = null;
    }
  };

  const havePermissionsFunction = permission => {
    if (permissoesHoje.includes(permission)) {
      return true;
    } else {
      return false;
    }
  };

  const getLocation = isReturnRoute => {
    Geolocation.getCurrentPosition(
      position => {
        setLocation(position);
        getInfo(position, isReturnRoute);
      },
      error => {
        console.log(error.code, error.message);
      },
      {enableHighAccuracy: true, timeout: 15000, maximumAge: 10000},
    );
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const fetchData = async () => {
    const permissions = await getPermissions();

    setPermissoesHoje(permissions);
  };

  useEffect(() => {
    fetchData();
    return () => {
      BackgroundTimer.stopBackgroundTimer();
      stopWatchingPosition();
    };
  }, []);

  useEffect(() => {
    const timeout = setTimeout(() => {
      if (search) {
        setClientes(
          clientesOrig.filter(item =>
            item.nome_completo.toLowerCase().includes(search.toLowerCase()),
          ),
        );
      } else {
        setClientes(clientesOrig);
      }
    }, 300);
    return () => clearTimeout(timeout);
  }, [search, clientesOrig]);

  useEffect(() => {
    if (!tipoCliente) return;
    console.log(tipoCliente);
    const filteredData = clientesOrig.filter(item => {
      if (tipoCliente.value === 1) return true;
      if (tipoCliente.value === 2) return item.atrasadas > 10;
      if (tipoCliente.value === 3)
        return item.atrasadas >= 3 && item.atrasadas <= 10;
      if (tipoCliente.value === 4)
        return item.atrasadas >= 1 && item.atrasadas <= 2;
      return item.atrasadas === 0;
    });
    setClientes(filteredData);
  }, [tipoCliente, clientesOrig]);

  const getInfo = async (position, isReturnRoute) => {
    const companyReq = await getAuthCompany();
    setCompany(companyReq);

    const userReq = await getUser();
    setUser(userReq);

    if (clientes.length == 0 || 1 == 1) {
      let reqClientes = await api.getClientesPendentes();

      reqClientes.forEach(item => {
        item.distance = haversineDistance(
          position.coords.latitude,
          position.coords.longitude,
          parseFloat(item.latitude),
          parseFloat(item.longitude),
        );
      });

      const sortedArray = reqClientes.sort((a, b) => a.distance - b.distance);

      setClientes(sortedArray);
      setClientesOrig(sortedArray);
    }

    if (!havePermissionsFunction('resumo_financeiro_aplicativo')) {
      const resumoFinanceiro = await api.getResumoFinanceiro();
      setResumoFinanceiro(resumoFinanceiro);
    }
  };

  const informarLocalizacao = async position => {
    const userReq = await getUser();

    let dados = {
      user_id: userReq.id,
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
    };

    await api.informarLocalizacao(dados);
  };

  const getInfoRoute = async position => {
    console.log('clientes entrou 3');
    const companyReq = await getAuthCompany();
    setCompany(companyReq);

    const userReq = await getUser();
    setUser(userReq);

    if (clientesOrig.length > 0) {
      console.log('clientes entrou 4');
      let reqClientes = await api.getClientesPendentes();

      reqClientes.forEach(item => {
        item.distance = haversineDistance(
          position.coords.latitude,
          position.coords.longitude,
          parseFloat(item.latitude),
          parseFloat(item.longitude),
        );
      });

      const sortedArray = reqClientes.sort((a, b) => a.distance - b.distance);

      setClientes(sortedArray);
      setClientesOrig(sortedArray);
    }

    console.log('finalizou');
  };

  const navigateTo = screen => navigation.navigate(screen);

  const formatCurrency = value => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  useEffect(() => {
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
  }, [clientes]);

  useEffect(() => {
    setFilterSearch();
  }, [search]);

  const debouncedSetSearch = useMemo(() => debounce(setSearch, 300), []);

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
    console.log('item', item);
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
    const pieData = [
      {key: 1, value: data.azul, svg: {fill: '#194ADFFF'}},
      {key: 2, value: data.verde, svg: {fill: '#4CAF50'}},
      {key: 3, value: data.amarelo, svg: {fill: '#FFC107'}},
      {key: 4, value: data.vermelho, svg: {fill: '#F34646'}},
    ].filter(item => item.value !== 0);

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
        <Text style={styles2.title}>Status de Atrasos 📆</Text>
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
      <FlatList
        ListHeaderComponent={
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
              </View>
            </View>
            <ChartExample parcelas={clientes} />

            {havePermissionsFunction('resumo_financeiro_aplicativo') && (
              <ResumoFinanceiro resumoFinanceiro={resumoFinanceiro} />
            )}
            <View style={localStyles.mainImg}>
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
            <View style={localStyles.parentTodayTxt}>
              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                value={search}
                onChangeText={t => setSearch(t)}
                text={'Pesquisar Cliente...'}
              />
            </View>
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
            </View>
            <View style={localStyles.parentTodayTxt}>
              <CText type={'B14'} color={colors.tabColor}>
                {dataAtual()}
              </CText>
            </View>
          </View>
        }
        keyExtractor={(item, index) => index.toString()}
        data={clientes}
        renderItem={renderHomeData}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      />
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
