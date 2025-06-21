import {StyleSheet, View, Text, Alert, ScrollView, Modal, TextInput, FlatList} from 'react-native';
import React, {useState} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {TouchableOpacity} from 'react-native-gesture-handler';
import {useNavigation} from '@react-navigation/native';
import {StackNav} from '../../navigation/navigationKeys';
import api from '../../services/api';
import Saldo from './Saldo';

export default function InfoNotas(props) {
  let {sheetRef, parcelas, clientes, notas, getNotas} = props;
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});

  const [novaNota, setNovaNota] = useState('');
  const [modalVisible, setModalVisible] = useState(false);

  const adicionarNota = async () => {
    if (!novaNota.trim()) {
      Alert.alert('Nota vazia', 'Digite alguma coisa antes de salvar.');
      return;
    }

    try {
      await api.gravarNota({
        emprestimo_id: clientes?.emprestimo_id,
        conteudo: novaNota.trim(),
      });

      Alert.alert('Nota salva com sucesso!');
      setNovaNota('');
      setModalVisible(false);
      getNotas();
    } catch (error) {
      console.error(error);
      Alert.alert('Erro', 'Não foi possível salvar a nota.');
    }
  };

  const excluirNota = (id) => {
    Alert.alert('Excluir nota', 'Deseja excluir esta nota?', [
      {text: 'Cancelar', style: 'cancel'},
      {
        text: 'Excluir',
        style: 'destructive',
        onPress: async () => {
          try {
            await api.excluitNota(id);
            Alert.alert('Nota excluída com sucesso!');
            getNotas();
          } catch (error) {
            console.error(error);
            Alert.alert('Erro', 'Não foi possível excluir a nota.');
          }
        },
      },
    ]);
  };

  const renderNota = ({item}) => (
    <View style={localStyles.notaContainer}>
      <CText color={colors.black} type="M14">{item.conteudo}</CText>
      <Community
        name="trash-can-outline"
        size={24}
        color={colors.red}
        onPress={() => excluirNota(item.id)}
      />
    </View>
  );

  return (
    <View>
      <ActionSheet containerStyle={localStyles.actionSheet} ref={sheetRef}>
        <TouchableOpacity style={localStyles.parentDepEnd} onPress={() => sheetRef.current?.hide()}>
          <Community size={40} name={'close'} color={colors.black} />
        </TouchableOpacity>

        <ScrollView showsVerticalScrollIndicator={false}>
          <View style={localStyles.mainContainer}>

            <View style={localStyles.outerComponent}>
              <View style={{gap: moderateScale(7)}}>
                <CText color={colors.black} type={'B24'}>Notas do Cliente</CText>
                <CText color={colors.black} type={'M16'}>Adicione ou exclua observações manuais</CText>
              </View>
            </View>

            <FlatList
              data={notas}
              keyExtractor={(item) => item.id.toString()}
              renderItem={renderNota}
              ListEmptyComponent={<CText color={colors.gray}>Nenhuma nota cadastrada.</CText>}
              style={{marginTop: moderateScale(20)}}
            />

            <CButton
              text="Adicionar Nota"
              onPress={() => setModalVisible(true)}
              containerStyle={{marginTop: moderateScale(20)}}
            />
          </View>
        </ScrollView>
      </ActionSheet>

      <Modal
        visible={modalVisible}
        animationType="slide"
        transparent
        onRequestClose={() => setModalVisible(false)}>
        <View style={localStyles.modalOverlay}>
          <View style={localStyles.modalContainer}>
            <CText type="B16" color={colors.black}>Digite sua nota:</CText>
            <TextInput
              style={localStyles.input}
              placeholder="Ex: Cobrar cliente amanhã"
              value={novaNota}
              onChangeText={setNovaNota}
              multiline
            />
            <CButton text="Salvar Nota" onPress={adicionarNota} />
            <CButton
              text="Cancelar"
              onPress={() => setModalVisible(false)}
              containerStyle={{backgroundColor: colors.red, marginTop: moderateScale(10)}}
            />
          </View>
        </View>
      </Modal>
    </View>
  );
}

const localStyles = StyleSheet.create({
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
  notaContainer: {
    backgroundColor: colors.bgGray,
    padding: moderateScale(12),
    borderRadius: 8,
    marginBottom: moderateScale(10),
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  modalOverlay: {
    flex: 1,
    justifyContent: 'center',
    backgroundColor: 'rgba(0,0,0,0.3)',
    paddingHorizontal: moderateScale(20),
  },
  modalContainer: {
    backgroundColor: colors.white,
    padding: moderateScale(20),
    borderRadius: 16,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.gray,
    borderRadius: 8,
    padding: moderateScale(10),
    height: 100,
    marginVertical: moderateScale(10),
    textAlignVertical: 'top',
    color: colors.black,
  },
  parentDepEnd: {
    ...styles.alignEnd,
    ...styles.mr25,
    ...styles.mt30,
    ...styles.mb20
  },
});