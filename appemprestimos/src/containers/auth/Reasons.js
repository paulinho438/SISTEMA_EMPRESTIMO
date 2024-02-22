import {
  StyleSheet,
  SafeAreaView,
  View,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import React, {useState} from 'react';

// Local imports
import CBackButton from '../../components/common/CBackButton';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {moderateScale} from '../../common/constant';
import {colors} from '../../themes/colors';
import CButton from '../../components/common/CButton';
import {AuthNav} from '../../navigation/navigationKeys';
import {ReasonsData} from '../../api/constants';

export default function Reasons({navigation}) {
  const [selectedData, setSelectedData] = useState(ReasonsData);

  const ontoggle = item => {
    const newItem = selectedData.map(value => {
      if (value.id === item.id) {
        return {...value, selected: !value.selected};
      } else {
        return value;
      }
    });
    setSelectedData(newItem);
  };

  const moveToCreatePin = () => {
    navigation.navigate(AuthNav.CreatePin);
  };

  const backToCountry = () => {
    navigation.navigate(AuthNav.CountryRes);
  };

  const CommonComponent = ({item}) => {
    return (
      <TouchableOpacity
        style={[
          localStyles.BoltMain,
          {
            backgroundColor: item.selected ? colors.black : colors.white,
          },
        ]}
        onPress={() => ontoggle(item)}>
        <CText
          style={[
            localStyles.iconStyle,
            {
              color: item.selected ? colors.white : colors.numbersColor,
            },
          ]}>
          {item.svgIcon}
        </CText>
        <CText
          color={colors.black}
          type={'B14'}
          style={[
            localStyles.fastTransTxt,
            {
              color: item.selected ? colors.white : colors.black,
            },
          ]}>
          {item.name}
        </CText>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.outerContainer}>
        <View>
          <CBackButton onPress={backToCountry} />
          <CText
            color={colors.black}
            type={'B24'}
            style={localStyles.reasonsTxt}>
            {strings.MainReason}
          </CText>
          <CText color={colors.black} style={localStyles.knowReasonTxt}>
            {strings.KnowReasons}
          </CText>
          <View style={localStyles.mainBoxes}>
            <FlatList
              keyExtractor={(item, index) => index.toString()}
              numColumns={2}
              key={2}
              data={selectedData}
              renderItem={CommonComponent}
            />
          </View>
        </View>

        <CButton
          containerStyle={localStyles.CButtonMain}
          onPress={moveToCreatePin}
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
  reasonsTxt: {
    ...styles.mt30,
  },
  knowReasonTxt: {
    ...styles.mt15,
  },
  BoltMain: {
    width: moderateScale(156),
    height: moderateScale(108),
    borderRadius: moderateScale(16),
    ...styles.mv10,
    ...styles.flex,
    ...styles.mh10,
    ...styles.justifyBetween,
    borderWidth: moderateScale(1),
    borderColor: colors.google,
  },
  mainBoxes: {
    ...styles.justifyBetween,
    ...styles.mt10,
  },
  spendDailyTxt: {
    width: moderateScale(100),
    ...styles.pl20,
  },
  fastTransTxt: {
    ...styles.ph20,
    ...styles.mb15,
  },
  CButtonMain: {
    ...styles.mv25,
  },
  imgSty: {
    ...styles.p10,
  },
  iconStyle: {
    ...styles.p15,
    color: colors.red,
  },
  outerContainer: {
    ...styles.flex,
    ...styles.justifyBetween,
    ...styles.ph20,
  },
});
