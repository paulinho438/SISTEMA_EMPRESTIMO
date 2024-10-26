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

import {getAuthCompany, getUser} from '../../utils/asyncStorage';
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

  useFocusEffect(
    React.useCallback(() => {
      setFilterSearch();
    }, [search]),
  );

  useFocusEffect(
    React.useCallback(() => {
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
      const filteredData = clientesOrig.filter(item => item.atrasadas > 5);

      setClientes(filteredData);
    } else if (tipoCliente.value == 3) {
      const filteredData = clientesOrig.filter(
        item => item.atrasadas > 1 && item.atrasadas <= 5,
      );

      setClientes(filteredData);
    } else {
      const filteredData = clientesOrig.filter(item => item.atrasadas == 1);

      setClientes(filteredData);
    }

    //console.log(clientes)
  }, [tipoCliente]);

  const setFilterSearch = () => {
    if (search) {
      const newData = clientesOrig.filter(item => {
        return (
          item.nome_cliente
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

    reqClientes.data.forEach(item => {
      item.distance = haversineDistance(
        position.coords.latitude,
        position.coords.longitude,
        parseFloat(item.latitude),
        parseFloat(item.longitude),
      );
    });

    const sortedArray = reqClientes.data.sort(
      (a, b) => a.distance - b.distance,
    );

    setClientes(sortedArray);
    setClientesOrig(sortedArray);
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
            </View>

            <CNotification onPress={moveToNot} />
          </View>

          <View style={localStyles.ParentImg}>
            <Image source={images.cardBalance} style={localStyles.card3Style} />
            <View style={localStyles.parentNomeEmpresa}>
              <CText
                color={colors.white}
                type={'B18'}
                style={localStyles.NameEmpresa}>
                {company?.company}
              </CText>
            </View>
          </View>
        </View>

        <View style={localStyles.mainImg}>
          {/* <FirstImage 
            image={images.Deposit}
            text='Clientes'
            onPress={moveToDeposit}
          /> */}
          {/* <FirstImage
            image={images.Transfer}
            text='Pendentes'
            onPress={moveToTrans}
          /> */}
          <FirstImage
            image={images.Withdraw}
            text="Emprestimo"
            onPress={moveToWith}
          />
          {/* <FirstImage
            image={images.More}
            text={strings.More}
            onPress={moveToOpt}
          /> */}
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
      return '#32a83c';
    } else if (at == 1) {
      return '#17a2b8';
    } else if (at > 1 && at <= 5) {
      return '#dae32d';
    } else if (at > 5) {
      return '#dc3545';
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
              {item.nome_cliente}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>
              Emprestimo Número {item.emprestimo_id}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>
              {item.distance.toFixed(2)} Km de distancia
            </CText>
          </View>
        </View>

        <View>
          <CText type={'B16'} color={colors.red}>
            {item.total_pendente}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={[styles.mainContainerSurface]}>
      <ScrollView showsVerticalScrollIndicator={false}>
        {ListHeaderComponent()}
        <FlatList
          keyExtractor={(item, index) => index.toString()}
          data={clientes}
          renderItem={renderHomeData}
          scrollEnabled={false}
          showsVerticalScrollIndicator={false}
        />
      </ScrollView>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.Primary,
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
    top: moderateScale(63),
  },
  mainImg: {
    ...styles.rowSpaceAround,
    backgroundColor: colors.GreyScale,
    ...styles.mt90,
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
