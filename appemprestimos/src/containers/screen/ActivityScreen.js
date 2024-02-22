import {
  StyleSheet,
  Image,
  View,
  SafeAreaView,
  TouchableOpacity,
  FlatList,
  ScrollView,
} from 'react-native';
import React, {useCallback, useState} from 'react';
import Feather from 'react-native-vector-icons/Feather';
import Octicons from 'react-native-vector-icons/Octicons';

// Local imports
import CHeader from '../../components/common/CHeader';
import {moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import {styles} from '../../themes';
import {TimeData, miniCardDetails} from '../../api/constants';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {VictoryAxis, VictoryChart, VictoryLine} from 'victory-native';
import images from '../../assets/images/index';
import {StackNav} from '../../navigation/navigationKeys';

export default function MyCardScreen({navigation}) {
  const [OnBoardingDetails, setOnBoardingDetails] = useState(0);
  const [data, setData] = useState(1);

  const _viewabilityConfig = {itemVisiblePercentThreshold: 50};

  const _onViewableItemsChanged = useCallback(({viewableItems}) => {
    setOnBoardingDetails(viewableItems[0]?.index);
  }, []);

  const onChangeColor = item => {
    setData(item);
  };

  const moveToGraph = () => {
    navigation.navigate(StackNav.ActivityGraph);
  };

  const renderTimeData = ({item}) => {
    return (
      <TouchableOpacity
        style={[
          localStyles.timeSty,
          {
            borderRadius: moderateScale(8),
            backgroundColor: data === item.id ? colors.GreyScale : null,
          },
        ]}
        onPress={() => onChangeColor(item.id)}>
        <CText
          style={[
            {
              color: data === item.id ? colors.numbersColor : colors.black,
            },
          ]}>
          {item.name}
        </CText>
      </TouchableOpacity>
    );
  };

  const TransHis = ({source, name, subName, dollars, onPress}) => {
    return (
      <TouchableOpacity onPress={onPress} style={localStyles.outerContainer}>
        <Image source={source} style={localStyles.iconSty} />

        <View style={localStyles.parentContainer}>
          <View style={{gap: moderateScale(5)}}>
            <CText color={colors.black} type={'B14'}>
              {name}
            </CText>
            <CText type={'M12'} color={colors.tabColor}>
              {subName}
            </CText>
          </View>

          <CText color={colors.black} type={'B14'}>
            {dollars}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };
  const renderCard = ({item}) => {
    return (
      <View backgroundColor={item.backgroundColor} style={localStyles.cardSty}>
        <CText color={item.color} type={'M14'}>
          {item.name}
        </CText>
        <CText color={item.color} type={'M14'}>
          {item.number}
        </CText>
        <Image source={item.image} style={localStyles.imgSty} />
      </View>
    );
  };

  const RightIcon = () => {
    return (
      <TouchableOpacity style={localStyles.parentMore}>
        <Feather color={colors.black} name={'more-horizontal'} size={20} />
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <ScrollView showsVerticalScrollIndicator={false}>
        <View style={styles.mh20}>
          <CHeader
            customStyle={localStyles.headerSty}
            color={colors.black}
            title={'Activity'}
            rightIcon={<RightIcon />}
          />
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={miniCardDetails}
            renderItem={renderCard}
            viewabilityConfig={_viewabilityConfig}
            onViewableItemsChanged={_onViewableItemsChanged}
            pagingEnabled
            horizontal
            showsHorizontalScrollIndicator={false}
          />

          <View style={styles.rowCenter}>
            {miniCardDetails.map((item, index) => (
              <View
                style={[
                  localStyles.IndicatorStyle,
                  {
                    backgroundColor:
                      index !== OnBoardingDetails
                        ? colors.silver
                        : colors.black,
                  },
                ]}
              />
            ))}
          </View>
        </View>

        <View style={localStyles.mainBorder}>
          <View style={localStyles.mainTotal}>
            <CText
              color={colors.tabColor}
              style={localStyles.TotalSpendTxt}
              align={'center'}
              type={'M14'}>
              {strings.TotalSpend}
            </CText>
            <CText color={colors.black} type={'B24'} align={'center'}>
              {strings.TotalDollars}
            </CText>
          </View>

          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={TimeData}
            renderItem={renderTimeData}
            horizontal
          />

          <VictoryChart minDomain={{y: 1}}>
            <VictoryAxis />
            <VictoryLine
              style={{
                data: {
                  stroke: colors.numbersColor,
                  strokeWidth: moderateScale(3),
                },
              }}
              data={[
                {x: 'S', y: 70},
                {x: 'M', y: 60},
                {x: 'T', y: 45},
                {x: 'W', y: 65},
                {x: 'T', y: 45},
                {x: 'F', y: 70},
                {x: 'S', y: 60},
              ]}
            />
          </VictoryChart>
        </View>

        <View style={styles.mh20}>
          <View style={localStyles.parentComponent}>
            <CText color={colors.black} type={'B18'}>
              {strings.Transaction}
            </CText>

            <TouchableOpacity style={localStyles.outerComponent}>
              <CText color={colors.black} type={'M14'}>
                {strings.All}
              </CText>
              <Octicons
                name={'chevron-down'}
                color={colors.numbersColor}
                size={14}
              />
            </TouchableOpacity>
          </View>

          <View style={localStyles.TransactionHistory}>
            <TransHis
              onPress={moveToGraph}
              source={images.UiKit}
              name={strings.BulkPayUi}
              subName={strings.UiNet}
              dollars={strings.NineNine}
            />
            <TransHis
              onPress={moveToGraph}
              source={images.Gym}
              name={strings.Gym}
              subName={strings.Payment}
              dollars={strings.FourFive}
            />
            <TransHis
              onPress={moveToGraph}
              source={images.BitCoin}
              name={strings.Bitcoin}
              subName={strings.Deposit}
              dollars={strings.TwoFiveFIveZero}
            />
          </View>
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
  parentMore: {
    height: moderateScale(40),
    width: moderateScale(42),
    borderWidth: moderateScale(1),
    borderColor: colors.google,
    ...styles.p10,
    borderRadius: moderateScale(12),
  },
  cardSty: {
    width: moderateScale(315),
    height: moderateScale(64),
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.m5,
    ...styles.mt20,
    ...styles.p10,
    borderRadius: moderateScale(16),
  },
  imgSty: {
    width: moderateScale(40),
    height: moderateScale(24),
  },
  IndicatorStyle: {
    height: moderateScale(8),
    borderRadius: moderateScale(10),
    ...styles.mh5,
    ...styles.mt10,
    width: moderateScale(8),
  },
  TotalSpendTxt: {
    ...styles.mt30,
  },
  mainTotal: {
    gap: moderateScale(10),
  },
  mainBorder: {
    ...styles.mt40,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.GreyScale,
  },
  parentComponent: {
    ...styles.mv10,
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  outerComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(5),
  },
  iconSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  outerContainer: {
    gap: moderateScale(15),
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  parentContainer: {
    ...styles.flexRow,
    ...styles.flex,
    ...styles.justifyBetween,
    ...styles.alignCenter,
  },
  timeSty: {
    backgroundColor: colors.red,
    ...styles.flexRow,
    ...styles.mh19,
    ...styles.mt25,
    ...styles.p10,
  },
  headerSty: {
    ...styles.mh0,
    ...styles.pl25,
  },
  TransactionHistory: {
    gap: moderateScale(15),
    ...styles.mv25,
  },
});
