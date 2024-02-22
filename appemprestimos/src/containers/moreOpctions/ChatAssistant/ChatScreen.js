import {
  SafeAreaView,
  StyleSheet,
  TouchableOpacity,
  Image,
  FlatList,
} from 'react-native';
import React, {memo} from 'react';
import Material from 'react-native-vector-icons/MaterialIcons';
import MaterialIcons from 'react-native-vector-icons/MaterialCommunityIcons';
import Feather from 'react-native-vector-icons/Feather';

// Local imports
import {styles} from '../../../themes/index';
import {colors} from '../../../themes/colors';
import {moderateScale} from '../../../common/constant';
import CText from '../../../components/common/CText';
import strings from '../../../i18n/strings';
import {View} from 'react-native';
import images from '../../../assets/images/index';
import {ChatData} from '../../../api/constants';
import CTextInput from '../../../components/common/CTextInput';
import KeyBoardAvoidWrapper from '../../../components/common/KeyBoardAvoidWrapper';
import {StackNav} from '../../../navigation/navigationKeys';

export default function ChatScreen({navigation}) {
  const backToMore = () => {
    navigation.navigate(StackNav.MoreOptions);
  };

  const RenderData = memo(({item}) => {
    return (
      <View
        style={[
          localStyles.senderContainer,
          item.type == 'sender' && {
            maxWidth: item.type == 'sender' && '60%',
            borderBottomLeftRadius: moderateScale(16),
            borderTopLeftRadius: item.type == 'sender' && moderateScale(16),
            borderBottomRightRadius: item.type == 'sender' && moderateScale(16),
            backgroundColor: item.type == 'sender' && colors.black,
            alignSelf: item.type == 'sender' ? 'flex-end' : 'flex-start',
          },

          item.type !== 'sender' && {
            maxWidth: item.type !== 'sender' && '85%',
            backgroundColor: item.type !== 'sender' && colors.GreyScale,
            borderBottomRightRadius: moderateScale(16),
            borderTopRightRadius: moderateScale(16),
            borderBottomLeftRadius: moderateScale(16),
          },
        ]}>
        <CText
          style={styles.flex}
          color={item.type == 'sender' ? colors.white : colors.black}
          type="m16">
          {item.message}
        </CText>
      </View>
    );
  });
  return (
    <SafeAreaView style={localStyles.mainComponent}>
      <KeyBoardAvoidWrapper contentContainerStyle={styles.flexGrow1}>
        <View>
          <SafeAreaView style={localStyles.outerComponent}>
            <TouchableOpacity
              onPress={backToMore}
              style={localStyles.parentMaterial}>
              <Material
                name={'arrow-back-ios'}
                size={24}
                color={colors.white}
                style={localStyles.vectorSty}
              />
            </TouchableOpacity>
          </SafeAreaView>

          <View style={localStyles.parentComponent}>
            <CText type={'B18'} color={colors.white}>
              {strings.SmartyHelp}
            </CText>
          </View>
        </View>

        <View style={localStyles.main}>
          <Image source={images.Robot} style={localStyles.imgSty} />
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={ChatData}
            renderItem={({item}) => <RenderData item={item} />}
          />

          <View style={localStyles.mainContainer}>
            <TouchableOpacity>
              <View style={localStyles.iconSty}>
                <MaterialIcons
                  size={24}
                  color={colors.tabColor}
                  style={localStyles.childSty}
                  name={'link-variant'}
                />
              </View>
            </TouchableOpacity>

            <CTextInput
              text={'Type here...'}
              mainTxtInp={localStyles.mainTxtInp}
              RightIcon={() => (
                <View>
                  <TouchableOpacity>
                    <Feather
                      name={'chevrons-right'}
                      style={localStyles.iconStyle}
                      size={30}
                      color={colors.Primary}
                    />
                  </TouchableOpacity>
                </View>
              )}
            />
          </View>
        </View>
      </KeyBoardAvoidWrapper>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.justifyBetween,
    height: '92%',
    ...styles.ph20,
  },
  mainComponent: {
    ...styles.flex,
    backgroundColor: colors.black,
  },
  outerComponent: {
    ...styles.mh20,
    ...styles.mv10,
  },
  parentComponent: {
    position: 'absolute',
    top: moderateScale(25),
    ...styles.selfCenter,
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  parentMaterial: {
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(12),
    borderColor: colors.google,
    width: moderateScale(40),
    height: moderateScale(40),
    ...styles.alignCenter,
    ...styles.justifyCenter,
    ...styles.pl10,
    ...styles.mv10,
  },
  imgSty: {
    ...styles.mt20,
    width: moderateScale(48),
    height: moderateScale(48),
  },
  senderContainer: {
    ...styles.p15,
    ...styles.flexRow,
    ...styles.mv15,
  },
  mainTxtInp: {
    backgroundColor: colors.GreyScale,
    width: moderateScale(259),
  },
  iconSty: {
    borderRadius: moderateScale(16),
    backgroundColor: colors.GreyScale,
    width: moderateScale(56),
    height: moderateScale(56),
    ...styles.center,
  },
  mainContainer: {
    ...styles.mb40,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.center,
    gap: moderateScale(12),
  },
  iconStyle: {
    ...styles.pr10,
  },
});
