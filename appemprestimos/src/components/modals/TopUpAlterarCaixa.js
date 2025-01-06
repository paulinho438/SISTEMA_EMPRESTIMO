import {StyleSheet, View, Image} from 'react-native';
import React, {useState, useEffect} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import {useNavigation} from '@react-navigation/native';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';

import CTextInput from '../../components/common/CTextInput';
import {styles} from '../../themes';
import strings from '../../i18n/strings';
import CText from '../common/CText';
import CButton from '../common/CButton';
import {StackNav} from '../../navigation/navigationKeys';
import {colors} from '../../themes/colors';

import api from '../../services/api';

export default function TopUpAlterarCaixa(props) {
  let {sheetRef, dados} = props;

  const navigation = useNavigation();
  const [saldoBanco, setSaldoBanco] = useState(0);
  const [saldoCaixa, setSaldoCaixa] = useState(0);
  const [saldoCaixaPix, setSaldoCaixaPix] = useState(0);
  const [isVisible, setIsVisible] = useState(false);

  const backToHome = () => {
    navigation.navigate(StackNav.TabNavigation);
  };

  const handleSaldoBancoChange = text => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto
    if (isNaN(number)) {
      number = 0;
    }
    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro
    setSaldoBanco(currency);
  };

  const handleSaldoCaixaChange = text => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto

    if (isNaN(number)) {
      number = 0;
    }

    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro
    setSaldoCaixa(currency);
  };

  const handleSaldoCaixaPixChange = text => {
    let cleaned = text.replace(/\D/g, ''); // Remove tudo que não é número
    let number = parseFloat(cleaned) / 100; // Divide por 100 para obter o decimal correto
    if (isNaN(number)) {
      number = 0;
    }
    let currency = number.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }); // Formata o número para o formato monetário brasileiro
    setSaldoCaixaPix(currency);
  };

  const alterarCaixa = async () => {
    try {
      let response = await api.alterarCaixa(dados.id, {
        saldobanco: parseFloat(saldoBanco.replace(/\D/g, '')) / 100,
        saldocaixa: parseFloat(saldoCaixa.replace(/\D/g, '')) / 100,
        saldocaixapix: parseFloat(saldoCaixaPix.replace(/\D/g, '')) / 100
      });
      alert('Caixa alterado com sucesso!');
      navigation.navigate(StackNav.TabNavigation);
    } catch (e) {
      console.log(e);
    }
  };

  useEffect(() => {
    if (isVisible) {
      console.log('ActionSheet aberto', dados);
      // Ação a ser executada quando o ActionSheet for aberto
      setSaldoBanco(0);
      setSaldoCaixa(0);
      setSaldoCaixaPix(0);
    }
  }, [isVisible]);

  return (
    <ActionSheet
      ref={sheetRef}
      containerStyle={localStyles.actionSheet}
      onOpen={() => setIsVisible(true)}
      onClose={() => setIsVisible(false)}>
      <View style={localStyles.mainContainer}>
        <CText
          color={colors.black}
          align={'center'}
          type={'B24'}
          style={localStyles.TUSTxt}>
          Alterar Caixa
        </CText>

        <View>
          <CText color={colors.black} type={'M16'} style={styles.mb10}>
            Saldo no Banco
          </CText>
          <CTextInput
            value={saldoBanco}
            onChangeText={handleSaldoBancoChange}
            mainTxtInp={[localStyles.border]}
            text={'Saldo no banco'}
          />
        </View>

        <View>
          <CText color={colors.black} type={'M16'} style={styles.mb10}>
            Saldo no Caixa
          </CText>
          <CTextInput
            value={saldoCaixa}
            onChangeText={handleSaldoCaixaChange}
            mainTxtInp={[localStyles.border]}
            text={'Saldo no caixa'}
          />
        </View>

        <View>
          <CText color={colors.black} type={'M16'} style={styles.mb10}>
            Saldo no Caixa Pix
          </CText>
          <CTextInput
            value={saldoCaixaPix}
            onChangeText={handleSaldoCaixaPixChange}
            mainTxtInp={[localStyles.border]}
            text={'Saldo no caixa pix'}
          />
        </View>
      </View>

      <View style={localStyles.mainTop}>
        <CText
          color={colors.black}
          type={'R14'}
          align={'center'}
          style={localStyles.noticeTxt}>
          Atualizará os saldos do banco e da empresa com base nos dados
          fornecidos na requisição, garantindo que as alterações sejam feitas de
          forma atômica usando transações do banco de dados.
        </CText>

        <CButton
          text={'Alterar Caixa'}
          containerStyle={localStyles.parentButton}
          onPress={alterarCaixa}
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
