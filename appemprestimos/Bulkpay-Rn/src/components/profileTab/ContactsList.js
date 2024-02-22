import {
  FlatList,
  SafeAreaView,
  StyleSheet,
  View,
  Image,
  TouchableOpacity,
} from 'react-native';
import React, {useEffect, useState} from 'react';
import EvilIcons from 'react-native-vector-icons/EvilIcons';

// Local imports
import CHeader from '../common/CHeader';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CTextInput from '../common/CTextInput';
import {colors} from '../../themes/colors';
import typography from '../../themes/typography';
import CText from '../common/CText';
import {moderateScale} from '../../common/constant';
import {contactList} from '../../api/constants';

const ListHeaderComponent = () => {
  return (
    <View>
      <CText type={'B18'} color={colors.tabColor}>
        {strings.RecentContacts}
      </CText>

      <View style={styles.mt15}>
        <FlatList   keyExtractor={(item, index) => index.toString()} data={contactList.slice(0, 3)} renderItem={renderData} />
      </View>
      <View style={localStyles.line} />

      <CText type={'B18'} color={colors.tabColor}>
        {strings.AllContacts}
      </CText>
    </View>
  );
};

const renderData = ({item}) => {
  return (
    <View>
      <TouchableOpacity style={localStyles.outerContainer}>
        <Image source={item.image} style={localStyles.imgSty} />

        <View>
          <CText color={colors.black} type={'S16'}>
            {item.name}
          </CText>
          <CText type={'R14'} color={colors.tabColor}>
            {item.number}
          </CText>
        </View>
      </TouchableOpacity>
    </View>
  );
};

export default function ContactsList() {
  const [filterData, setFilterData] = useState(contactList);
  const [search, setSearch] = useState('');
  const [showSearchResult, setShowSearchResult] = useState(false);

  useEffect(() => {
    if (search.length > 0) {
      SearchPerson();
    } else {
      setShowSearchResult(false);
    }
  }, [search]);

  const SearchPerson = () => {
    const newData = contactList.filter(item => {
      return (
        item.name.toLocaleLowerCase().indexOf(search.toLocaleLowerCase()) > -1
      );
    });
    setFilterData(newData);
    setShowSearchResult(true);
  };

  const onchangeTxt = txt => {
    setSearch(txt);
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.innerContainer}>
        <CHeader color={colors.black} title={'Contacts'} />

        <CTextInput
          mainTxtInp={localStyles.outerComponent}
          value={search}
          onChangeText={onchangeTxt}
          textInputStyle={localStyles.CTxtInp}
          text={'Search'}
          LeftIcon={() => (
            <EvilIcons color={colors.black} name={'search'} size={35} />
          )}
        />
        {!showSearchResult ? (
          <FlatList
          keyExtractor={(item, index) => index.toString()}
            data={contactList}
            renderItem={renderData}
            showsVerticalScrollIndicator={false}
            ListHeaderComponent={<ListHeaderComponent />}
          />
        ) : (
          <FlatList
          keyExtractor={(item, index) => index.toString()}
            showsVerticalScrollIndicator={false}
            data={filterData}
            renderItem={renderData}
          />
        )}
      </View>
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
  },
  innerContainer: {
    ...styles.mh20,
    ...styles.flex,
  },
  outerComponent: {
    ...styles.ph15,
    backgroundColor: colors.GreyScale,
    ...styles.mv20,
  },
  CTxtInp: {
    ...styles.pl10,
    ...typography.fontSizes.f16,
    ...typography.fontWeights.Regular,
  },
  imgSty: {
    width: moderateScale(56),
    height: moderateScale(56),
  },
  outerContainer: {
    ...styles.mv15,
    ...styles.flexRow,
    ...styles.alignCenter,
    gap: moderateScale(15),
  },
  line: {
    ...styles.mv30,
    borderBottomWidth: moderateScale(1),
    borderBottomColor: colors.bottomBorder,
  },
});
