import {StyleSheet} from 'react-native';
import flex from './flex';
import margin from './margin';
import padding from './padding';
import commonStyles from './commonStyles'

export const styles = StyleSheet.create({
  ...flex,
  ...margin,
  ...padding,
  ...commonStyles
});
