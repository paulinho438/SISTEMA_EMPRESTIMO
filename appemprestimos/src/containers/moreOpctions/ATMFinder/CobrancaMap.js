import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
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
import {StackNav, TabNav} from '../../../navigation/navigationKeys';

export default function ATMDetails({navigation, route}) {
  const { clientes } = route.params;
  
  const [empty, nonEmpty] = useState('');

  const Search = useRef(null);

  const onPress = () => {
    nonEmpty('');
  };

  const moveToModel = () => {
    Search.current.show();
  };

  const backToMore = () => {
    navigation.navigate(TabNav.HomeScreen);
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
              Cobrança
            </CText>
          </View>
        </View>

        <View>
          <Image source={images.Map} style={localStyles.imgSty} />

          <View style={localStyles.outerComponent}>
            <View style={localStyles.outerContainer}>
              <Image style={localStyles.iconSty} source={images.Boy} />

              <View style={{gap: moderateScale(4)}}>
                <CText color={colors.black} type={'B16'}>
                  {clientes.nome_cliente}
                </CText>
                <CText color={colors.black} type={'M12'}>
                  {clientes.endereco}
                </CText>
              </View>
            </View>

            <CButton
              onPress={moveToModel}
              text={'Mais Informação'}
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
        </View>

        {/* <View style={localStyles.mainContainer}>
          <CTextInput
            value={empty}
            onChangeText={nonEmpty}
            LeftIcon={() => (
              <Ionicons
                color={colors.black}
                name={'search-outline'}
                size={24}
              />
            )}
            RightIcon={() => (
              <TouchableOpacity onPress={onPress}>
                <AntDesign name={'close'} size={24} color={colors.SignUpTxt} />
              </TouchableOpacity>
            )}
            text={'Enter the name of ATM'}
            mainTxtInp={localStyles.TxtInpSty}
          />
        </View> */}

        <Location sheetRef={Search} cliente={clientes} />
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
