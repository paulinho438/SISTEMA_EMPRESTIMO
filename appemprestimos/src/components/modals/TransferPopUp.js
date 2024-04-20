import {
  Modal,
  StyleSheet,
  View,
  TouchableOpacity,
  ImageBackground,
  Platform,
} from 'react-native';
import React from 'react';
import {useNavigation} from '@react-navigation/native';

// Local imports
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CButton from '../common/CButton';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';
import CText from '../common/CText';
import strings from '../../i18n/strings';

export default function TransferPopUp(props) {
  let {visible, onPressClose, amount} = props;

  const navigation = useNavigation();

  const moveToHome = () => {
    navigation.navigate(StackNav.TransferProof, {amount: amount});
    onPressClose();
  };

  const Detail = ({
    header,
    bankName,
    name,
    cardNumber,
    total,
    prize,
    isTotal = false,
  }) => {
    return (
      <View style={localStyles.parentFromBOA}>
        {!isTotal ? (
          <View>
            <View style={localStyles.detailHeaderStyle}>
              <CText color={colors.tabColor} type={'R12'}>
                {header}
              </CText>
              <CText color={colors.tabColor} type={'R12'}>
                {bankName}
              </CText>
            </View>
            <View style={localStyles.detailStyle}>
              <CText color={colors.black} type={'B16'}>
                {name}
              </CText>
              <CText color={colors.black} type={'B16'}>
                {cardNumber}
              </CText>
            </View>
          </View>
        ) : (
          <View style={localStyles.totalPrize}>
            <CText color={colors.tabColor} type={'M16'}>
              {total}
            </CText>
            <CText color={colors.black} type={'B16'}>
              {prize}
            </CText>
          </View>
        )}
      </View>
    );
  };

  return (
    <View style={localStyles.flex}>
      <Modal animationType={'fade'} transparent={true} visible={visible}>
        <TouchableOpacity
          style={localStyles.modalMainContainer}
          onPress={onPressClose}>
          <TouchableOpacity activeOpacity={1} onPress={onPressClose}>
            <ImageBackground
              source={images.TransferPopUp}
              style={localStyles.imgStyle}>
              <View style={localStyles.innerContainer}>
                <View>
                  <CText color={colors.black} type={'S18'} align="center">
                    Confirmar Empréstimo
                  </CText>

                  <Detail
                    header='Banco'
                    bankName='Itau'
                    name={strings.Anabella}
                    cardNumber=''
                  />
                  <Detail
                    header={strings.To}
                    bankName={strings.Citibank}
                    name={'Maria'}
                    cardNumber={strings.MariaNumber}
                  />

                  <Detail
                    isTotal={true}
                    total={strings.Total}
                    prize={'R$' + parseFloat(amount).toFixed(2)}
                  />
                </View>
                <CButton
                  text={'Aprovar'}
                  containerStyle={[
                    localStyles.ParentLgnBtn,
                    {
                      bottom:
                        Platform.OS === 'ios'
                          ? moderateScale(0)
                          : moderateScale(40),
                    },
                  ]}
                  onPress={moveToHome}
                />
              </View>
            </ImageBackground>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

const localStyles = StyleSheet.create({
  modalMainContainer: {
    ...styles.flex,
    ...styles.center,
    backgroundColor: colors.transparent,
  },
  imgStyle: {
    width: moderateScale(327),
    height: moderateScale(464),
    ...styles.justifyCenter,
    ...styles.ph20,
  },
  innerContainer: {
    ...styles.justifyBetween,
    marginTop: moderateScale(120),
    ...styles.flex,
    ...styles.mv15,
  },
  ParentLgnBtn: {
    // bottom: moderateScale(40),
  },
  parentFromBOA: {
    ...styles.mt15,
    ...styles.pb15,
    borderBottomColor: colors.bottomBorder,
    borderBottomWidth: moderateScale(1),
  },
  detailHeaderStyle: {
    ...styles.rowSpaceBetween,
    ...styles.pv5,
  },
  detailStyle: {
    ...styles.rowSpaceBetween,
  },
  totalPrize: {
    ...styles.rowSpaceBetween,
    ...styles.mv20,
  },
});
