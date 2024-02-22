import {
  StyleSheet,
  SafeAreaView,
  Image,
  View,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import React, {useState} from 'react';
import Feathers from 'react-native-vector-icons/FontAwesome';
import {Dropdown} from 'react-native-element-dropdown';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes/index';
import images from '../../assets/images/index';
import {getHeight, moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';
import {CurrencyList, DollarsData} from '../../api/constants';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import CDropdownInput from '../common/CDropdownInput';
import strings from '../../i18n/strings';

export default function TopUpScreen({navigation}) {
  const [amount, setAmount] = useState('');
  const [Data, setData] = useState('');
  const [currency, setCurrency] = useState();

  const moveToConfirm = () => {
    navigation.navigate(StackNav.Confirmation, {amount: amount});
  };

  const onChangeColor = item => {
    setData(item);
  };

  const onChangeAmount = txt => {
    setAmount(parseFloat(txt));
  };

  const onChangeCurrency = value => {
    setCurrency(value);
  };

  const dollarsData = ({item}) => {
    return (
      <TouchableOpacity
        style={[
          localStyles.mainDollarsData,
          {
            backgroundColor: Data === item ? colors.Primary : colors.GreyScale,
          },
        ]}
        onPress={() => onChangeColor(item)}>
        <CText
          type={'M14'}
          style={[
            localStyles.itemTxt,
            {
              color: Data === item ? colors.white : colors.black,
            },
          ]}>
          {item}
        </CText>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper contentContainerStyle={localStyles.keyboardType}>
        <View>
          <CHeader color={colors.black} title={'Top Up'} />
          <TouchableOpacity style={localStyles.parentDebit}>
            <Image source={images.card3} style={localStyles.ImageSty} />
            <View style={localStyles.innerContainer}>
              <CText
                color={colors.tabColor}
                type={'B18'}
                style={localStyles.debitTxt}>
                {strings.Debit}
              </CText>
              <CText color={colors.black} type={'B18'}>
                {strings.RealDollars}
              </CText>
            </View>
            <Feathers
              color={colors.black}
              name={'angle-down'}
              style={localStyles.angleButton}
              size={24}
            />
          </TouchableOpacity>

          <View style={localStyles.mainBorder}>
            <View style={localStyles.parentAmt}>
              <CText type={'M12'} color={colors.tabColor}>
                {strings.EnterAmount}
              </CText>
              <CText type={'M12'} color={colors.tabColor}>
                {strings.TopUpFee}
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
                placeholder={strings.PleaseAmount}
                placeholde
                mainTxtInp={localStyles.CTxtInp}
                textInputStyle={localStyles.ChildTxtInp}
                keyboardType={'numeric'}
                value={amount}
                onChangeText={onChangeAmount}
              />
            </View>
          </View>

          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={DollarsData}
            renderItem={dollarsData}
            horizontal
          />
        </View>
      </KeyBoardAvoidWrapper>

      <CButton
        disabled={!!!amount}
        containerStyle={localStyles.CButton}
        onPress={moveToConfirm}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.mainContainerSurface,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  ImageSty: {
    width: moderateScale(42),
    height: moderateScale(24),
  },
  parentDebit: {
    ...styles.mv30,
    backgroundColor: colors.GreyScale,
    ...styles.flexRow,
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
    ...styles.alignCenter,
    ...styles.p15,
  },
  debitTxt: {
    ...styles.pl15,
  },
  innerContainer: {
    ...styles.flex,
    ...styles.rowSpaceBetween,
  },
  angleButton: {
    ...styles.pl10,
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
  Dollars1txt: {
    ...styles.ph16,
    ...styles.p8,
  },
  mainDollarsData: {
    ...styles.mv25,
    ...styles.ph15,
    ...styles.center,
    ...styles.mr22,
    backgroundColor: colors.red,
    height: moderateScale(40),
    borderRadius: moderateScale(12),
  },
  keyboardType: {
    ...styles.ph20,
  },
  CButton: {
    width: '90%',
    ...styles.mv25,
  },
  miniContainer: {
    color: colors.black,
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
});
