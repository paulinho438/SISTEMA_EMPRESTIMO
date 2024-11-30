import {
  StyleSheet,
  SafeAreaView,
  Image,
  View,
  ScrollView,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import React, {useEffect, useState} from 'react';

import { useFocusEffect } from '@react-navigation/native';

// Local imports
import {styles} from '../../themes';
import CHeader from '../common/CHeader';
import strings from '../../i18n/strings';
import CText from '../common/CText';
import Images from '../../assets/images';
import {moderateScale} from '../../common/constant';
import CTextInput from '../../components/common/CTextInput';
import Ionicons from 'react-native-vector-icons/Ionicons';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {ContactsData} from '../../api/constants';
import MetarialIcon from 'react-native-vector-icons/MaterialIcons';
import {StackNav, AuthNav} from '../../navigation/navigationKeys';
import api from '../../services/api';

export default function TransferMoney({navigation}) {
  const [data, setData] = useState(1);
  const [filterData, setFilterData] = useState([]);
  const [search, setSearch] = useState('');
  const [clientes, setClientes] = useState([]);
  const [clienteSelecionado, setClienteSelecionado] = useState(null);


  useFocusEffect(
    React.useCallback(() => {
      setFilterSearch();
    }, [search])
  );

  useFocusEffect(
    React.useCallback(() => {
      buscarClientes();
      return () => {
      };
    }, [])
  );

  const buscarClientes =  async () => {
      const clientes = await api.buscarClientes();
      if(clientes?.data){
        setFilterData(clientes.data);
        setClientes(clientes.data);
      }
  }

  const setFilterSearch = () => {
    if (search) {
      const newData = clientes.filter(item => {
        return (
          item.nome_completo_cpf.toLocaleLowerCase().indexOf(search.toLocaleLowerCase()) > -1
        );
      });
      setFilterData(newData);
    } else {
      setFilterData(clientes);
    }
  };

  const moveToMoney = () => {
    navigation.navigate(StackNav.SendMoney, {
      cliente : clienteSelecionado
    });
  };

  const moveToCadastroUsuario = () => {
    navigation.navigate(StackNav.CadastroCliente);
  };

  const onChangeSearch = text => setSearch(text);

  const Cards = ({style, source}) => {
    return (
      <TouchableOpacity>
        <Image style={style} source={source} />
      </TouchableOpacity>
    );
  };

  const ContactDetails = ({item}) => {
    return (
      <View style={localStyles.mainBoyGirl}>
        <TouchableOpacity
          style={[
            localStyles.parentGirl,
            {
              borderColor: clienteSelecionado?.id === item.id ? colors.SignUpTxt : colors.google,
            },
          ]}
          onPress={() => setClienteSelecionado(item)}>
          {clienteSelecionado?.id === item.id ? (
            <MetarialIcon
              name={'done'}
              color={colors.SignUpTxt}
              size={16}
              style={localStyles.doneIcon}
            />
          ) : null}
          <Image source={Images.AGE} style={localStyles.imgSty} />
          <CText
            color={colors.black}
            align={'center'}
            type={'B12'}
            style={localStyles.GirlNameTxt}>
            {item.nome_completo_cpf}
          </CText>
        </TouchableOpacity>
      </View>
    );
  };

  return (
    <SafeAreaView style={localStyles.main}>
      <View style={localStyles.mainContainer}>
        <ScrollView>
          <CHeader color={colors.black} title='Cliente' />
          <CButton text='Cadastrar Cliente' containerStyle={localStyles.ContBtn} onPress={moveToCadastroUsuario} />

          <CTextInput
            mainTxtInp={localStyles.CTxtInp}
            value={search}
            onChangeText={onChangeSearch}
            text={'Procurar Cliente...'}
            LeftIcon={() => (
              <Ionicons
                name={'search-outline'}
                size={moderateScale(22)}
                color={colors.black}
                style={styles.ml15}
              />
            )}
          />
          <FlatList
            keyExtractor={(item, index) => index.toString()}
            data={filterData}
            renderItem={ContactDetails}
            horizontal
            showsHorizontalScrollIndicator={false}
          />
        </ScrollView>
      </View>
      {clienteSelecionado && 
        <CButton containerStyle={localStyles.ContBtn} onPress={moveToMoney} />
      }
      
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
    width: moderateScale(180),
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
});
