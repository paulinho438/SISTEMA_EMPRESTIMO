import {StyleSheet, SafeAreaView, View, TouchableOpacity} from 'react-native';
import React, {useRef, useState} from 'react';

// Local imports
import {styles} from '../../themes';
import CBackButton from '../../components/common/CBackButton';
import CText from '../../components/common/CText';
import {colors} from '../../themes/colors';
import Countries from '../../components/modals/Countries';
import strings from '../../i18n/strings';
import {moderateScale} from '../../common/constant';
import Feathers from 'react-native-vector-icons/FontAwesome';
import {US} from '../../assets/svgs';
import CButton from '../../components/common/CButton';
import {AuthNav} from '../../navigation/navigationKeys';

export default function CountryRes({navigation}) {
  const Choose = useRef(null);
  const [country, SetCountry] = useState('');

  const selectedCountry = itm => {
    SetCountry(itm);
  };

  const moveToModel = () => {
    Choose.current?.show();
  };

  const backToSignUp = () => {
    navigation.navigate(AuthNav.SignUp);
  };

  const moveToReasons = () => {
    navigation.navigate(AuthNav.Reasons);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerMainContainer}>
        <View>
          <CBackButton onPress={backToSignUp} />
          <CText
            color={colors.black}
            style={localStyles.CountryResTxt}
            type={'B24'}>
            {strings.CountryOfRes}
          </CText>
          <CText color={colors.black} style={localStyles.selectCountryTxt}>
            {strings.SelectCountry}
          </CText>

          <TouchableOpacity onPress={moveToModel} style={localStyles.mainBox}>
            <View style={localStyles.UsStyle}>
              {!!country ? (
                <View style={localStyles.USTxt}>
                  {country?.svgIcon}

                  <CText color={colors.black} type={'B18'}>
                    {country?.FullName}
                  </CText>
                </View>
              ) : (
                <View style={localStyles.ViewOfInitial}>
                  <US />
                  <CText
                    color={colors.black}
                    type={'B18'}
                    style={localStyles.USTxtStyle}>
                    {strings.America}
                  </CText>
                </View>
              )}
            </View>
            <Feathers
              color={colors.black}
              name={'angle-down'}
              style={localStyles.angleButton}
              size={24}
            />
          </TouchableOpacity>

          <Countries sheetRef={Choose} selectedCountry={selectedCountry} />
        </View>

        <CButton
          ParentLoginBtn={localStyles.ParentCButton}
          onPress={moveToReasons}
        />
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  CountryResTxt: {
    ...styles.mt30,
  },
  selectCountryTxt: {
    ...styles.mt15,
  },
  parentButton: {
    backgroundColor: colors.GreyScale,
  },
  ChildButton: {
    color: colors.black,
  },
  mainBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt40,
    ...styles.pl20,
    backgroundColor: colors.GreyScale,
    height: moderateScale(56),
    borderRadius: moderateScale(16),
  },
  USTxt: {
    ...styles.rowCenter,
    gap: moderateScale(10),
  },
  UsStyle: {
    ...styles.flexRow,
    ...styles.center,
  },
  angleButton: {
    ...styles.pr10,
  },
  ViewOfInitial: {
    ...styles.rowCenter,
  },
  USTxtStyle: {
    ...styles.pl15,
  },
  parentMain: {
    ...styles.flex,
    ...styles.justifyBetween,
  },
  ParentCButton: {
    ...styles.mb30,
  },
  outerMainContainer: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
});
