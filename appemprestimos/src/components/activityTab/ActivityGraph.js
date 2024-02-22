import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  View,
  Image,
  ScrollView,
} from 'react-native';
import React from 'react';
import Feather from 'react-native-vector-icons/Feather';
import Octicons from 'react-native-vector-icons/Octicons';
import FontAwesome from 'react-native-vector-icons/FontAwesome6';
import AntDesign from 'react-native-vector-icons/AntDesign';

// Local imports
import {styles} from '../../themes/index';
import {colors} from '../../themes/colors';
import {moderateScale} from '../../common/constant';
import CText from '../common/CText';
import strings from '../../i18n/strings';
import {
  VictoryAxis,
  VictoryBar,
  VictoryChart,
  VictoryGroup,
  VictoryTheme,
} from 'victory-native';
import images from '../../assets/images/index';
import CHeader from '../common/CHeader';

export default function ActivityGraph() {
  const CommonCom = ({name, source, dollars}) => {
    return (
      <View style={localStyles.mainContainer}>
        <Image source={source} style={localStyles.imgSty} />

        <View style={localStyles.InvTxt}>
          <CText type={'R12'} color={colors.tabColor}>
            {name}
          </CText>
          <CText color={colors.black} type={'B16'}>
            {dollars}
          </CText>
        </View>
      </View>
    );
  };
  const ButtonsData = ({iconName, name, dollars, color}) => {
    return (
      <TouchableOpacity style={localStyles.parentComponent}>
        <View style={localStyles.UpIconSty}>
          <FontAwesome color={color} name={iconName} size={20} />
        </View>

        <View style={localStyles.outerComponent}>
          <CText type={'R12'} color={colors.tabColor}>
            {name}
          </CText>
          <CText color={colors.black} type={'S14'}>
            {dollars}
          </CText>
        </View>
      </TouchableOpacity>
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
      <ScrollView style={styles.mh20} showsVerticalScrollIndicator={false}>
        <CHeader
          color={colors.black}
          title={'Activity'}
          rightIcon={<RightIcon />}
        />
        <View style={localStyles.parentContainer}>
          <View style={{gap: moderateScale(10)}}>
            <CText type={'M14'} color={colors.tabColor}>
              {strings.TotalSpending}
            </CText>
            <CText color={colors.black} type={'B24'}>
              {strings.OneFourNineEight}
            </CText>
          </View>

          <View style={localStyles.iconSty}>
            <CText type={'S14'} color={colors.numbersColor}>
              {strings.Month}
            </CText>
            <Octicons
              name={'chevron-down'}
              color={colors.numbersColor}
              style={localStyles.iconSty}
            />
          </View>
        </View>
        <View style={localStyles.mainGraph}>
          <VictoryChart theme={VictoryTheme.material} domainPadding={20}>
            <VictoryAxis
              tickValues={['Dec 27', 'Dec 28', 'Dec 29', 'Dec 30', 'Dec 31']}
            />
            <VictoryAxis
              dependentAxis
              tickValues={[1, 2, 3, 4]}
              tickFormat={t => `$${Math.round(t)}`}
              orientation="right"
            />

            <VictoryGroup offset={20}>
              <VictoryBar
                cornerRadius={{topLeft: 8, topRight: 8}}
                style={{data: {fill: colors.numbersColor, width: 15}}}
                animate={{
                  onExit: {
                    duration: 500,
                    before: () => ({
                      _y: 0,
                    }),
                  },
                }}
                data={[
                  {x: 'Dec 27', y: 2.1},
                  {x: 'Dec 28', y: 3.2},
                  {x: 'Dec 29', y: 3.8},
                  {x: 'Dec 30', y: 2.9},
                  {x: 'Dec 31', y: 3.3},
                ]}
              />
              <VictoryBar
                cornerRadius={{topLeft: 8, topRight: 8}}
                style={{data: {fill: colors.black, width: 15}}}
                animate={{
                  onExit: {
                    duration: 500,
                    before: () => ({
                      _y: 0,
                    }),
                  },
                }}
                data={[
                  {x: 'Dec 27', y: 2.5},
                  {x: 'Dec 28', y: 2.8},
                  {x: 'Dec 29', y: 3.4},
                  {x: 'Dec 30', y: 2.6},
                  {x: 'Dec 31', y: 2.1},
                ]}
              />
            </VictoryGroup>
          </VictoryChart>
        </View>

        <View style={localStyles.mainComponent}>
          <ButtonsData
            color={colors.numbersColor}
            iconName={'arrow-up-long'}
            name={strings.Income}
            dollars={strings.FourSixZeroZero}
          />
          <ButtonsData
            color={colors.black}
            iconName={'arrow-down-long'}
            name={strings.Expense}
            dollars={strings.OneFourNineEight}
          />
        </View>

        <View style={localStyles.outerContainer}>
          <CText color={colors.black} type={'B18'}>
            {strings.Categories}
          </CText>

          <TouchableOpacity style={localStyles.ExpenseText}>
            <CText color={colors.black} type={'M14'}>
              {strings.Expense}
            </CText>
            <AntDesign color={colors.Primary} name={'down'} size={14} />
          </TouchableOpacity>
        </View>

        <ScrollView
          showsHorizontalScrollIndicator={false}
          horizontal
          style={localStyles.scrollView}>
          <CommonCom
            source={images.Bank}
            name={strings.Investments}
            dollars={strings.FourFiveZero}
          />
          <CommonCom
            source={images.Car}
            name={strings.Traveling}
            dollars={strings.TwoSixOne}
          />
          <CommonCom
            source={images.Crown}
            name={strings.Subscriptions}
            dollars={strings.OneTwoSeven}
          />
        </ScrollView>
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
    width: moderateScale(38),
    height: moderateScale(38),
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    ...styles.p10,
    borderRadius: moderateScale(12),
  },
  parentContainer: {
    ...styles.mt25,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  iconSty: {
    backgroundColor: colors.GreyScale,
    borderRadius: moderateScale(8),
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.ph10,
    ...styles.pv8,
  },
  mainGraph: {
    ...styles.center,
  },
  UpIconSty: {
    ...styles.center,
    backgroundColor: colors.GreyScale,
    borderRadius: moderateScale(8),
    height: moderateScale(40),
    width: moderateScale(40),
  },
  parentComponent: {
    width: '48%',
    ...styles.p10,
    ...styles.flexRow,
    ...styles.alignCenter,
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    borderRadius: moderateScale(16),
  },
  outerComponent: {
    gap: moderateScale(5),
    ...styles.pl10,
  },
  mainComponent: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  outerContainer: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mv20,
  },
  imgSty: {
    width: moderateScale(34),
    height: moderateScale(34),
    ...styles.m15,
  },
  mainContainer: {
    ...styles.mh10,
    width: moderateScale(121),
    height: moderateScale(134),
    backgroundColor: colors.GreyScale,
    borderRadius: moderateScale(16),
  },
  InvTxt: {
    gap: moderateScale(5),
    ...styles.pv15,
    ...styles.pl20,
  },
  graphSty: {
    ...styles.mr20,
  },
  scrollView: {
    ...styles.mb20,
  },
  ExpenseText: {
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(5),
  },
});
