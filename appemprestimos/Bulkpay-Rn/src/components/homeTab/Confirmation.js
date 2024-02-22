import {SafeAreaView, StyleSheet, Text, View, Image} from 'react-native';
import React, {useRef} from 'react';

// Local imports
import {styles} from '../../themes';
import CHeader from '../common/CHeader';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import strings from '../../i18n/strings';
import CButton from '../common/CButton';
import TopUpSuccess from '../modals/TopUpSuccess';

export default function Confirmation({route}) {
  let successRef = useRef(null);
  let {amount} = route.params;

  const onPress = () => {
    successRef.current.show();
  };
  const ConfirmationData = ({name, dollars, Total, TotalDollars}) => {
    return (
      <View>
        <View style={localStyles.parentBalance}>
          <CText color={colors.tabColor} type={'M16'}>
            {name}
          </CText>
          <CText color={colors.black} type={'B16'}>
            {dollars}
          </CText>
        </View>

        <View style={localStyles.parentBalance}>
          <CText color={colors.tabColor} type={'M16'}>
            {Total}
          </CText>
          <CText color={colors.black} type={'B16'}>
            {TotalDollars}
          </CText>
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View>
        <View style={localStyles.margin}>
          <CHeader color={colors.black} title={'Confirmation'} />
          <View style={localStyles.imgParent}>
            <Image source={images.card3} style={localStyles.cardSty} />
          </View>

          <ConfirmationData
            name={strings.TUB}
            dollars={'$' + parseFloat(amount).toFixed(2)}
          />
          <ConfirmationData
            name={strings.FeeTxt}
            dollars={'$' + parseFloat(strings.Fee).toFixed(2)}
          />
          <View style={localStyles.line}></View>
          <ConfirmationData
            Total={strings.Total}
            TotalDollars={
              '$' + (parseFloat(amount) + parseFloat(strings.Fee)).toFixed(2)
            }
          />
        </View>
      </View>

      <CButton
        text={'Confirm Top Up'}
        containerStyle={localStyles.parentButton}
        onPress={onPress}
      />
      <TopUpSuccess sheetRef={successRef} />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.flex,
    ...styles.justifyBetween,
    backgroundColor: colors.white,
  },
  margin: {
    ...styles.mh20,
  },
  cardSty: {
    width: moderateScale(327),
    height: moderateScale(190),
  },
  imgParent: {
    ...styles.center,
    ...styles.mv20,
  },
  parentBalance: {
    ...styles.rowSpaceBetween,
    ...styles.mv5,
  },
  line: {
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  parentButton: {
    width: '90%',
    ...styles.mv25,
  },
});
