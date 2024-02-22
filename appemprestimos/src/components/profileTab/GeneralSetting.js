import {
  SafeAreaView,
  StyleSheet,
  View,
  Image,
  Switch,
  TouchableOpacity,
} from 'react-native';
import React, {useState} from 'react';
import Material from 'react-native-vector-icons/MaterialIcons';

// Local imports
import {styles} from '../../themes/index';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import CHeader from '../common/CHeader';
import {colors} from '../../themes/colors';

export default function GeneralSetting({navigation}) {
  const [close, open] = useState(false);
  const [close1, open1] = useState(false);
  const [close2, open2] = useState(false);

  const onchange = () => {
    open(!close);
  };
  const onchange1 = () => {
    open1(!close1);
  };
  const onchange2 = () => {
    open2(!close2);
  };

  const CommonCom = ({source, strings}) => {
    return (
      <View style={localStyles.parentContainer}>
        <Image source={source} style={localStyles.imgSty} />

        <View style={localStyles.outerComponent}>
          <CText color={colors.black} type={'M14'}>
            {strings}
          </CText>
          <Material color={colors.black} name={'navigate-next'} size={16} />
        </View>
      </View>
    );
  };

  const SettingComponent = ({name, subName, onchange, value}) => {
    return (
      <View style={localStyles.mainContainer}>
        <View style={localStyles.outerContainer}>
          <View style={{gap: moderateScale(8)}}>
            <CText color={colors.black} type={'M14'}>
              {name}
            </CText>
            <CText
              type={'R12'}
              color={colors.tabColor}
              style={localStyles.lineTxt}>
              {subName}
            </CText>
          </View>

          <Switch
            trackColor={{false: colors.bottomBorder, true: colors.Toggle}}
            value={value}
            onChange={onchange}
          />
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={styles.mh20}>
        <CHeader color={colors.black} title={'General Setting'} />

        <TouchableOpacity>
          <CommonCom source={images.Notification} strings={strings.DNA} />
        </TouchableOpacity>

        <TouchableOpacity>
          <CommonCom source={images.Setting} strings={strings.MangeNot} />
        </TouchableOpacity>

        <View style={localStyles.line} />

        <SettingComponent
          value={close}
          onchange={onchange}
          name={strings.DNA}
          subName={strings.DNALine}
        />
        <SettingComponent
          value={close1}
          onchange={onchange1}
          name={strings.BillCal}
          subName={strings.BillCalLine}
        />
        <SettingComponent
          value={close2}
          onchange={onchange2}
          name={strings.CScoreCalendar}
          subName={strings.CScoreCalendarLine}
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
    width: moderateScale(40),
    height: moderateScale(40),
  },
  parentContainer: {
    ...styles.mt40,
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(15),
  },
  outerComponent: {
    ...styles.flex,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  line: {
    ...styles.mv22,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  lineTxt: {
    width: moderateScale(210),
  },
  outerContainer: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  mainContainer: {
    ...styles.mv20,
  },
});
