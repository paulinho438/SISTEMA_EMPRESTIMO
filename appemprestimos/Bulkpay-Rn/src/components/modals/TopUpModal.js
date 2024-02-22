import {Image, StyleSheet, View, FlatList} from 'react-native';
import React, {useState} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import Feather from 'react-native-vector-icons/Feather';
import AntDesign from 'react-native-vector-icons/AntDesign';
import Slider from '@react-native-community/slider';

// Local imports
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import {useNavigation} from '@react-navigation/native';
import {TouchableOpacity} from 'react-native-gesture-handler';
import CTextInput from '../common/CTextInput';
import typography from '../../themes/typography';
import {moneyData, moneyData2} from '../../api/constants';
import KeyBoardAvoidWrapper from '../common/KeyBoardAvoidWrapper';

export default function TopUpModal(props) {
  const [data, setData] = useState();

  let {sheetRef, paymentDetail} = props;
  const [defaultValue, setDefaultValue] = useState(100);

  const navigation = useNavigation();

  const onPressTopUp = () => {
    sheetRef.current.hide();
    navigation.navigate(StackNav.PhoneBook);
  };

  const onChangeTxt = txt => {
    let number = txt.replace(/\D/g, '');
    if (number) {
      setDefaultValue(parseInt(number));
    } else {
      setDefaultValue(0);
    }
  };

  const changeColor = item => {
    setData(item);
    setDefaultValue(parseInt(item));
  };

  const sliderContainer = defaultValue => {
    setDefaultValue(parseInt(defaultValue));
  };

  const MinusClick = () => {
    if (defaultValue > 0) {
      setDefaultValue(defaultValue - 1);
    }
  };

  const PlusClick = () => {
    setDefaultValue(defaultValue + 1);
  };

  const Dollars = ({item}) => {
    return (
      <TouchableOpacity
        onPress={() => changeColor(item)}
        style={[
          localStyles.parentComponent,
          {
            backgroundColor: data === item ? colors.Primary : colors.google,
          },
        ]}>
        <CText
          style={[
            {
              color: data === item ? colors.white : colors.black,
            },
          ]}
          align={'center'}
          type={'B16'}
          color={colors.black}>
          {'$' + item}
        </CText>
      </TouchableOpacity>
    );
  };

  return (
    <View>
      <KeyBoardAvoidWrapper>
        <ActionSheet ref={sheetRef} containerStyle={localStyles.actionSheet}>
          <View style={localStyles.main}>
            <View style={localStyles.mainContainer}>
              <View style={localStyles.outerComponent}>
                <Image
                  style={localStyles.imgSty}
                  source={paymentDetail.image}
                />

                <View style={{gap: moderateScale(4)}}>
                  <CText color={colors.black} type={'S16'}>
                    {paymentDetail.name}
                  </CText>
                  <CText type={'M12'} color={colors.tabColor}>
                    {paymentDetail.num}
                  </CText>
                </View>
              </View>

              <Feather
                style={styles.pr10}
                color={colors.tabColor}
                name={'chevron-down'}
                size={20}
              />
            </View>

            <CText type={'B16'} color={colors.black}>
              {strings.Account}
            </CText>

            <View style={localStyles.outerContainer}>
              <TouchableOpacity
                onPress={MinusClick}
                style={localStyles.iconStyle}>
                <AntDesign color={colors.black} name={'minus'} size={16} />
              </TouchableOpacity>

              <CTextInput
                value={'$' + defaultValue.toString()}
                onChangeText={onChangeTxt}
                keyboardType={'numeric'}
                align={'center'}
                textInputStyle={localStyles.childComponent}
                mainTxtInp={localStyles.mainComponent}
              />

              <TouchableOpacity
                onPress={PlusClick}
                style={localStyles.iconStyle}>
                <AntDesign color={colors.black} name={'plus'} size={16} />
              </TouchableOpacity>
            </View>

            <Slider
              value={defaultValue}
              onValueChange={sliderContainer}
              minimumValue={5}
              maximumValue={500}
              step={1}
              style={{width: '100%'}}
              minimumTrackTintColor={colors.Primary}
              maximumTrackTintColor={colors.google}
            />

            <View style={localStyles.dollarsComponent}>
              <FlatList
                keyExtractor={(item, index) => index.toString()}
                horizontal
                data={moneyData}
                renderItem={Dollars}
              />
            </View>
            <FlatList
              keyExtractor={(item, index) => index.toString()}
              horizontal
              data={moneyData2}
              renderItem={Dollars}
            />

            <CButton
              containerStyle={localStyles.buttonSty}
              text={'Top Up'}
              onPress={onPressTopUp}
            />
          </View>
        </ActionSheet>
      </KeyBoardAvoidWrapper>
    </View>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.ph20,
  },
  mainContainer: {
    ...styles.justifyBetween,
    ...styles.pl10,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.google,
    height: moderateScale(80),
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.mt35,
    ...styles.mv20,
  },
  outerComponent: {
    gap: moderateScale(10),
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  imgSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
  },
  mainComponent: {
    width: moderateScale(200),
  },
  outerContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.alignCenter,
  },
  iconStyle: {
    ...styles.mv40,
    backgroundColor: colors.google,
    borderRadius: moderateScale(8),
    width: moderateScale(32),
    height: moderateScale(32),
    ...styles.center,
  },
  childComponent: {
    ...styles.pl0,
    ...typography.fontSizes.f32,
  },
  parentComponent: {
    ...styles.mh5,
    ...styles.mv10,
    borderRadius: moderateScale(16),
    width: moderateScale(73),
    height: moderateScale(72),
    ...styles.center,
  },
  dollarsComponent: {
    ...styles.mt30,
  },
  buttonSty: {
    ...styles.mv30,
  },
});
