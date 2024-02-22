import React from 'react';
import {KeyboardAvoidingView, ScrollView} from 'react-native';

import {isIOS, moderateScale} from '../../common/constant';
import {styles} from '../../themes';

export default KeyBoardAvoidWrapper = ({
  children,
  containerStyle,
  contentContainerStyle,
}) => {
  return (
    <KeyboardAvoidingView
      // keyboardVerticalOffset={isIOS ? moderateScale(10) : null}
      style={[styles.flex, containerStyle]}
      behavior={isIOS ? 'padding' : null}>
      <ScrollView
        nestedScrollEnabled={true}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={contentContainerStyle}
        bounces={false}>
        {children}
      </ScrollView>
    </KeyboardAvoidingView>
  );
};
