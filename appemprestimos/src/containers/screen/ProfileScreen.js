import {
  SafeAreaView,
  StyleSheet,
  Image,
  View,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import React, {useEffect, useState} from 'react';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import {colors} from '../../themes/colors';
import strings from '../../i18n/strings';
import CHeader from '../../components/common/CHeader';
import Material from 'react-native-vector-icons/MaterialIcons';
import {StackNav} from '../../navigation/navigationKeys';
import {useFocusEffect} from '@react-navigation/native';

import {
  getAuthCompany,
  getUser,
  getPermissions,
} from '../../utils/asyncStorage';
import margin from '../../themes/margin';

export default function ProfileScreen({navigation}) {
  const [user, setUser] = useState(null);

  useFocusEffect(
    React.useCallback(() => {
      getInfo();
      return () => {};
    }, [])
  );

  const getInfo = async position => {
    let userReq = await getUser();
    setUser(userReq);
  };

  const moveToAcc = () => {
    navigation.navigate(StackNav.AccountInfo);
  };

  const moveToLang = () => {
    navigation.navigate(StackNav.SelectLanguage);
  };

  const moveToGs = () => {
    navigation.navigate(StackNav.GeneralSetting);
  };

  const moveToRefer = () => {
    navigation.navigate(StackNav.ReferralCode);
  };

  const moveToContact = () => {
    navigation.navigate(StackNav.ContactsList);
  };

  const moveToFQA = () => {
    navigation.navigate(StackNav.FQA);
  };

  const moveToLogOut = () => {
    navigation.navigate(StackNav.LogOut);
  };

  const RenderData = ({image, name, onPress}) => {
    return (
      <TouchableOpacity style={localStyles.outerContainer} onPress={onPress}>
        <View style={localStyles.parentCompo}>
          <Image source={image} style={localStyles.iconSty} />
          <CText color={colors.black} type={'M14'}>
            {name}
          </CText>
        </View>
        <Material color={colors.black} name={'navigate-next'} size={16} />
      </TouchableOpacity>
    );
  };

  const RenderHeaderComponent = () => {
    return (
      <View>
        <Image source={images.AGELOGO} style={localStyles.imgSty} />

        <View style={localStyles.outerComponent}>
          <CText color={colors.black} >
            {user?.nome_completo}
          </CText>
          <CText type={'R12'} color={colors.tabColor}>
          {user?.email}
          </CText>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <ScrollView
        style={styles.mh20}
        showsVerticalScrollIndicator={false}
        bounces={false}>
        <CHeader color={colors.black} title={'Profile'} />
        <RenderHeaderComponent />

        <View style={styles.mv20}>
          {/* <RenderData
            name={strings.RC}
            image={images.Refer}
            onPress={moveToRefer}
          />
          <RenderData
            name={strings.AccInfo}
            image={images.user}
            onPress={moveToAcc}
          />
          <RenderData
            name={strings.ContactList}
            image={images.userGroup}
            onPress={moveToContact}
          />
          <RenderData
            name={strings.Language}
            image={images.Language}
            onPress={moveToLang}
          />

          <View style={localStyles.bottomLine} />
          <RenderData
            name={strings.GS}
            image={images.Setting}
            onPress={moveToGs}
          />
          <RenderData name={strings.ChangePass} image={images.Lock} />
          <RenderData name={strings.ChangePin} image={images.Scan} />

          <View style={localStyles.bottomLine} />
          <RenderData
            name={strings.FQA}
            image={images.FQA}
            onPress={moveToFQA}
          /> */}
          {/* <RenderData name={strings.Rate} image={images.RateUs} /> */}
          <RenderData
            onPress={moveToLogOut}
            name={strings.LogOut}
            image={images.user}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  imgSty: {
    width: moderateScale(100),
    height: moderateScale(100),
    marginTop: moderateScale(20),
    marginBottom: moderateScale(150),
    ...styles.selfCenter,
  },
  parentImg: {
    ...styles.center,
  },
  outerComponent: {
    ...styles.center,
    gap: moderateScale(7),
    position: 'absolute',
    top: moderateScale(165),
    width: '100%',
  },
  iconSty: {
    width: moderateScale(40),
    height: moderateScale(40),
  },
  parentCompo: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.mv10,
    gap: moderateScale(20),
  },
  outerContainer: {
    ...styles.mt10,
    ...styles.flex,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  bottomLine: {
    ...styles.mv15,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
});
