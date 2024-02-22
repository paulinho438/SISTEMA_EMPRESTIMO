import {
  StyleSheet,
  View,
  TouchableOpacity,
  Image,
  SafeAreaView,
  ScrollView,
  FlatList,
} from 'react-native';
import React from 'react';

// Local imports
import images from '../../assets/images/index';
import {styles} from '../../themes';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import {StackNav} from '../../navigation/navigationKeys';
import {HomeData} from '../../api/constants';
import CHeader from '../common/CHeader';

export default function MoreOptions({navigation}) {
  const moveToTrans = () => {
    navigation.navigate(StackNav.TransferMoney);
  };

  const moveToDeposit = () => {
    navigation.navigate(StackNav.TopUpScreen);
  };

  const moveToWith = () => {
    navigation.navigate(StackNav.WithDrawBalance);
  };

  const moveToHelp = () => {
    navigation.navigate(StackNav.ChatScreen);
  };

  const moveToAtm = () => {
    navigation.navigate(StackNav.ATMDetails);
  };

  const moveToMobile = () => {
    navigation.navigate(StackNav.SelectProvider);
  };

  const renderItems = ({item}) => {
    return (
      <TouchableOpacity style={localStyles.parentTrans}>
        <View style={localStyles.oneBox}>
          <Image
            source={item.image}
            resizeMode="cover"
            style={localStyles.GymImg}
          />
          <View style={localStyles.mainCText}>
            <CText color={colors.black} type={'B16'} style={localStyles.name}>
              {item.name}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>{item.subName}</CText>
          </View>
        </View>

        <View>
          <CText type={'B16'} color={item.color}>
            {item.dollars}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  const FirstImage = ({image, text, onPress, style}) => {
    return (
      <TouchableOpacity style={style} onPress={onPress}>
        <Image source={image} style={localStyles.childImg} />
        <CText type={'M12'} color={colors.black} style={localStyles.Txt}>
          {text}
        </CText>
      </TouchableOpacity>
    );
  };
  return (
    <SafeAreaView style={localStyles.main}>
      <ScrollView showsVerticalScrollIndicator={false} style={styles.mh20}>
        <CHeader />
        <View style={localStyles.mainImg}>
          <View style={localStyles.menuRowStyle}>
            <FirstImage
              style={localStyles.parentDep}
              image={images.Deposit}
              text={strings.Deposit}
              onPress={moveToDeposit}
            />
            <FirstImage
              style={localStyles.parentDep}
              image={images.Transfer}
              text={strings.Transfers}
              onPress={moveToTrans}
            />
            <FirstImage
              style={localStyles.parentDep}
              image={images.Withdraw}
              text={strings.Withdraw}
              onPress={moveToWith}
            />
          </View>
          <View style={localStyles.menuRowStyle2}>
            <FirstImage
              style={localStyles.parentDep2}
              image={images.Deposit}
              text={strings.ATM}
              onPress={moveToAtm}
            />
            <FirstImage
              style={localStyles.parentDep2}
              image={images.Deposit}
              text={strings.TopUp}
              onPress={moveToMobile}
            />
            <FirstImage
              style={localStyles.parentDep2}
              image={images.Transfer}
              text={strings.Help}
              onPress={moveToHelp}
            />
          </View>
        </View>

        <View style={localStyles.outerComponent}>
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={[...HomeData, ...HomeData, ...HomeData]}
            renderItem={renderItems}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  parentDep: {
    ...styles.alignCenter,
  },
  childImg: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  Txt: {
    ...styles.pt10,
  },
  mainImg: {
    ...styles.justifyCenter,
    backgroundColor: colors.GreyScale,
    ...styles.mt30,
    ...styles.ph30,
    ...styles.pv20,
    gap: moderateScale(20),
    borderRadius: moderateScale(16),
  },
  parentDep2: {
    ...styles.alignCenter,
    ...styles.mr10,
  },
  menuRowStyle: {
    ...styles.rowSpaceBetween,
  },
  menuRowStyle2: {
    ...styles.flexRow,
    ...styles.ph5,
    ...styles.justifyBetween,
  },
  parentTrans: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.pv20,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  oneBox: {
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  GymImg: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  mainCText: {
    ...styles.pl20,
  },
  name: {
    ...styles.pv5,
  },
  outerComponent: {
    ...styles.mt25,
  },
});
