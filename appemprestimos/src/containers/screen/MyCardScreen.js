import {
  FlatList,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  View,
} from 'react-native';
import React from 'react';
import Ionicons from 'react-native-vector-icons/Ionicons';

// Local imports
import CHeader from '../../components/common/CHeader';
import {styles} from '../../themes';
import CText from '../../components/common/CText';
import strings from '../../i18n/strings';
import {CommonBOA} from '../../components/common/CommonBOA';
import images from '../../assets/images/index';
import {colors} from '../../themes/colors';
import {deviceWidth, moderateScale} from '../../common/constant';
import CAddNewBank from '../../components/common/CAddNewBank';
import {StackNav} from '../../navigation/navigationKeys';
import {BankList} from '../../api/constants';

export default function MyCardScreen({navigation}) {
  const moveToSee = item => {
    navigation.navigate(StackNav.SeeMyCard, {item: item});
  };

  const RenderBankCard = ({item}) => {
    return (
      <ScrollView
        bounces={false}
        horizontal
        showsHorizontalScrollIndicator={false}>
        <CommonBOA
          ParentContainer={localStyles.mainContainer}
          source={images.BlueTick}
          item={item}
          onPress={() => {
            moveToSee(item);
          }}
        />

        <View style={localStyles.popUp}>
          <TouchableOpacity style={localStyles.parentTrash}>
            <Ionicons name={'trash-outline'} size={24} color={colors.tomato} />
          </TouchableOpacity>
        </View>
      </ScrollView>
    );
  };

  const ListFooterComponent = () => {
    return <CAddNewBank newBankSty={localStyles.newBank} />;
  };

  const ListHeaderComponent = () => {
    return (
      <CText
        color={colors.black}
        style={localStyles.YourBankAccTxt}
        type={'B24'}>
        {strings.YourBankAcc}
      </CText>
    );
  };
  return (
    <SafeAreaView style={localStyles.main}>
      <View style={styles.ph20}>
        <CHeader color={colors.black} title={'My Bank'} />
        <FlatList
          keyExtractor={(item, index) => index.toString()}
          data={BankList}
          renderItem={RenderBankCard}
          showsHorizontalScrollIndicator={false}
          ListHeaderComponent={<ListHeaderComponent />}
          ListFooterComponent={<ListFooterComponent />}
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
  YourBankAccTxt: {
    ...styles.mv30,
  },
  imgSty: {
    ...styles.mv0,
    width: deviceWidth - moderateScale(40),
  },
  parentTrash: {
    backgroundColor: colors.secondary,
    ...styles.flex,
    ...styles.center,
    ...styles.m10,
    borderRadius: moderateScale(16),
  },
  popUp: {
    width: deviceWidth - moderateScale(305),
  },
  mainContainer: {
    borderWidth: moderateScale(1),
    borderColor: colors.bottomBorder,
    width: deviceWidth - moderateScale(40),
    backgroundColor: colors.white,
  },
  newBank: {
    ...styles.mh0,
    backgroundColor: colors.GreyScale,
  },
});
