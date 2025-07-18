import {StyleSheet, View} from 'react-native';
import React from 'react';

// Local imports
import CText from '../common/CText';
import strings from '../../i18n/strings';
import {styles} from '../../themes/index';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {StackNav, TabNav} from '../../navigation/navigationKeys';
import {useNavigation} from '@react-navigation/native';

import {authToken, authCompany, user, permissions, removeAuthToken, removeTipoCliente} from '../../utils/asyncStorage';

export default function LogOut() {
  const navigation = useNavigation();

  const moveToSignIn = async () => {

    await removeAuthToken();

    await removeTipoCliente();

    navigation.reset({
      index: 0,
      routes: [{name: StackNav.AuthNavigation}],
    });
  };

  const moveToProfile = () => {
    navigation.goBack()
  };

  return (
    <View style={localStyles.mainContainer}>
      <CText align={'center'} type={'B24'} color={colors.black}>
        Você confirma que deseja sair do AGE CONTROLE ?
      </CText>

      <View style={localStyles.outerComponentOfCButtons}>
        <CButton
          onPress={moveToSignIn}
          text={'Sim'}
          containerStyle={localStyles.ParentCButtonContainerYes}
        />
        <CButton
          onPress={moveToProfile}
          text={'Não'}
          containerStyle={localStyles.ParentCButtonContainerNo}
        />
      </View>
    </View>
  );
}

const localStyles = StyleSheet.create({
  mainContainer: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyCenter,
  },
  ParentCButtonContainerYes: {
    backgroundColor: colors.Green,
    width: '60%',
  },
  ParentCButtonContainerNo: {
    backgroundColor: colors.red,
    width: '60%',
  },
  outerComponentOfCButtons: {
    ...styles.flexRow,
    ...styles.justifyCenter,
  },
});
