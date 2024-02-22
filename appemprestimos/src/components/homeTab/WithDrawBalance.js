import {StyleSheet, View, FlatList, TouchableOpacity} from 'react-native';
import React, {useRef, useState} from 'react';
import Feather from 'react-native-vector-icons/Feather';

// Local imports
import {SafeAreaView} from 'react-native-safe-area-context';
import CHeader from '../common/CHeader';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import {percentageData} from '../../api/constants';
import CButton from '../common/CButton';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import SelectBank from '../modals/SelectBank';
import {CommonBOA} from '../common/CommonBOA';
import images from '../../assets/images/index';

export default function WithDrawBalance() {
  const [val, setVal] = useState('');
  const [data, setData] = useState('');
  const BankRef = useRef(null);

  const onChangeColor = item => {
    setData(item);
  };

  const openSheet = () => {
    BankRef.current.show();
  };

  const renderPercent = ({item}) => {
    return (
      <TouchableOpacity
        style={[
          localStyles.mainData,
          {
            backgroundColor: data === item ? colors.Primary : colors.GreyScale,
          },
        ]}
        onPress={() => onChangeColor(item)}>
        <CText
          type={'S14'}
          style={{
            color: data === item ? colors.white : colors.black,
          }}>
          {item}
        </CText>
      </TouchableOpacity>
    );
  };

  const onChangeText = txt => {
    setVal(txt);
  };
  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper>
        <View style={localStyles.parent}>
          <CHeader color={colors.black} title={'WithDraw'} />
          <CommonBOA
            source={images.BankAmerica}
            Icon={
              <Feather color={colors.black} name={'chevron-down'} size={20} />
            }
          />
        </View>

        <CTextInput
          align={'center'}
          keyboardType={'numeric'}
          value={val}
          onChangeText={onChangeText}
          text={'Amount'}
          textInputStyle={localStyles.childTxtInp}
        />

        <CText
          color={colors.black}
          align={'center'}
          type={'R14'}
          style={localStyles.maxTxt}>
          {strings.MaxAmt}
        </CText>

        <FlatList   keyExtractor={(item, index) => index.toString()} data={percentageData} renderItem={renderPercent} horizontal />
      </KeyBoardAvoidWrapper>
      <CButton
        disabled={!!!val}
        containerStyle={localStyles.parentCButton}
        onPress={openSheet}
      />

      <SelectBank sheetRef={BankRef} />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  parent: {
    ...styles.mh25,
  },
  forIcon: {
    ...styles.flex,
    ...styles.justifyBetween,
  },
  AmericaPng: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  parentBOA: {
    ...styles.mh20,
    ...styles.flexRow,
    ...styles.alignCenter,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.google,
    ...styles.mv30,
    ...styles.ph20,
  },
  BOATxt: {
    ...styles.p15,
    ...styles.flex,
    ...styles.justifyBetween,
    gap: moderateScale(10),
  },
  childTxtInp: {
    ...typography.fontSizes.f40,
    ...typography.fontWeights.Bold,
    textAlign: 'center',
    ...styles.pr15,
  },
  maxTxt: {
    ...styles.pv20,
  },
  mainData: {
    ...styles.mv25,
    ...styles.center,
    ...styles.mh9,
    width: moderateScale(75),
    height: moderateScale(40),
    borderRadius: moderateScale(12),
  },
  parentCButton: {
    width: '90%',
    ...styles.mv25,
  },
});
