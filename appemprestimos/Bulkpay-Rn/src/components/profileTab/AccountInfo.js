import {SafeAreaView, StyleSheet, Image, View, Switch} from 'react-native';
import React, {useState} from 'react';

// Local imports
import CText from '../common/CText';
import strings from '../../i18n/strings';
import CHeader from '../common/CHeader';
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';

export default function AccountInfo({navigation}) {
  const [close, open] = useState(true);

  const moveToEdit = () => {
    navigation.navigate(StackNav.EditAccount);
  };

  const ProfileDetails = ({question, answer}) => {
    return (
      <View style={localStyles.parentContainer}>
        <CText type={'M14'} color={colors.tabColor}>
          {question}
        </CText>
        <CText color={colors.black} type={'M14'}>
          {answer}
        </CText>
      </View>
    );
  };

  const onValueChangeToggle = () => {
    open(!close);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={styles.mh20}>
        <CHeader color={colors.black} title={'Account Info'} />
        <Image source={images.EditPhoto} style={localStyles.imgSty} />
        <CText type={'B16'} color={colors.tabColor}>
          {strings.PersonalInfo}
        </CText>

        <View style={localStyles.parentComponent}>
          <ProfileDetails
            question={strings.YourName}
            answer={strings.Anna}
            color={colors.tabColor}
          />

          <ProfileDetails
            question={strings.Occupation}
            answer={strings.Students}
            color={colors.tabColor}
          />

          <ProfileDetails
            question={strings.Employer}
            answer={strings.OverlayDesign}
            color={colors.tabColor}
          />

          <View style={localStyles.parentContainer}>
            <CText type={'M14'} color={colors.tabColor}>
              {strings.USCitizen}
            </CText>
            <Switch
              trackColor={{false: colors.bottomBorder, true: colors.Toggle}}
              value={close}
              onValueChange={onValueChangeToggle}
            />
          </View>
        </View>

        <CText type={'B16'} color={colors.tabColor}>
          {strings.ContactInfo}
        </CText>

        <View style={localStyles.parentComponent}>
          <ProfileDetails
            question={strings.PhoneNumber}
            answer={strings.ProfileNumber}
            color={colors.tabColor}
          />

          <ProfileDetails
            question={strings.Email}
            answer={strings.AnnaEmail}
            color={colors.tabColor}
          />
        </View>

        <CButton
          onPress={moveToEdit}
          text={'Edit'}
          ChildLoginBtn={localStyles.childCompo}
          containerStyle={localStyles.CButton}
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
  imgSty: {
    width: moderateScale(200),
    height: moderateScale(140),
    ...styles.selfCenter,
  },
  parentContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.mv20,
  },
  parentComponent: {
    ...styles.mv15,
    ...styles.ph15,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.bottomBorder,
  },
  CButton: {
    ...styles.mt0,
    backgroundColor: colors.bottomBorder,
  },
  childCompo: {
    color: colors.black,
  },
});
