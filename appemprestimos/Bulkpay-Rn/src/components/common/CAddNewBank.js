import {StyleSheet, View, TouchableOpacity} from 'react-native';
import React from 'react';
import Material from 'react-native-vector-icons/MaterialIcons';
import MaterialCommunity from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import CText from './CText';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes';
import {colors} from '../../themes/colors';

export default function CAddNewBank({newBankSty}) {
  return (
    <View>
      <TouchableOpacity style={[localStyles.parentNewBank, newBankSty]}>
        <MaterialCommunity
          color={colors.black}
          name={'bank-outline'}
          size={20}
        />

        <View style={localStyles.outerAddBank}>
          <CText color={colors.black} type={'B16'}>
            {strings.AddNewBank}
          </CText>
          <Material color={colors.black} name={'navigate-next'} size={20} />
        </View>
      </TouchableOpacity>
    </View>
  );
}

const localStyles = StyleSheet.create({
  parentNewBank: {
    gap: moderateScale(13),
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.mh20,
    backgroundColor: colors.GreyScale,
    ...styles.p20,
    borderRadius: moderateScale(16),
  },
  outerAddBank: {
    ...styles.flex,
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
});
