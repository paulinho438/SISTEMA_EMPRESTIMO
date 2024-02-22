import {StyleSheet, View, SafeAreaView, TouchableOpacity} from 'react-native';
import React, {useRef, useState} from 'react';

// Local imports
import CBackButton from '../../components/common/CBackButton';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {Card, DigDoc, Passport} from '../../assets/svgs';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import Countries from '../../components/modals/Countries';
import Material from 'react-native-vector-icons/MaterialIcons';
import {AuthNav} from '../../navigation/navigationKeys';
import {US} from '../../assets/svgs';

export default function ProofRes({navigation}) {
  const Change = useRef(null);
  const [country, SetCountry] = useState('');

  const MethodData = ({name, icon}) => {
    return (
      <TouchableOpacity style={localStyles.parentPass} onPress={moveToCard}>
        <View style={localStyles.parentPassAndTxt}>
          {icon}
          <CText color={colors.black} type={'B16'} style={localStyles.PassTxt}>
            {name}
          </CText>
        </View>
        <Material
          style={localStyles.iconSty}
          color={colors.black}
          name={'navigate-next'}
          size={25}
        />
      </TouchableOpacity>
    );
  };

  const selectedCountry = itm => {
    SetCountry(itm);
  };

  const showCountry = () => {
    Change.current?.show();
  };

  const moveToCard = () => {
    navigation.navigate(AuthNav.CardOnBoarding);
  };

  const backToFace = () => {
    navigation.navigate(AuthNav.FaceIdentity);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerMainContainer}>
        <View>
          <CBackButton onPress={backToFace} />
          <CText
            color={colors.black}
            type={'B24'}
            style={localStyles.ProofResTxt}>
            {strings.ProofOfRes}
          </CText>
          <CText color={colors.black}>{strings.ProveLive}</CText>

          <View style={localStyles.mainNation}>
            <CText type={'B18'} color={colors.tabColor}>
              {strings.Nationality}
            </CText>

            <View style={localStyles.mainBox}>
              {!!country ? (
                <View type={'B18'} style={localStyles.USTxtStyle}>
                  {country?.svgIcon}
                  <CText color={colors.black} type={'B16'}>
                    {country?.FullName}
                  </CText>
                </View>
              ) : (
                <View style={localStyles.ViewOfFlag}>
                  <US />
                  <CText
                    color={colors.black}
                    type={'B18'}
                    style={localStyles.USTxtStyle}>
                    {strings.America}
                  </CText>
                </View>
              )}

              <TouchableOpacity
                style={localStyles.mainChange}
                onPress={showCountry}>
                <CText color={colors.SignUpTxt} type={'B16'}>
                  {strings.Change}
                </CText>
              </TouchableOpacity>
            </View>
          </View>
        </View>

        <View style={localStyles.mainMethod}>
          <CText
            type={'B18'}
            color={colors.tabColor}
            style={localStyles.MethodTxt}>
            {strings.MethodVer}
          </CText>
          <View style={localStyles.mainBoxes}>
            <MethodData name={strings.PassPort} icon={<Passport />} />
            <MethodData name={strings.IdeCard} icon={<Card />} />
            <MethodData name={strings.DigDoc} icon={<DigDoc />} />
          </View>
        </View>

        <Countries sheetRef={Change} selectedCountry={selectedCountry} />
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  ProofResTxt: {
    ...styles.mv20,
  },
  mainBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt10,
    ...styles.pl20,
    backgroundColor: colors.GreyScale,
    height: moderateScale(56),
    borderRadius: moderateScale(16),
  },
  USTxtStyle: {
    ...styles.rowCenter,
    gap: moderateScale(10),
  },
  mainChange: {
    ...styles.pr15,
  },
  ViewOfFlag: {
    ...styles.flexRow,
    gap: moderateScale(10),
  },
  mainNation: {
    ...styles.mv50,
  },
  mainBoxes: {
    ...styles.mv10,
    borderRadius: moderateScale(16),
    borderColor: colors.bottomBorder,
    borderWidth: moderateScale(1),
    backgroundColor: colors.white,
  },
  parentPass: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.pv25,
    borderRadius: moderateScale(16),
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  parentPassAndTxt: {
    ...styles.rowCenter,
    ...styles.pl10,
  },
  PassTxt: {
    ...styles.pl10,
  },
  mainMethod: {
    ...styles.mb180,
  },
  iconSty: {
    ...styles.pr15,
  },
  outerMainContainer: {
    ...styles.ph20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
});
