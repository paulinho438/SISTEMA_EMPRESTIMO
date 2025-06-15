import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Clipboard,
  ScrollView,
  Alert,
} from 'react-native';
import {IconButton} from 'react-native-paper';
import {useNavigation} from '@react-navigation/native';
import MaterialCommunityIcons from 'react-native-vector-icons/MaterialCommunityIcons';
import {useRoute} from '@react-navigation/native';

const PixParcela = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const {emprestimo, parcela} = route.params;

  // Dados simulados — você pode receber via props ou route.params
  const codigoPix =
    '23798858400000253183380250976516801122114000ABC00000000000';
  const vencimento = '14/12/2023';
  const valor = 'R$ 1.280,00';
  const descricao = 'Mensalidade de Novembro';

  const copiarCodigo = () => {
    Clipboard.setString(parcela.chave_pix);
  };

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Header */}
        <View style={styles.header}>
          <IconButton icon="arrow-left" onPress={() => navigation.goBack()} />
        </View>

        {/* QR / Título */}
        <View style={styles.qrContainer}>
          <MaterialCommunityIcons name="qrcode" size={48} color="#fcbf49" />
        </View>
        <Text style={styles.title}>Pagamento</Text>
        <Text style={styles.subTitle}>{parcela.title}</Text>
        <Text style={styles.valor}>{parcela.valor}</Text>

        {/* Instruções */}
        <View style={styles.instructionBox}>
          <Text style={styles.instructionTitle}>Pague usando o código Pix</Text>
          <Text style={styles.instructionText}>
            {parcela.msgPagamento}
          </Text>

          {/* Código Pix Box */}
          <View style={styles.pixBox}>
            <View style={styles.pixTextContainer}>
              <Text style={styles.pixLabel}>código pix</Text>
              <Text style={styles.pixCode} numberOfLines={1}>
                {parcela.chave_pix}
              </Text>
              <Text style={styles.pixVencimento}>Vence em {vencimento}</Text>
            </View>
            <IconButton icon="content-copy" onPress={copiarCodigo} />
          </View>
        </View>
      </ScrollView>

      {/* Botão copiar */}
      <TouchableOpacity style={styles.copyButton} onPress={copiarCodigo}>
        <MaterialCommunityIcons name="content-copy" size={20} color="#000" />
        <Text style={styles.copyText}>Copiar código</Text>
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 100,
  },
  header: {
    marginBottom: 16,
  },
  qrContainer: {
    alignItems: 'center',
    marginBottom: 8,
  },
  title: {
    textAlign: 'center',
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 4,
    color: '#000',
  },
  subTitle: {
    textAlign: 'center',
    fontSize: 14,
    color: '#777',
  },
  valor: {
    textAlign: 'center',
    fontSize: 18,
    fontWeight: 'bold',
    color: '#000',
    marginTop: 4,
  },
  instructionBox: {
    marginTop: 32,
  },
  instructionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#000',
    marginBottom: 4,
  },
  instructionText: {
    fontSize: 14,
    color: '#555',
    marginBottom: 12,
  },
  pixBox: {
    backgroundColor: '#f4f4f4',
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
  },
  pixTextContainer: {
    flex: 1,
  },
  pixLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 4,
  },
  pixCode: {
    fontSize: 14,
    color: '#000',
  },
  pixVencimento: {
    fontSize: 12,
    color: '#555',
    marginTop: 4,
  },
  copyButton: {
    position: 'absolute',
    bottom: 20,
    left: 20,
    right: 20,
    borderWidth: 1,
    borderColor: '#000',
    borderRadius: 16,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  copyText: {
    fontSize: 16,
    fontWeight: '500',
    marginLeft: 8,
    color: '#000',
  },
});

export default PixParcela;
