import {TouchableOpacity, StyleSheet, View} from 'react-native';
import React from 'react';

// Local imports
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import typography from '../../themes/typography';
import CText from './CText';

const CButton = props => {
  let {
    ParentLoginBtn,
    ChildLoginBtn,
    text,
    onPress,
    containerStyle,
    disabled,
    RightIcon,
  } = props;

  return (
    <View
      style={[styles.rowStart, styles.center]}>
      <TouchableOpacity
        disabled={disabled}
        style={[localStyles.ParentButton, ParentLoginBtn, containerStyle]}
        onPress={onPress}>
        <CText style={[localStyles.ChildButton, ChildLoginBtn]}>
          {text || 'Continue'}
        </CText>
        {!!RightIcon && <RightIcon />}
      </TouchableOpacity>
    </View>
  );
};

const localStyles = StyleSheet.create({
  ParentButton: {
    borderRadius: moderateScale(16),
    width: '100%',
    height: moderateScale(54),
    ...styles.center,
    ...styles.mt30,
    backgroundColor: colors.black,
  },

  ChildButton: {
    textAlign: 'center',
    fontSize: moderateScale(16),
    color: 'white',
    ...typography.fontSizes.f16,
    ...typography.fontWeights.Bold,
  },
});

export default CButton;
