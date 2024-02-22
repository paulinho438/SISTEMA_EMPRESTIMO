import {StyleSheet, SafeAreaView, Image, View} from 'react-native';
import React, {useState} from 'react';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes';
import images from '../../assets/images/index';
import {getHeight, moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import CButton from '../common/CButton';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import TransferPopUp from '../modals/TransferPopUp';
import {Dropdown} from 'react-native-element-dropdown';
import {CurrencyList} from '../../api/constants';

export default function SendMoney() {
  const [visible, setVisible] = useState(false);
  const [amount, setAmount] = useState('');
  const [currency, setCurrency] = useState('');

  const onPressClose = () => {
    setVisible(!visible);
  };

  const onChangeAmount = txt => {
    setAmount(parseFloat(txt));
  };

  const onChangeCurrency = ({value}) => {
    setCurrency(value);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper containerStyle={localStyles.keyBoardSty}>
        <View>
          <CHeader color={colors.black} title={'Send Money'} />
          <View style={localStyles.mainImg}>
            <Image source={images.Girl} style={localStyles.girlImg} />
          </View>
          <CText
            color={colors.black}
            align={'center'}
            type={'M14'}
            style={localStyles.mariaTxt}>
            {strings.ToMaria}
          </CText>

          <View style={localStyles.mainBorder}>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
                {strings.EnterAmount}
              </CText>
              <CText type={'M12'} color={colors.tabColor}>
                {strings.MaxAmt}
              </CText>
            </View>

            <View style={localStyles.parentTxtInp}>
              <Dropdown
                style={localStyles.dropdownStyle}
                data={CurrencyList}
                value={currency}
                maxHeight={moderateScale(180)}
                labelField="label"
                valueField="value"
                label={strings.usd}
                onChange={onChangeCurrency}
                selectedTextStyle={localStyles.miniContainer}
                itemTextStyle={localStyles.miniContainer}
                itemContainerStyle={{
                  backgroundColor: colors.GreyScale,
                  width: 'auto',
                }}
              />

              <CTextInput
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={amount}
                onChangeText={onChangeAmount}
              />
            </View>
          </View>
        </View>
      </KeyBoardAvoidWrapper>
      <CButton
        containerStyle={localStyles.mainCButton}
        text={'Send Money'}
        onPress={onPressClose}
        disabled={!!!amount}
      />
      <TransferPopUp
        visible={visible}
        onPressClose={onPressClose}
        amount={amount}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.flex,
    backgroundColor: colors.white,
    ...styles.justifyBetween,
  },
  mainImg: {
    borderWidth: moderateScale(1.5),
    borderRadius: moderateScale(60),
    borderColor: colors.numbersColor,
    ...styles.selfCenter,
    ...styles.p10,
  },
  girlImg: {
    width: moderateScale(88),
    height: moderateScale(88),
  },
  parentImg: {
    ...styles.center,
  },
  mariaTxt: {
    ...styles.mv30,
  },
  parentAmt: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.mt10,
  },
  mainBorder: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
    ...styles.ph20,
  },
  parentUsd: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(8),
    width: moderateScale(67),
    backgroundColor: colors.GreyScale,
    ...styles.rowCenter,
    ...styles.mv15,
  },
  UsdTxt: {
    ...styles.p5,
  },
  CTxtInp: {
    width: moderateScale(210),
    borderRadius: moderateScale(15),
    height: moderateScale(35),
    ...styles.mv15,
    backgroundColor: colors.GreyScale,
  },
  parentTxtInp: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  ChildTxtInp: {
    ...typography.fontSizes.f24,
    ...typography.fontWeights.SemiBold,
  },
  mainCButton: {
    ...styles.mv30,
    width: '90%',
  },
  keyBoardSty: {
    ...styles.ph20,
    ...styles.flexGrow1,
    ...styles.mainContainerSurface,
  },
  dropdownStyle: {
    backgroundColor: colors.GreyScale,
    height: getHeight(52),
    borderRadius: moderateScale(15),
    borderWidth: moderateScale(1),
    ...styles.ph20,
    width: '32%',
    ...styles.mv10,
  },
  miniContainer: {
    color: colors.black,
  },
});
