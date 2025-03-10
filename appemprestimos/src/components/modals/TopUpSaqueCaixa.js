import {StyleSheet, View, Image} from 'react-native';
import React, {useState, useEffect} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import {useNavigation} from '@react-navigation/native';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';

import CTextInput from '../common/CTextInput';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CText from '../common/CText';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import {colors} from '../../themes/colors';

import FullScreenLoader from '../FullScreenLoader';
import api from '../../services/api';

export default function TopUpSaqueCaixa(props) {
  let {sheetRef, dados} = props;

  const navigation = useNavigation();

  const [loading, setLoading] = useState(false);
  const [valor, setValor] = useState(0);
  const [mensagemSaque, setMensagemSaque] = useState('');
  const [isVisible, setIsVisible] = useState(false);

  const backToHome = () => {
    navigation.navigate(StackNav.TabNavigation);
  };

  const handleValorChange = text => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto
    if (isNaN(number)) {
      number = 0;
    }
    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro
    setValor(currency);
  };

  const efetivarSaque = async () => {
    if (!mensagemSaque) {
      try {
        setLoading(true);
        let response = await api.saqueConsulta(dados.id, {
          valor: parseFloat(valor.replace(/\D/g, '')) / 100,
        });
        setLoading(false);
        if (response?.error) {
          alert(response.error);
          return;
        } else {
          setMensagemSaque(
            `Tem certeza que deseja realizar o paramento de ${valor} para ${response?.creditParty?.name}?`,
          );
        }

        // alert('Caixa alterado com sucesso!');
        // navigation.navigate(StackNav.TabNavigation);
      } catch (e) {
        console.log(e);
      }
    } else {
      try {
        setLoading(true);
        await api.efetuarSaque(dados.id, {
          valor: parseFloat(valor.replace(/\D/g, '')) / 100,
        });
        setLoading(false);
        setMensagemSaque(null);
        alert('Saque efetuado com sucesso!');
        navigation.navigate(StackNav.TabNavigation);

        // alert('Caixa alterado com sucesso!');
        // navigation.navigate(StackNav.TabNavigation);
      } catch (e) {
        setLoading(false);
        setMensagemSaque(null);
        alert('Saque efetuado com sucesso!');
        navigation.navigate(StackNav.TabNavigation);
      }
    }
  };

  useEffect(() => {
    if (isVisible) {
      console.log('ActionSheet aberto', dados);
      // Ação a ser executada quando o ActionSheet for aberto
      setValor(null);
      setMensagemSaque(null);
    }
  }, [isVisible]);

  return (
    <ActionSheet
      ref={sheetRef}
      containerStyle={localStyles.actionSheet}
      onOpen={() => setIsVisible(true)}
      onClose={() => setIsVisible(false)}>
      <View style={localStyles.mainContainer}>
        <FullScreenLoader visible={loading} />
        <CText
          color={colors.black}
          align={'center'}
          type={'B24'}
          style={localStyles.TUSTxt}>
          Realizar Saque
        </CText>
        {!mensagemSaque && (
          <View>
            <CText color={colors.black} type={'M16'} style={styles.mb10}>
              Valor
            </CText>
            <CTextInput
              value={valor}
              onChangeText={handleValorChange}
              mainTxtInp={[localStyles.border]}
              text={'Valor'}
            />
          </View>
        )}

        {mensagemSaque && (
          <View>
            <CText
              color={colors.black}
              type={'R16'}
              align={'center'}
              style={localStyles.noticeTxt}>
              {mensagemSaque}
            </CText>
          </View>
        )}
      </View>

      <View style={localStyles.mainTop}>
        <CText
          color={colors.black}
          type={'R14'}
          align={'center'}
          style={localStyles.noticeTxt}>
          O sistema realizará o pagamento para o pix que está cadastrado no
          banco!
        </CText>

        <CButton
          text={'Efetivar Saque'}
          containerStyle={localStyles.parentButton}
          onPress={efetivarSaque}
        />
      </View>
    </ActionSheet>
  );
}

const localStyles = StyleSheet.create({
  imgSty: {
    width: moderateScale(258),
    height: moderateScale(194),
  },
  mainContainer: {
    ...styles.center,
    ...styles.mv40,
    gap: moderateScale(20),
  },
  noticeTxt: {
    ...styles.ph20,
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
    backgroundColor: colors.bottomBorder,
    ...styles.ph20,
    gap: moderateScale(20),
  },
  TUSTxt: {
    ...styles.pv25,
  },
  parentButton: {
    width: '90%',
    ...styles.mv30,
  },
  mainTop: {
    gap: moderateScale(80),
  },
});
