import {
  StyleSheet,
  View,
  TouchableOpacity,
  Image,
  SafeAreaView,
  ScrollView,
  FlatList,
} from 'react-native';
import React, {useEffect, useState} from 'react';

import {useFocusEffect} from '@react-navigation/native';
// Local imports
import images from '../../assets/images/index';
import {styles} from '../../themes';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import {StackNav} from '../../navigation/navigationKeys';
import {HomeData} from '../../api/constants';
import CHeader from '../common/CHeader';

import {
  getAuthCompany,
  getUser,
  getPermissions,
} from '../../utils/asyncStorage';

export default function MoreOptions({navigation}) {
  const [permissoesHoje, setPermissoesHoje] = useState([]);

  useFocusEffect(
    React.useCallback(() => {
      async function fetchData() {
        let a = await getPermissions();
        setPermissoesHoje(a);
      }
      fetchData();
      return () => {};
    }, []),
  );

  const moveToEmprestimos = () => {
    navigation.navigate(StackNav.Clientes);
  };

  const moveToTrans = () => {
    navigation.navigate(StackNav.TransferMoney);
  };

  const moveToDeposit = () => {
    navigation.navigate(StackNav.TopUpScreen);
  };

  const moveToAprovacao = () => {
    navigation.navigate(StackNav.Aprovacao);
  };

  const moveToWith = () => {
    navigation.navigate(StackNav.WithDrawBalance);
  };

  const moveToHelp = () => {
    navigation.navigate(StackNav.ChatScreen);
  };

  const moveToAtm = () => {
    navigation.navigate(StackNav.FechamentoCaixaScreen);
  };

  const moveToConfiguracoesCaixa = () => {
    navigation.navigate(StackNav.ConfiguracoesCaixaScreen);
  };

  const moveToSacarCaixa = () => {
    navigation.navigate(StackNav.SacarCaixaScreen);
  };

  const moveToDepositarCaixa = () => {
    navigation.navigate(StackNav.DepositarCaixaScreen);
  };

  const moveToMobile = () => {
    navigation.navigate(StackNav.SelectProvider);
  };

  const renderItems = ({item}) => {
    return (
      <TouchableOpacity style={localStyles.parentTrans}>
        <View style={localStyles.oneBox}>
          <Image
            source={item.image}
            resizeMode="cover"
            style={localStyles.GymImg}
          />
          <View style={localStyles.mainCText}>
            <CText color={colors.black} type={'B16'} style={localStyles.name}>
              {item.name}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>
              {item.subName}
            </CText>
          </View>
        </View>

        <View>
          <CText type={'B16'} color={item.color}>
            {item.dollars}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  const FirstImage = ({image, text, onPress, style}) => {
    return (
      <TouchableOpacity style={style} onPress={onPress}>
        <Image source={image} style={localStyles.childImg} />
        <CText type={'M12'} color={colors.black} style={localStyles.Txt}>
          {text}
        </CText>
      </TouchableOpacity>
    );
  };

  const baixaMap = item => {
    navigation.navigate(StackNav.BaixaMap, {
      clientes: [],
    });
  };

  const havePermissionsFunction = permission => {
    if (permissoesHoje.includes(permission)) {
      return true;
    } else {
      return false;
    }
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <ScrollView showsVerticalScrollIndicator={false} style={styles.mh20}>
        <CHeader />
        <View style={localStyles.mainImg}>
          <View style={localStyles.menuRowStyle}>
            {havePermissionsFunction(
              'view_emprestimos_autorizar_pagamentos',
            ) && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Aprovação"
                onPress={moveToAprovacao}
              />
            )}

            {havePermissionsFunction('aplicativo_baixas') && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Baixas"
                onPress={baixaMap}
              />
            )}
            <FirstImage
              style={localStyles.parentDep}
              image={images.Deposit}
              text="Emprestimo"
              onPress={moveToEmprestimos}
            />
          </View>
          <View style={localStyles.menuRowStyle}>
            {havePermissionsFunction('view_encerrarfechamentocaixa') && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Fechamento de caixa"
                onPress={moveToAtm}
              />
            )}

            {havePermissionsFunction('view_alterarfechamentocaixa') && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Alterar Caixa"
                onPress={moveToConfiguracoesCaixa}
              />
            )}
          </View>
          <View style={localStyles.menuRowStyle}>
            {havePermissionsFunction('view_sacarfechamentocaixa') && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Realizar Saque"
                onPress={moveToSacarCaixa}
              />
            )}

            {havePermissionsFunction('view_depositarfechamentocaixa') && (
              <FirstImage
                style={localStyles.parentDep}
                image={images.Deposit}
                text="Depositar na Wallet"
                onPress={moveToDepositarCaixa}
              />
            )}

            {/* <FirstImage
              style={localStyles.parentDep2}
              image={images.Deposit}
              text="Outros"
              onPress={moveToMobile}
            />s
            <FirstImage
              style={localStyles.parentDep2}
              image={images.Transfer}
              text={strings.Help}
              onPress={moveToHelp}
            /> */}
          </View>
        </View>

        {/* <View style={localStyles.outerComponent}>
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={[...HomeData, ...HomeData, ...HomeData]}
            renderItem={renderItems}
          />
        </View> */}
      </ScrollView>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  parentDep: {
    ...styles.alignCenter,
  },
  childImg: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  Txt: {
    ...styles.pt10,
  },
  mainImg: {
    ...styles.justifyCenter,
    backgroundColor: colors.GreyScale,
    ...styles.mt30,
    ...styles.ph30,
    ...styles.pv20,
    gap: moderateScale(20),
    borderRadius: moderateScale(16),
  },
  parentDep2: {
    ...styles.alignCenter,
    ...styles.mr10,
  },
  menuRowStyle: {
    ...styles.rowCenter,
    gap: moderateScale(30),
  },
  menuRowStyle2: {
    ...styles.flexRow,
    ...styles.ph5,
    ...styles.justifyBetween,
  },
  parentTrans: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.pv20,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  oneBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  GymImg: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  mainCText: {
    ...styles.pl20,
  },
  name: {
    ...styles.pv5,
  },
  outerComponent: {
    ...styles.mt25,
  },
});
