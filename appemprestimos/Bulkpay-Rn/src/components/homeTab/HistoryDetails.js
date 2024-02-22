import {
  SafeAreaView,
  StyleSheet,
  View,
  TouchableOpacity,
  Image,
  FlatList,
} from 'react-native';
import React from 'react';
import Feather from 'react-native-vector-icons/Feather';
import Octicons from 'react-native-vector-icons/Octicons';
import {
  VictoryChart,
  VictoryBar,
  VictoryTheme,
  VictoryAxis,
} from 'victory-native';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import images from '../../assets/images/index';
import {SpotifyData} from '../../api/constants';

const HeaderComponent = () => {
  const RightIcon = () => {
    return (
      <TouchableOpacity style={localStyles.parentMore}>
        <Feather color={colors.black} name={'more-horizontal'} size={20} />
      </TouchableOpacity>
    );
  };
  return (
    <View>
      <CHeader
        color={colors.black}
        title={'Spotify'}
        customStyle={localStyles.SpotifyText}
        rightIcon={<RightIcon />}
      />
      <View style={localStyles.mainView}>
        <View style={localStyles.mainPayment}>
          <CText color={colors.black} type={'B24'}>
            {strings.Amount}
          </CText>
          <CText>
            <CText color={colors.red}>{strings.ThreeFive}</CText>
            {strings.ago}
          </CText>
        </View>

        <View style={localStyles.outerComponent}>
          <Image source={images.graph} style={localStyles.imgSty} />
          <Image source={images.graph2} style={localStyles.imgSty} />
        </View>
      </View>

      <View style={styles.center}>
        <VictoryChart theme={VictoryTheme.material} domainPadding={20}>
          <VictoryAxis
            tickValues={['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']}
          />
          <VictoryAxis
            dependentAxis
            tickValues={[0, 30, 50, 70, 100]}
            tickFormat={t => `$${Math.round(t)}`}
            orientation="right"
          />
          <VictoryBar
            cornerRadius={{topLeft: 8, topRight: 8}}
            style={{data: {fill: colors.numbersColor, width: 24}}}
            animate={{
              onExit: {
                duration: 500,
                before: () => ({
                  _y: 0,
                }),
              },
            }}
            data={[
              {x: 'Jul', y: 55},
              {x: 'Aug', y: 40},
              {x: 'Sep', y: 65},
              {x: 'Oct', y: 55},
              {x: 'Nov', y: 85},
              {x: 'Dec', y: 35},
            ]}
          />
        </VictoryChart>
      </View>

      <View style={localStyles.outerContainer}>
        <CText color={colors.black} type={'B18'}>
          {strings.AllTrans}
        </CText>
        <Octicons name={'search'} color={colors.numbersColor} size={24} />
      </View>
    </View>
  );
};

export default function HistoryDetails() {
  const renderSpotify = ({item}) => {
    return (
      <View>
        <TouchableOpacity style={localStyles.outerImgTxt}>
          <Image source={item.image} style={localStyles.UiKitSty} />
          <View style={localStyles.outerCOntainer}>
            <View style={localStyles.parentUi}>
              <CText color={colors.black} type={'B14'}>
                {item.mainName}
              </CText>
              <CText type={'M12'} color={colors.tabColor}>
                {item.subName}
              </CText>
            </View>

            <View style={localStyles.viewOfPayment}>
              <CText
                style={localStyles.dateTxt}
                color={colors.black}
                type={'B14'}>
                {item.payments}
              </CText>
              <CText color={colors.tabColor}>{item.date}</CText>
            </View>
          </View>
        </TouchableOpacity>
        <View style={localStyles.forBorder}></View>
      </View>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <FlatList
        keyExtractor={(item, index) => index.toString()}
        ListHeaderComponent={<HeaderComponent />}
        data={SpotifyData}
        renderItem={renderSpotify}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={localStyles.listStyle}
      />
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    ...styles.flex,
    backgroundColor: colors.white,
  },
  listStyle: {
    ...styles.ph20,
  },
  parentMore: {
    height: moderateScale(38),
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    ...styles.p10,
    borderRadius: moderateScale(12),
  },
  spotifyTxt: {
    ...styles.ph20,
  },
  mainPayment: {
    ...styles.mv20,
    gap: moderateScale(5),
  },
  imgSty: {
    width: moderateScale(32),
    height: moderateScale(32),
  },
  parentImages: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  mainView: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.alignCenter,
  },
  outerComponent: {
    ...styles.flexRow,
    gap: moderateScale(10),
  },
  container: {
    ...styles.flex,
    ...styles.justifyCenter,
    ...styles.alignCenter,
  },
  outerContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  outerImgTxt: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.mv20,
    gap: moderateScale(12),
  },
  UiKitSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  outerCOntainer: {
    ...styles.flex,
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  parentUi: {
    gap: moderateScale(5),
  },
  forBorder: {
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
  viewOfPayment: {
    gap: moderateScale(5),
    alignItems: 'flex-end',
  },
  SpotifyText: {
    ...styles.ml80,
  },
});
