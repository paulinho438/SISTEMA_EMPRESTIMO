import {StyleSheet, Image, View, TouchableOpacity} from 'react-native';
import React from 'react';

// Local imports
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import strings from '../../i18n/strings';

export const CommonBOA = props => {
  let {ParentContainer, Icon, source, onPress} = props;
  return (
    <TouchableOpacity
      style={[localStyles.parentBOA, ParentContainer]}
      onPress={onPress}>
      <Image source={source} style={localStyles.AmericaPng} />
      <View style={localStyles.forIcon}>
        <View style={localStyles.BOATxt}>
          <CText color={colors.black} type={'S16'}>
            {strings.BOA}
          </CText>
          <CText color={colors.tabColor} type={'M12'}>
            {strings.AnnaNumber}
          </CText>
        </View>
      </View>
      {Icon}
    </TouchableOpacity>
  );
};

const localStyles = StyleSheet.create({
  parentBOA: {
    backgroundColor: colors.bottomBorder,
    ...styles.flexRow,
    ...styles.itemsCenter,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.google,
    ...styles.ph20,
    ...styles.mv10,
  },
  AmericaPng: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  forIcon: {
    ...styles.flex,
    ...styles.justifyBetween,
  },
  BOATxt: {
    ...styles.p15,
    ...styles.justifyBetween,
    gap: moderateScale(10),
  },
});
