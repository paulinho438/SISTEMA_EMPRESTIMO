import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  Linking,
  Alert,
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
import EmprestimosPendentesHoje from '../../../components/modals/EmprestimosPendentesHoje';
import ParcelasExtorno from '../../../components/modals/ParcelasExtorno';
import {useFocusEffect} from '@react-navigation/native';
import api from '../../../services/api';

import {StackNav, TabNav} from '../../../navigation/navigationKeys';

export default function Aprovacao({navigation, route}) {
  // const { clientes } = route.params;

  const [parcelas, setParcelas] = useState([]);
  const [loading, setLoading] = useState(false);

  const [parcelasPendentes, setParcelasPendentes] = useState([]);

  useFocusEffect(
    React.useCallback(() => {
      getPendentesParaHoje();
    }, []),
  );

  const Info = useRef(null);

  const moveToInfoModel = () => {
    if (parcelasPendentes.length == 0) {
      return Alert.alert('Não existem empréstimos para aprovação');
    }
    Info.current.show();
  };

  const backToMore = () => {
    navigation.navigate(TabNav.HomeScreen);
  };

  const getPendentesParaHoje = async () => {
    setLoading(true);
    let req = await api.emprestimosPendentesParaHoje();
    setParcelasPendentes(req.data);
    setLoading(false);
    
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
              Aprovação
            </CText>
          </View>
        </View>

        <View>
          <Image source={images.Map} style={localStyles.imgSty} />

          <View style={localStyles.outerComponent}>
            <CButton
              onPress={moveToInfoModel}
              disabled={loading}
              text={loading ? 'Carregando...' : 'Empréstimos Pendentes'}
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

        <EmprestimosPendentesHoje
          sheetRef={Info}
          parcelas={parcelas}
          clientes={{}}
          titulosPendentes={parcelasPendentes}
          onAtualizarClientes={getPendentesParaHoje}
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
    backgroundColor: colors.black,
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
