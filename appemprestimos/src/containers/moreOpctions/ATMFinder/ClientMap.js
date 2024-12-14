import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  Linking,
  Platform,
  PermissionsAndroid,
  Alert
} from 'react-native';
import React, {useEffect, useRef, useState} from 'react';
import MapView, { Marker, PROVIDER_GOOGLE } from 'react-native-maps';
import Material from 'react-native-vector-icons/MaterialIcons';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';
import Ionicons from 'react-native-vector-icons/Ionicons';
import AntDesign from 'react-native-vector-icons/AntDesign';

import Geolocation from 'react-native-geolocation-service';

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
import FinCadCliente from '../../../components/modals/FinCadCliente';
import { useFocusEffect } from '@react-navigation/native';
import api from '../../../services/api';

import {StackNav, TabNav} from '../../../navigation/navigationKeys';
import margin from '../../../themes/margin';

export default function ATMDetails({navigation, route}) {
  const { clientes } = route.params;

  const [empty, nonEmpty] = useState('');
  const [location, setLocation] = useState(null);
  const [endereco, setEndereco] = useState({
    "create" : true,
    "cep" : "",
    "address" : "",
    "neighborhood" : "",
    "city" : "",
    "number" : "",
    "complement" : "",
    "latitude" : "",
    "longitude" : "",
    "description" : ""
  });
  
  const [parcelas, setParcelas] = useState([]);
  const [search, setSearch] = useState('Clique no mapa para selecionar.');
  useFocusEffect(
    React.useCallback(() => {
      getInfo();
    }, [])
  );

  useEffect(()=>{
    getSearch();
  },[location]);

  useFocusEffect(
    React.useCallback(() => {

      const requestLocationPermission = async () => {
        if (Platform.OS === 'ios') {
          Geolocation.requestAuthorization('whenInUse');
          getLocation();
        } else {
          const granted = await PermissionsAndroid.request(
            PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
            {
              title: "Location Access Permission",
              message: "We need access to your location",
              buttonNeutral: "Ask Me Later",
              buttonNegative: "Cancel",
              buttonPositive: "OK"
            }
          );
  
          if (granted === PermissionsAndroid.RESULTS.GRANTED) {

            getLocation();
          } else {
            console.log("Location permission denied");
          }

        }
      };
  
      const getLocation = async () => {

        Geolocation.getCurrentPosition(
          (position) => {
            setLocation(position);

          },
          (error) => {
            console.log(error.code, error.message);
          },
          { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
        );

      };
  
      requestLocationPermission();


      
      return () => {
      };
    }, [])
  );

  const getSearch = async () => {
    let r = await api.getLocationGeocode(location.coords.latitude, location.coords.longitude);
    if(r?.results[0]?.formatted_address){
        const addressComponents = r.results[0].address_components;
        const cep = addressComponents.find(component =>
          component.types.includes('postal_code')
        );

        if(cep?.long_name){
          let r = await api.getEnderecoLatLong(cep?.long_name);
          setEndereco({
            "create" : true,
            "cep" : cep.long_name,
            "address" : r.logradouro,
            "neighborhood" : r.bairro,
            "city" : r.localidade,
            "number" : "999",
            "complement" : "",
            "latitude" : lat,
            "longitude" : long,
            "description" : "Loc"
          });
        }
      setSearch(r.results[0].formatted_address)

    }
  }

  const getInfo =  async (position) => {

    let reqClientes = await api.getParcelasInfoEmprestimo(clientes.id);
    setParcelas(reqClientes)

  }

  const Search = useRef(null);
  const Info = useRef(null);

  const moveToInfoModel = () => {
    if(search == 'Clique no mapa para selecionar.'){
      Alert.alert('Clique no mapa para selecionar o endereço');
      return false;
    }
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

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(clientes.id, obterDataAtual());

    Alert.alert('Cobranca alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  }

  const openWhatsApp = () => {

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

  const handleAlterSearchMapsClick = async (lat, long) => {
    let r = await api.getLocationGeocode(lat, long);
    if(r.status == "OK"){
        
        const addressComponents = r.results[0].address_components;
        const cep = addressComponents.find(component =>
          component.types.includes('postal_code')
        );

        if(cep?.long_name){
          let r = await api.getEnderecoLatLong(cep?.long_name);
          setEndereco({
            "create" : true,
            "cep" : cep?.long_name,
            "address" : r.logradouro,
            "neighborhood" : r.bairro,
            "city" : r.localidade,
            "number" : "999",
            "complement" : "",
            "latitude" : lat,
            "longitude" : long,
            "description" : "Loc"
          });
        }


        setSearch(r.results[0].formatted_address)
    }

    setLocation({
      ...location,
      coords : {
          latitude: lat, 
          longitude: long,
      },
    })

}

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
              Localizaçāo
            </CText>
          </View>
        </View>

        <View>
          {location?.coords?.latitude && 
          <MapView
            provider={PROVIDER_GOOGLE}
            style={localStyles.imgSty}
            onPress={(e) => {
              handleAlterSearchMapsClick(e.nativeEvent.coordinate.latitude, e.nativeEvent.coordinate.longitude);
          }}
            region={{
              latitude: location.coords.latitude,
              longitude: location.coords.longitude,
              latitudeDelta: 0.015,
              longitudeDelta: 0.0121,
            }}
          >
            <Marker
              pinColor={'red'} 
              coordinate={{latitude: location.coords.latitude, longitude: location.coords.longitude}}
            />
          </MapView>
        }
         

        </View>

        <View style={localStyles.mainContainer}>
          <CTextInput
            value={search}
            onChangeText={nonEmpty}
            text={'Enter the name of ATM'}
            mainTxtInp={localStyles.TxtInpSty}
          />
          <CButton
            onPress={moveToInfoModel}
            text={'Avancar'}
            containerStyle={localStyles.buttonContainer}
            RightIcon={() => (
                <Community
                  size={24}
                  name={'arrow-u-right-top'}
                  color={colors.white}
                />
              )}
            />
        </View>
        <FinCadCliente sheetRef={Info} parcelas={[]} clientes={clientes} localizacao={endereco} />
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
    height: moderateScale(440),
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
    marginTop: moderateScale(10),
    width: moderateScale(325),
  },
});
