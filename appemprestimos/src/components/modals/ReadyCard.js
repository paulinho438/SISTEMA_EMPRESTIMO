import {Modal, StyleSheet, View, TouchableOpacity, Image} from 'react-native';
import React from 'react';
import {useNavigation} from '@react-navigation/native';

// Local imports
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import CButton from '../common/CButton';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';

export default function ReadyCard(props) {
  let {visible, onPressClose} = props;

  const navigation = useNavigation();

  const moveToHome = () => {
    navigation.navigate(StackNav.TabNavigation);
    onPressClose();
  };

  return (
    <View style={localStyles.flex}>
      <Modal animationType={'fade'} transparent={true} visible={visible}>
        <TouchableOpacity
          style={localStyles.modalMainContainer}
          onPress={onPressClose}>
          <TouchableOpacity activeOpacity={1} onPress={onPressClose}>
            <Image source={images.PopUp} style={localStyles.imgStyle} />
            <View style={localStyles.getTxtParent}>
              <CText color={colors.black} type={'B18'}>
                {strings.GetReady}
              </CText>
              <CText color={colors.black} align={'center'}>
                {strings.ShopTransfer}
              </CText>
            </View>

            <CButton
              text={'Ok, I’m ready!'}
              ParentLoginBtn={localStyles.ParentLgnBtn}
              onPress={moveToHome}
            />
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
    width: moderateScale(290),
    height: moderateScale(337),
  },
  getTxtParent: {
    gap: moderateScale(13),
    position: 'absolute',
    top: moderateScale(150),
    ...styles.center,
    ...styles.selfCenter,
    width: '70%',
  },
  ParentLgnBtn: {
    position: 'absolute',
    width: moderateScale(250),
    height: moderateScale(56),
    bottom: moderateScale(25),
  },
});
