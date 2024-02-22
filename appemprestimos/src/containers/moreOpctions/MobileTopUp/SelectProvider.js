import {
  FlatList,
  SafeAreaView,
  StyleSheet,
  Image,
  View,
  TouchableOpacity,
} from 'react-native';
import React, {useRef, useState} from 'react';
import AntDesign from 'react-native-vector-icons/AntDesign';
import Ionicons from 'react-native-vector-icons/Ionicons';

// Local imports
import CHeader from '../../../components/common/CHeader';
import {styles} from '../../../themes/index';
import CText from '../../../components/common/CText';
import {colors} from '../../../themes/colors';
import {TransferData, TransferData2} from '../../../api/constants';
import {moderateScale} from '../../../common/constant';
import strings from '../../../i18n/strings';
import CButton from '../../../components/common/CButton';
import TopUpModal from '../../../components/modals/TopUpModal';

export default function SelectProvider() {
  const amount = useRef(null);
  const [selectedItem, setSelectedItem] = useState('');

  const valueChange = item => {
    setSelectedItem(item);
  };

  const openModal = () => {
    amount.current.show();
  };

  const renderData = ({item}) => {
    return (
      <TouchableOpacity onPress={() => valueChange(item)}>
        <View style={localStyles.mainContainer}>
          <View style={localStyles.outerComponent}>
            <Image style={localStyles.imgSty} source={item.image} />

            <View style={{gap: moderateScale(4)}}>
              <CText color={colors.black} type={'S16'}>
                {item.name}
              </CText>
              <CText type={'M12'} color={colors.tabColor}>
                {item.num}
              </CText>
            </View>
          </View>

          <View style={localStyles.iconSty}>
            {selectedItem.id === item.id ? (
              <AntDesign
                color={colors.Primary}
                name={'checkcircle'}
                size={24}
              />
            ) : (
              <Ionicons
                name={'radio-button-off'}
                size={24}
                color={colors.google}
              />
            )}
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerContainer}>
        <View>
          <CHeader color={colors.black} title={'Top Up'} />
          <CText style={styles.mt15} color={colors.black} type={'B18'}>
            {strings.BankTrans}
          </CText>

          <FlatList
            keyExtractor={(item, index) => index.toString()}
            scrollEnabled={false}
            data={TransferData}
            renderItem={renderData}
          />

          <CText color={colors.black} style={localStyles.OtherTxt} type={'B18'}>
            {strings.Other}
          </CText>
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            scrollEnabled={false}
            data={TransferData2}
            renderItem={renderData}
          />

          <TopUpModal sheetRef={amount} paymentDetail={selectedItem} />
        </View>
        <CButton
          containerStyle={localStyles.CommonButtonStyle}
          text={'Confirm'}
          onPress={openModal}
        />
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  imgSty: {
    width: moderateScale(48),
    height: moderateScale(48),
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
    ...styles.mv5,
  },
  outerComponent: {
    gap: moderateScale(10),
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  iconSty: {
    ...styles.ph20,
  },
  OtherTxt: {
    ...styles.mv5,
  },
  outerContainer: {
    ...styles.mh20,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  CommonButtonStyle: {
    ...styles.mb30,
  },
});
