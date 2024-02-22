import {moderateScale} from '../common/constant';

const fontWeights = {
  Regular: {
    fontFamily: 'Recursive-Regular',
  },
  Medium: {
    fontFamily: 'Recursive-Medium',
  },
  SemiBold: {
    fontFamily: 'Recursive-SemiBold',
  },
  Bold: {
    fontFamily: 'Recursive-Bold',
  },
};

const fontSizes = {
  f12: {
    fontSize: moderateScale(12),
  },
  f14: {
    fontSize: moderateScale(14),
  },
  f16: {
    fontSize: moderateScale(16),
  },
  f18: {
    fontSize: moderateScale(18),
  },
  f20: {
    fontSize: moderateScale(20),
  },
  f24: {
    fontSize: moderateScale(24),
  },
  f32: {
    fontSize: moderateScale(32),
  },
  f40: {
    fontSize: moderateScale(40),
  },
  f46: {
    fontSize: moderateScale(46),
  },
};

const typography = {fontWeights, fontSizes};

export default typography;
