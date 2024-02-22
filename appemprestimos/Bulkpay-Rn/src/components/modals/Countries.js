import {
  StyleSheet,
  FlatList,
  Image,
  TouchableOpacity,
  View,
} from 'react-native';
import React, {useEffect, useState} from 'react';

// Local imports
import ActionSheet from 'react-native-actions-sheet';
import {colors} from '../../themes/colors';
import {CountriesData} from '../../api/constants';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes';
import CTextInput from '../common/CTextInput';
import CText from '../common/CText';
import Ionicons from 'react-native-vector-icons/Ionicons';
import MetarialIcon from 'react-native-vector-icons/MaterialIcons';
import strings from '../../i18n/strings';

export default function Countries(props) {
  const [data, setData] = useState(false);
  const [filterData, setFilterData] = useState(CountriesData);
  const [search, setSearch] = useState('');

  let {sheetRef, selectedCountry} = props;

  useEffect(() => {
    setFilterSearch();
  }, [search]);

  const setFilterSearch = () => {
    if (search) {
      const newData = CountriesData.filter(item => {
        return (
          item.FullName.toLocaleLowerCase().indexOf(
            search.toLocaleLowerCase(),
          ) > -1
        );
      });
      setFilterData(newData);
    } else {
      setFilterData(CountriesData);
    }
  };

  const onPressCancel = () => {
    sheetRef.current?.hide();
    setFilterData(CountriesData);
  };

  const onPressCountry = itm => {
    setData(itm.id);
    selectedCountry(itm);
    sheetRef.current?.hide();
  };

  const onChangeSearch = text => setSearch(text);

  const renderItems = ({item}) => {
    return (
      <View
        style={[
          localStyles.mainParent,
          {
            backgroundColor: data === item.id ? colors.GreyScale : colors.white,
          },
        ]}>
        <TouchableOpacity
          style={[
            localStyles.main,
            {
              backgroundColor:
                data === item.id ? colors.GreyScale : colors.white,
            },
          ]}
          onPress={() => onPressCountry(item)}>
          {item.svgIcon ? item.svgIcon : <Image source={{uri: item}} />}
          <CText color={colors.black} style={localStyles.title}>
            {item.title}
          </CText>

          <View style={localStyles.mainImg}>
            <CText
              color={colors.black}
              type={'B16'}
              style={localStyles.FullName}>
              {item.FullName}
            </CText>
          </View>
        </TouchableOpacity>

        {data === item.id ? (
          <MetarialIcon name={'done'} color={colors.SignUpTxt} size={24} />
        ) : null}
      </View>
    );
  };

  const searchIcon = () => (
    <Ionicons
      name={'search-outline'}
      size={moderateScale(22)}
      color={colors.black}
      style={styles.ml15}
    />
  );

  return (
    <ActionSheet
      ref={sheetRef}
      style={localStyles.main}
      containerStyle={localStyles.actionSheet}>
      <View style={localStyles.parentCTxtInp}>
        <CTextInput
          text={'Search'}
          value={search}
          onChangeText={onChangeSearch}
          mainTxtInp={localStyles.parentTxtInp}
          LeftIcon={searchIcon}
        />

        <TouchableOpacity
          style={localStyles.parentCancel}
          onPress={onPressCancel}>
          <CText color={colors.black} type={'B18'}>
            {strings.Cancel}
          </CText>
        </TouchableOpacity>
      </View>

      <FlatList
        keyExtractor={(item, index) => index.toString()}
        data={filterData}
        renderItem={renderItems}
        scrollEnabled={false}
      />
    </ActionSheet>
  );
}

const localStyles = StyleSheet.create({
  mainParent: {
    ...styles.mh15,
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    backgroundColor: colors.white,
    borderRadius: moderateScale(16),
  },
  main: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.ml15,
    height: moderateScale(64),
    width: moderateScale(300),
  },
  flagStyle: {
    width: moderateScale(32),
    height: moderateScale(24),
  },
  parentTxtInp: {
    backgroundColor: colors.GreyScale,
    width: moderateScale(258),
    ...styles.ml20,
    ...styles.mv20,
  },
  parentCTxtInp: {
    ...styles.flexRow,
    ...styles.alignCenter,
  },
  parentCancel: {
    ...styles.pl15,
  },
  title: {
    ...styles.ml20,
    color: colors.ShortName,
  },
  FullName: {
    ...styles.mh20,
    ...styles.justifyBetween,
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
  },
  checkImg: {
    width: moderateScale(24),
    height: moderateScale(24),
  },
  mainImg: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
});
