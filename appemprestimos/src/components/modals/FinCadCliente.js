import {StyleSheet, View, Image, Text, Linking, Alert, ScrollView} from 'react-native';
import React, {useState} from 'react';
import ActionSheet, {FlatList} from 'react-native-actions-sheet';
import Fonisto from 'react-native-vector-icons/Fontisto';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import {LocationData} from '../../api/constants';
import CButton from '../common/CButton';
import {TouchableOpacity} from 'react-native-gesture-handler';
import {useNavigation} from '@react-navigation/native';
import {StackNav, TabNav} from '../../navigation/navigationKeys';
import CTextInput from '../../components/common/CTextInput';
import api from '../../services/api';
import Saldo from './Saldo';
import margin from '../../themes/margin';

export default function InfoParcelas(props) {
  let {sheetRef, parcelas, clientes, localizacao} = props;

  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});

  const [endereco, setEndereco] = useState('');
  const [complemento, setComplemento] = useState('');

  const renderData = ({item}) => {
    return (
      <TouchableOpacity>
        <View style={localStyles.mainComponent}>
          <Image style={localStyles.imageStyle} source={item.image} />
          <CText align={'center'} type={'M12'} color={colors.black}>
            {item.reviews}
          </CText>
        </View>
      </TouchableOpacity>
    );
  };

  const obterDataAtual = () => {
    const data = new Date();
    const ano = data.getFullYear();
    let mes = data.getMonth() + 1; // Os meses vão de 0 a 11 em JavaScript, então adicionamos 1
    let dia = data.getDate();
  
    // Adicionar um zero à esquerda se o mês ou o dia for menor que 10
    mes = mes < 10 ? '0' + mes : mes;
    dia = dia < 10 ? '0' + dia : dia;
  
    return `${ano}-${mes}-${dia}`;
  };

  const onPressCadastroCliente = async () => {

    let req = await api.cadastroCliente(clientes.name, clientes.email, clientes.cellphone, clientes.cellphone2, clientes.cpf, clientes.rg, clientes.nascimento, clientes.sexo,  localizacao, clientes.pix);

    Alert.alert('Cliente Cadastrado com Sucesso!');

    navigation.navigate(StackNav.Clientes);
  };

  const cancelModel = () => {
    sheetRef.current?.hide();
  };

  const arrowParcelaIcon = () => (
    <Community size={24} name={'account-cash-outline'} color={colors.white} />
  );

  const arrowRightTopIcon = () => (
    <Community size={24} name={'arrow-u-right-top'} color={colors.white} />
  );

  const whatsapp = () => (
    <Community size={24} name={'whatsapp'} color={colors.white} />
  );

  const close = () => (
    <Community size={24} name={'close-outline'} color={colors.white} />
  );

  const check = () => (
    <Community size={24} name={'check'} color={colors.white} />
  );

  const timer = () => (
    <Community size={24} name={'timer-sand-empty'} color={colors.white} />
  );

  const onPressClose = (item) => {
    if(item?.id){
      setCliente(item)
    }
    setVisible(!visible);
  };

  return (
    <View>
      <ActionSheet containerStyle={localStyles.actionSheet} ref={sheetRef}>
      <TouchableOpacity style={localStyles.parentDepEnd}  onPress={cancelModel}>
        <Community size={40} name={'close'} color={colors.black} />
      </TouchableOpacity>
      <ScrollView showsVerticalScrollIndicator={false}>
      <View style={localStyles.mainContainer}>
      
      
      <View style={localStyles.outerComponent}>
        <View style={{gap: moderateScale(20)}}>
          <CText
            color={colors.black}
            type={'B24'}>
            Informe o endereço do cliente
          </CText>
          <CTextInput
                value={endereco}
                onChangeText={setEndereco}
                mainTxtInp={[localStyles.border]}
                text={'Endereço Completo'}
              />
              <CTextInput
                value={complemento}
                onChangeText={setComplemento}
                mainTxtInp={[localStyles.border]}
                text={'Complemento'}
              />
              <CButton
          onPress={() => onPressCadastroCliente()}
          text={`Cadastrar`}
          containerStyle={localStyles.buttonContainerGreen
          }
          RightIcon={check
          }
        />

          
        </View>
      </View>
      
      
      </View>
       
      </ScrollView>
        
      </ActionSheet>
         
    </View>
  );
}

const localStyles = StyleSheet.create({
  imgSty: {
    width: moderateScale(330),
    height: moderateScale(100),
    ...styles.selfCenter,
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
  },
  mainContainer: {
    ...styles.m20,
  },

  outerComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  outerComponent2: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt10
  },
  imageStyle: {
    width: moderateScale(40),
    height: moderateScale(40),
  },
  mainComponent: {
    gap: moderateScale(10),
    ...styles.justifyEvenly,
    ...styles.alignCenter,
    ...styles.p15,
    ...styles.mh5,
    width: moderateScale(101),
    height: moderateScale(106),
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.bottomBorder,
  },
  outerContainer: {
    ...styles.mt25,
  },
  buttonContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.red,
  },
  buttonContainerRed: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.red,
  },
  buttonContainerGreen: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Green,
  },
  buttonContainerPrimary: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Primary,
  },
  parentDepEnd: {
    ...styles.alignEnd,
    ...styles.mr25,
    ...styles.mt30,
    ...styles.mb20
  },
  border: {
    backgroundColor: colors.GreyScale,
    borderWidth: moderateScale(1),
  },
});


