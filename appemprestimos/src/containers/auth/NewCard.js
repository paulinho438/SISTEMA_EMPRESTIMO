import {
  SafeAreaView,
  StyleSheet,
  View,
  Image,
  TouchableOpacity,
  Alert,
} from 'react-native';
import React, {useRef, useState} from 'react';

// Local imports
import CText from '../../components/common/CText';
import images from '../../assets/images/index';
import strings from '../../i18n/strings';
import CTextInput from '../../components/common/CTextInput';
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import {moderateScale} from '../../common/constant';
import {US} from '../../assets/svgs';
import Countries from '../../components/modals/Countries';
import Feathers from 'react-native-vector-icons/FontAwesome';
import CButton from '../../components/common/CButton';
import KeyBoardAvoidWrapper from '../../components/common/KeyBoardAvoidWrapper';
import ReadyCard from '../../components/modals/ReadyCard';
import CHeader from '../../components/common/CHeader';
import {
  atmCardNumberRegex,
  expiryDateValidation,
  validateName,
  vccNumber,
} from '../../utils/validation';

const BlurStyle = {
  borderColor: colors.white,
};

const FocusStyle = {
  borderColor: colors.numbersColor,
};

export default function NewCard() {
  const init = useRef(null);

  const [country, setCountry] = useState('');
  const [visible, setVisible] = useState(false);
  const [focus, setFocus] = useState(BlurStyle);
  const [focus2, setFocus2] = useState(BlurStyle);
  const [focus3, setFocus3] = useState(BlurStyle);
  const [focus4, setFocus4] = useState(BlurStyle);

  const [card, setCard] = useState('');
  const [message, setMessage] = useState(false);

  const [cardName, setCardName] = useState('');
  const [message2, setMessage2] = useState(false);

  const [vcc, setVcc] = useState('');
  const [message3, setMessage3] = useState(false);

  const [expiryDate, setExpiryDate] = useState('');
  const [message4, setMessage4] = useState(false);

  const onPressSave = () => {
    // if (card === '' || message || message2 || message3 || message4) {
    //   Alert.alert(strings.PleaseFill);
    // } else {
    //   setVisible(!visible);
    // }
    setVisible(!visible);
  };

  const cardNumberValidation = itm => {
    const {msg} = atmCardNumberRegex(itm);
    setCard(itm);
    setMessage(msg);
  };

  const cardHolderName = itm => {
    const {msg} = validateName(itm);
    setCardName(itm);
    setMessage2(msg);
  };

  const vccValidation = itm => {
    const {msg} = vccNumber(itm);
    setVcc(itm);
    setMessage3(msg);
  };

  const expiryDateVal = itm => {
    const {msg} = expiryDateValidation(itm);
    setExpiryDate(itm);
    setMessage4(msg);
  };

  const onFocus = () => {
    onFocusInput(setFocus);
  };

  const onBlur = () => {
    onBlurInput(setFocus);
  };

  const onFocus2 = () => {
    onFocusInput(setFocus2);
  };

  const onBlur2 = () => {
    onBlurInput(setFocus2);
  };

  const onFocus3 = () => {
    onFocusInput(setFocus3);
  };

  const onBlur3 = () => {
    onBlurInput(setFocus3);
  };

  const onFocus4 = () => {
    onFocusInput(setFocus4);
  };

  const onBlur4 = () => {
    onBlurInput(setFocus4);
  };

  const onFocusInput = onHighlight => {
    onHighlight(FocusStyle);
  };
  const onBlurInput = onHighlight => {
    onHighlight(BlurStyle);
  };

  const showCountry = () => {
    init.current?.show();
  };

  const selectedCountry = itm => {
    setCountry(itm);
  };

  const onPressCancel = () => {
    setVisible(!visible);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <KeyBoardAvoidWrapper contentContainerStyle={styles.flexGrow1}>
        <View style={styles.mh20}>
          <CHeader color={colors.black} title={'New Card'} />
          <View style={localStyles.parentImg}>
            <Image style={localStyles.imgStyle} source={images.MainCard} />
          </View>

          <View style={localStyles.parentColor}>
            <Image source={images.CardColor} />
          </View>

          <CText color={colors.black} type={'B24'}>
            {strings.CardDetailTxt}
          </CText>
          <CTextInput
            value={card}
            onChangeText={cardNumberValidation}
            onFocus={onFocus}
            onBlur={onBlur}
            keyboardType={'number-pad'}
            text={'Card number'}
            mainTxtInp={[localStyles.numberTxt, focus]}
            RightIcon={() => (
              <Image
                style={localStyles.imageStyle}
                source={images.MasterIcon}
              />
            )}
          />

          {message ? <CText color={colors.red}>{message}</CText> : null}

          <View style={localStyles.mainCTxtInp}>
            <CTextInput
              value={expiryDate}
              onChangeText={expiryDateVal}
              onFocus={onFocus2}
              onBlur={onBlur2}
              text={'Expiry date'}
              mainTxtInp={[localStyles.parentCTxtInp, focus2]}
            />
            <CTextInput
              value={vcc}
              onChangeText={vccValidation}
              onFocus={onFocus3}
              onBlur={onBlur3}
              keyboardType={'numeric'}
              text={'VCC'}
              mainTxtInp={[localStyles.parentCTxtInp, focus3]}
            />
          </View>

          <View style={localStyles.mainValidationContainer}>
            <View style={localStyles.validationContainer}>
              {message4 ? <CText color={colors.red}>{message4}</CText> : null}
            </View>
            <View style={localStyles.validationContainer}>
              {message3 ? <CText color={colors.red}>{message3}</CText> : null}
            </View>
          </View>

          <CTextInput
            value={cardName}
            onChangeText={cardHolderName}
            onFocus={onFocus4}
            onBlur={onBlur4}
            text={'Card holder'}
            mainTxtInp={[localStyles.numberTxt, focus4]}
          />

          {message2 ? <CText color={colors.red}>{message2}</CText> : null}
          <TouchableOpacity
            onPress={showCountry}
            style={localStyles.mainSelector}>
            <View style={localStyles.mainBox}>
              {!!country ? (
                <View style={[styles.itemsCenter, styles.flexRow]}>
                  {country?.svgIcon}
                  <CText
                    color={colors.black}
                    type={'B18'}
                    style={localStyles.USTxtStyle}>
                    {country?.FullName}
                  </CText>
                </View>
              ) : (
                <View style={[styles.itemsCenter, styles.flexRow]}>
                  <US />
                  <CText
                    color={colors.black}
                    type={'B18'}
                    style={localStyles.USTxtStyle}>
                    {strings.America}
                  </CText>
                </View>
              )}

              <Feathers
                color={colors.black}
                name={'angle-down'}
                style={localStyles.angleButton}
                size={24}
              />
            </View>
          </TouchableOpacity>

          <Countries sheetRef={init} selectedCountry={selectedCountry} />

          <ReadyCard visible={visible} onPressClose={onPressCancel} />
        </View>
      </KeyBoardAvoidWrapper>

      <CButton
        containerStyle={localStyles.CButtonStyle}
        text={'Save'}
        onPress={onPressSave}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  imgStyle: {
    width: moderateScale(360),
    height: moderateScale(200),
  },
  parentImg: {
    ...styles.alignCenter,
    ...styles.mv10,
  },
  parentColor: {
    position: 'absolute',
    top: moderateScale(87),
    right: 0,
  },
  numberTxt: {
    backgroundColor: colors.GreyScale,
    borderWidth: moderateScale(1),
  },
  parentCTxtInp: {
    backgroundColor: colors.GreyScale,
    borderWidth: moderateScale(1),
    width: '48%',
  },
  mainCTxtInp: {
    ...styles.mv10,
    ...styles.rowCenter,
    ...styles.justifyBetween,
  },
  validationContainer: {
    ...styles.flex,
    ...styles.mb20,
  },
  imageStyle: {
    ...styles.mr15,
  },
  mainBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt10,
    ...styles.pl20,
    backgroundColor: colors.GreyScale,
    height: moderateScale(56),
    borderRadius: moderateScale(16),
  },
  USTxtStyle: {
    ...styles.pl15,
  },
  angleButton: {
    ...styles.ph15,
  },
  mainSelector: {
    ...styles.mt20,
  },
  mainValidationContainer: {
    ...styles.rowSpaceBetween,
    gap: moderateScale(15),
  },
  CButtonStyle: {
    ...styles.mv15,
    width: '90%',
  },
});
