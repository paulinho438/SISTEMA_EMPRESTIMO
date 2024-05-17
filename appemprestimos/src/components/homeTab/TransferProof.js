import {StyleSheet, View, Image} from 'react-native';
import React from 'react';

// Local imports
import {SafeAreaView} from 'react-native-safe-area-context';
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import CHeader from '../common/CHeader';

export default function TransferProof({route, navigation}) {
  const {amount, valores} = route.params;

  const backToHome = () => {
    navigation.navigate(StackNav.TabNavigation);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View>
        <CHeader color={colors.black} />
        <View style={localStyles.parentImg}>
          <Image
            source={images.Congratulation}
            style={localStyles.congratsImg}
          />
        </View>
        <CText
          color={colors.black}
          style={localStyles.successTxt}
          type={'B24'}
          align={'center'}>
          Empréstimo em aprovação
        </CText>
        <CText
          color={colors.black}
          style={localStyles.NoticeTxt}
          type={'R14'}
          align={'center'}>
          {strings.SuccessNotice}
        </CText>
        <View style={localStyles.parentAmount}>
          <CText
            color={colors.black}
            align={'center'}
            type={'B32'}
            style={localStyles.amountTxt}>
            {valores?.valor}
          </CText>
        </View>
      </View>
      <CButton
        text={'Voltar para o inicio'}
        containerStyle={localStyles.cButton}
        onPress={backToHome}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
    backgroundColor: colors.white,
  },
  congratsImg: {
    width: moderateScale(258),
    height: moderateScale(218),
  },
  parentImg: {
    ...styles.alignCenter,
  },
  successTxt: {
    ...styles.mt50,
  },
  NoticeTxt: {
    ...styles.mv20,
  },
  amountTxt: {
    ...styles.ph24,
    ...styles.pv16,
  },
  parentAmount: {
    ...styles.mt40,
    backgroundColor: colors.GreyScale,
    ...styles.selfCenter,
    borderRadius: moderateScale(16),
  },
  cButton: {
    ...styles.mb25,
  },
});
