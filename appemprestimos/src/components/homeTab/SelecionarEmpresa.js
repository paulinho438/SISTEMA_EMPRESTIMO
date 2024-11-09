import {
  StyleSheet,
  SafeAreaView,
  Image,
  View,
  ScrollView,
  TouchableOpacity,
  FlatList,
  Alert,
} from 'react-native';
import React, {useEffect, useState} from 'react';

// Local imports
import {styles} from '../../themes';
import CHeader from '../common/CHeader';
import strings from '../../i18n/strings';
import CText from '../common/CText';
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import CTextInput from '../../components/common/CTextInput';
import Ionicons from 'react-native-vector-icons/Ionicons';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import typography from '../../themes/typography';
import {ContactsData} from '../../api/constants';
import MetarialIcon from 'react-native-vector-icons/MaterialIcons';
import {StackNav} from '../../navigation/navigationKeys';

import {authCompany, getPermissions, permissions} from '../../utils/asyncStorage';

export default function TransferMoney({navigation, route}) {
  const { companies } = route.params;
  const [selectedCompany, setSelectedCompany] = useState(null);
  const [data, setData] = useState(1);
  const [filterData, setFilterData] = useState(ContactsData);
  const [search, setSearch] = useState('');

  useEffect(() => {
  }, [search]);

  const avancarHome = async () => {
    await authCompany(selectedCompany);

    let beforePermissions = await getPermissions();

    const companyPermissions = beforePermissions.find(p => p.company_id === selectedCompany.id)?.permissions || [];

    await permissions(companyPermissions);

    navigation.navigate(StackNav.TabNavigation);
  };

  const selecionarEmpresa = (company) => {
    setSelectedCompany(company)
  }

  const Cards = ({companyInfo}) => {
    return (
      <TouchableOpacity style={[
        localStyles.ParentImg, 
      ]}  onPress={() => selecionarEmpresa(companyInfo)}>
        <Image source={images.cardBalance} style={[localStyles.card3Style, (companyInfo.id == selectedCompany?.id) ? localStyles.Selected : null]} />
        <View style={localStyles.parentNomeEmpresa}>
          <CText
              color={colors.white}
              type={'B18'}
              style={localStyles.NameEmpresa}>
              {companyInfo?.company}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.mainContainer}>
        <ScrollView>
          <CHeader color={colors.black} title='Empresas' />
          <CText color={colors.black} type={'B18'} style={localStyles.CardTxt}>
          Escolha uma empresa
          </CText>
          <View style={localStyles.ImgParent}>
            <ScrollView horizontal showsHorizontalScrollIndicator={false}>
              {companies.map((company, index) => (
                <Cards key={index} companyInfo={company}  />
              ))}
            </ScrollView>
          </View>
          
        </ScrollView>
      </View>
      {selectedCompany && <CButton containerStyle={localStyles.ContBtn} onPress={avancarHome} /> }
    </SafeAreaView>
  );
}

const localStyles = StyleSheet.create({
  main: {
    backgroundColor: colors.white,
    ...styles.flex,
    ...styles.justifyBetween,
  },
  mainContainer: {
    ...styles.ph20,
    ...styles.justifyBetween,
    ...styles.flex,
  },
  CardTxt: {
    ...styles.mt15,
  },
  cardImg1: {
    width: moderateScale(310),
    height: moderateScale(180),
    ...styles.mr10,
    ...styles.mv30,
  },
  ImgParent: {
    ...styles.flexRow,
  },
  girlSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  parentGirl: {
    width: moderateScale(130),
    height: moderateScale(154),
    ...styles.center,
    ...styles.mh5,
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
  },
  mainBoyGirl: {
    ...styles.flexRow,
    ...styles.justifyBetween,
  },
  CTxtInp: {
    ...styles.mv10,
  },
  GirlNameTxt: {
    ...styles.ph20,
    ...styles.pv10,
  },
  ContBtn: {
    ...styles.mb20,
    width: '90%',
  },
  imgSty: {
    width: moderateScale(48),
    height: moderateScale(48),
  },
  doneIcon: {
    ...styles.selfEnd,
    ...styles.mr10,
  },
  ParentImg: {
    ...styles.center,
    top: moderateScale(23),
    marginRight: moderateScale(2),
    position: 'relative',
  },
  card3Style: {
    width: moderateScale(327),
    height: moderateScale(190),
    opacity: 0.6,

  },
  NameEmpresa: {
    ...styles.mt10,
  },
  parentNomeEmpresa: {
    ...styles.flexRow,
    top:moderateScale(-50),
    width: moderateScale(300),
  },
  childTxtInp: {
    ...typography.fontSizes.f32,
    ...typography.fontWeights.Bold,
    textAlign: 'center',
    marginTop: moderateScale(20),
    ...styles.pr15,
  },
  Selected: {
    opacity: null
  },
});
