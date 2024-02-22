import {
  SafeAreaView,
  StyleSheet,
  View,
  TouchableOpacity,
  Image,
  FlatList,
  ScrollView,
} from 'react-native';
import React from 'react';

// Local imports
import CHeader from '../common/CHeader';
import {colors} from '../../themes/colors';
import {styles} from '../../themes/index';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {NotificationData1, NotificationData2} from '../../api/constants';
import CText from '../common/CText';
import strings from '../../i18n/strings';

export default function Notification() {
  const renderData = ({item}) => {
    return (
      <TouchableOpacity style={localStyles.outerComponent}>
        <View style={localStyles.outerComponent}>
          <Image source={item.image} style={localStyles.iconSty} />

          <View style={localStyles.outerContainer}>
            <View style={{gap: moderateScale(3)}}>
              <CText color={colors.black} type={'B16'}>
                {item.name}
              </CText>
              <CText color={colors.tabColor} type={'R12'} numberOfLines={1}>
                {item.subName}
              </CText>
            </View>
          </View>

          <CText type={'R12'} color={colors.tabColor}>
            {item.Time}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };
  const RightIcon = () => {
    return (
      <TouchableOpacity>
        <Image source={images.RightIcon} style={localStyles.imgSty} />
      </TouchableOpacity>
    );
  };
  return (
    <SafeAreaView style={localStyles.main}>
      <SafeAreaView style={styles.mh20}>
        <CHeader
          color={colors.black}
          customStyle={localStyles.notTxt}
          title={'Notification'}
          rightIcon={<RightIcon />}
        />

        <ScrollView showsVerticalScrollIndicator={false}>
          <CText
            style={localStyles.TxtSty}
            type={'B16'}
            color={colors.tabColor}>
            {strings.TodayNot}
          </CText>

          <FlatList   keyExtractor={(item, index) => index.toString()} data={NotificationData1} renderItem={renderData} />

          <View style={localStyles.flatListDataStyle}>
            <CText type={'B16'} color={colors.tabColor}>
              {strings.ThisWeek}
            </CText>

            <FlatList
              keyExtractor={(item, index) => index.toString()}
              contentContainerStyle={styles.pv20}
              data={[...NotificationData2, ...NotificationData2]}
              renderItem={renderData}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  imgSty: {
    width: moderateScale(40),
    height: moderateScale(40),
  },
  notTxt: {
    ...styles.ml80,
  },
  iconSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  outerComponent: {
    ...styles.mv15,
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(10),
  },
  TxtSty: {
    ...styles.mt10,
  },
  outerContainer: {
    ...styles.flex,
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  flatListDataStyle: {
    ...styles.mv50,
  },
});
