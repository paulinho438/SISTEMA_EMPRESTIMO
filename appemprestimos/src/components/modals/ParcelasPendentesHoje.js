import {
  StyleSheet,
  View,
  Text,
  Linking,
  Alert,
  FlatList,
  TextInput,
  ActivityIndicator,
} from 'react-native';
import React, { useRef, useState, useMemo } from 'react';
import ActionSheet from 'react-native-actions-sheet';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import { colors } from '../../themes/colors';
import CText from '../common/CText';
import { TouchableOpacity } from 'react-native-gesture-handler';
import { useNavigation } from '@react-navigation/native';
import { StackNav } from '../../navigation/navigationKeys';
import api from '../../services/api';
import Saldo from '../modals/Saldo';

export default function ParcelasPendentesHoje({ sheetRef, parcelasPendentes, onAtualizarClientes }) {
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});
  const [item, setItem] = useState({});
  const [searchText, setSearchText] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const textInputRef = useRef(null);

  const obterDataAtual = () => {
    const data = new Date();
    return data.toISOString().split('T')[0]; // Retorna YYYY-MM-DD
  };

  const baixaManual = async () => {
    setIsLoading(true);
    try {
      await api.baixaManual(parcelas.id, obterDataAtual());
      Alert.alert('Baixa realizada com sucesso!');
      navigation.navigate(StackNav.TabNavigation);
    } catch (error) {
      Alert.alert('Erro ao processar a baixa.');
    }
    setIsLoading(false);
  };

  const cancelModel = () => {
    sheetRef.current?.hide();
  };

  const onPressClose = item => {
    if (item?.id) {
      setCliente(item);
    } else {
      onAtualizarClientes();
    }
    setVisible(!visible);
  };

  const fecharSaldo = () => {
    setVisible(false);
    onAtualizarClientes();
  }

  const formatCurrency = value =>
    value ? value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : 'R$ 0,00';

  const filteredParcelasPendentes = useMemo(() => {
    return parcelasPendentes.filter(item =>
      item.cliente.nome_completo.toLowerCase().includes(searchText.toLowerCase())
    );
  }, [parcelasPendentes, searchText]);

  return (
    <View>
      <ActionSheet
        containerStyle={localStyles.actionSheet}
        ref={sheetRef}
        onOpen={() => textInputRef.current?.focus()}
      >
        <View style={localStyles.searchContainer}>
          <TextInput
            ref={textInputRef}
            style={localStyles.searchInput}
            placeholderTextColor="#000"
            placeholder="Pesquisar pelo nome do cliente"
            value={searchText}
            onChangeText={setSearchText}
          />
          <TouchableOpacity style={localStyles.closeButton} onPress={cancelModel}>
            <Community size={40} name={'close'} color={colors.black} />
          </TouchableOpacity>
        </View>

        {isLoading ? (
          <ActivityIndicator size="large" color={colors.primary} style={{ marginVertical: 20 }} />
        ) : (
          <FlatList
            data={filteredParcelasPendentes}
            keyExtractor={item => item.id.toString()}
            renderItem={({ item }) => (
              <View style={styles2.container}>
                <Text style={styles2.subTitle}>
                  {item.cliente.nome_completo} - CPF: {item.cliente.cpf}
                </Text>

                {item.parcelas_vencidas[0]?.valor_recebido > 0 && (
                  <Text style={styles2.subTitleValor}>
                    Valor recebido em dinheiro {formatCurrency(item.parcelas_vencidas[0]?.valor_recebido)}
                  </Text>
                )}

                {item.parcelas_vencidas[0]?.valor_recebido_pix > 0 && (
                  <Text style={styles2.subTitleValor}>
                    Valor recebido em Pix {formatCurrency(item.parcelas_vencidas[0]?.valor_recebido_pix)}
                  </Text>
                )}

                <View style={styles2.buttonContainer}>
                  <TouchableOpacity
                    onPress={() => onPressClose(item.parcelas_vencidas[0])}
                    style={styles2.actionButton}
                  >
                    <Text style={styles2.buttonText}>Efetuar Baixa</Text>
                  </TouchableOpacity>
                  <Text style={styles2.valorHoje}>
                    Valor Hoje {formatCurrency(item.saldoatrasado > 0 ? item.saldoatrasado : item.parcelas_vencidas[0]?.saldo)}
                  </Text>
                </View>
              </View>
            )}
          />
        )}

        <Saldo visible={visible} onPressClose={() => fecharSaldo()} cliente={cliente} pendenteHoje={item} tela="baixa_pendentes_hoje" />
      </ActionSheet>
    </View>
  );
}

const styles2 = StyleSheet.create({
  container: {
    padding: 20,
    backgroundColor: '#F7F6F6',
    marginTop: 10,
    marginBottom: 10,
    borderRadius: 20,
  },
  subTitle: {
    color: '#242826',
    fontSize: 14,
    fontWeight: 'bold',
    marginBottom: 10,
  },
  subTitleValor: {
    fontSize: 14,
    color: '#3CA454',
    marginTop: -15,
    marginBottom: 20,
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    backgroundColor: '#e0e0e0',
    paddingVertical: 10,
    paddingHorizontal: 15,
    borderRadius: 5,
  },
  buttonText: {
    fontSize: 14,
  },
  valorHoje: {
    fontSize: 14,
    color: '#666',
    marginTop: 15,
  },
});

const localStyles = StyleSheet.create({
  actionSheet: {
    borderTopLeftRadius: 40,
    borderTopRightRadius: 40,
    padding: 20,
  },
  searchContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
  },
  searchInput: {
    flex: 1,
    height: 40,
    borderColor: '#c6c6c6',
    color: '#242826',
    borderWidth: 1,
    borderRadius: 5,
    paddingHorizontal: 10,
  },
  closeButton: {
    marginLeft: 10,
  },
});
