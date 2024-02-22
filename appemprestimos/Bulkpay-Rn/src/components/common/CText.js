import React from 'react';
import {Text} from 'react-native';
import {colors} from '../../themes/colors';
import Typography from '../../themes/typography';

const CText = ({type, style, align, color, children, onPress, ...props}) => {
  const fontWeights = () => {
    switch (type.charAt(0).toUpperCase()) {
      case 'R':
        return Typography.fontWeights.Regular;
      case 'M':
        return Typography.fontWeights.Medium;
      case 'S':
        return Typography.fontWeights.SemiBold;
      case 'B':
        return Typography.fontWeights.Bold;
      default:
        return Typography.fontWeights.Regular;
    }
  };

  const fontSize = () => {
    switch (type.slice(1)) {
      case '12':
        return Typography.fontSizes.f12;
      case '14':
        return Typography.fontSizes.f14;
      case '16':
        return Typography.fontSizes.f16;
      case '18':
        return Typography.fontSizes.f18;
      case '20':
        return Typography.fontSizes.f20;
      case '24':
        return Typography.fontSizes.f24;
      case '32':
        return Typography.fontSizes.f32;
      case '40':
        return Typography.fontSizes.f40;
      case '48':
        return Typography.fontSizes.f48;
      default:
        return Typography.fontSizes.f14;
    }
  };

  return (
    <Text
      style={[
        type && {...fontWeights(), ...fontSize()},
        {color: color ? color : colors.textColor},
        align && {textAlign: align},
        style,
      ]}
      onPress={onPress}
      {...props}>
      {children}
    </Text>
  );
};

export default React.memo(CText);
