import {
  StyleSheet,
  TextInput,
  View,
  Image,
  TouchableOpacity,
} from 'react-native';
import React, {useState} from 'react';
import {moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import Images from '../../assets/images';
import { TextInputMask } from 'react-native-masked-text';

export default function CTextInput({
  text,
  textInputStyle,
  mainTxtInp,
  isSecure,
  RightIcon,
  LeftIcon,
  onChangeText,
  value,
  onPress,
  keyboardType,
  align,
  onFocus,
  onBlur,
  maskType, // Adiciona uma nova prop para o tipo de máscara
}) {
  const [isSecurePass, setIsSecurePass] = useState(isSecure);

  const onPressSecureIcon = () => {
    setIsSecurePass(!isSecurePass);
  };

  const renderTextInput = () => {
    if (maskType === 'datetime') {
      return (
        <TextInputMask
          type={'datetime'}
          options={{
            format: 'DD/MM/YYYY',
          }}
          onFocus={onFocus}
          onBlur={onBlur}
          style={[localStyles.local, textInputStyle]}
          placeholder={text}
          placeholderTextColor={colors.silver}
          value={value}
          textAlign={align}
          onChangeText={onChangeText}
          keyboardType={keyboardType}
        />
      );
    }
    
    // Caso não haja uma máscara definida, utilize o TextInput padrão
    return (
      <TextInput
        onFocus={onFocus}
        onBlur={onBlur}
        style={[localStyles.local, textInputStyle]}
        placeholder={text}
        placeholderTextColor={colors.silver}
        secureTextEntry={isSecurePass}
        value={value}
        textAlign={align}
        onChangeText={onChangeText}
        onPress={onPress}
        keyboardType={keyboardType}
      />
    );
  };

  return (
    <View style={[localStyles.main, mainTxtInp]}>
      {!!LeftIcon && <LeftIcon />}
      {renderTextInput()}
      {!!RightIcon && <RightIcon />}
      {!!isSecure && (
        <TouchableOpacity onPress={onPressSecureIcon}>
          {!isSecurePass ? (
            <Image source={Images.view} style={localStyles.EyePng} />
          ) : (
            <Image source={Images.nonView} style={localStyles.EyePng} />
          )}
        </TouchableOpacity>
      )}
    </View>
  );
}

const localStyles = StyleSheet.create({
  main: {
    width: '100%',
    height: moderateScale(56),
    backgroundColor: colors.white,
    borderRadius: moderateScale(16),
    ...styles.rowCenter,
    ...styles.justifyBetween,
  },
  local: {
    color: colors.black,
    ...styles.pl15,
    ...styles.flex,
    width: '100%',
    height: moderateScale(80),
  },
  EyePng: {
    ...styles.mr10,
    width: moderateScale(24),
    height: moderateScale(24),
  },
});