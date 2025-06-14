import React from 'react';
import { View, StyleSheet, Text, ScrollView, TouchableOpacity } from 'react-native';
import { IconButton, Divider, useTheme } from 'react-native-paper';
import Svg, { Circle } from 'react-native-svg';


const DetalhesEmprestimos = ({ navigation }) => {
  const theme = useTheme();

  const dados = {
    plano: '#0001',
    grupo: '#0001',
    valorPago: 12190,
    valorCredito: 100000,
    taxa: 21.9,
    reajuste: 6.47,
    prazo: 100,
    mesesPagos: 10,
    mensalidade: 1219,
  };

  const restante = dados.valorCredito + dados.valorCredito * (dados.taxa / 100) - dados.valorPago;
  const percentualPago = ((dados.mesesPagos / dados.prazo) * 100).toFixed(0);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.scrollContent}>
      {/* Header */}
      <View style={styles.header}>
        <IconButton icon="arrow-left" onPress={() => navigation.goBack()} />
        <ProgressCircle percentage={30} />
      </View>

      {/* Plano e grupo */}
      <Text style={styles.subTitle}>Plano {dados.plano} | Grupo {dados.grupo}</Text>

      {/* Valor já pago */}
      <Text style={styles.label}>Você já pagou</Text>
      <Text style={styles.valorPago}>R$ {dados.valorPago.toLocaleString('pt-BR')}</Text>
      <Text style={styles.detalhesPagos}>
        {dados.mesesPagos} de {dados.prazo} meses
      </Text>
      <Text style={styles.detalhesPagos}>
        A pagar: R$ {restante.toLocaleString('pt-BR')}
      </Text>

      {/* Informações financeiras */}
      <Divider style={styles.divider} />
      <Item label="Valor do crédito" valor={`R$ ${dados.valorCredito.toLocaleString('pt-BR')}`} />
      <Item label="Prazo" valor={`${dados.prazo} meses`} />
      <Item label="Mensalidade" valor={`R$ ${dados.mensalidade.toLocaleString('pt-BR')}`} />

      {/* Botão de dúvidas */}
    </ScrollView>
  );
};

const Item = ({ label, valor }) => (
  <View style={styles.itemContainer}>
    <Text style={styles.itemLabel}>{label}</Text>
    <Text style={styles.itemValue}>{valor}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  scrollContent: {
    padding: 20,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  progressBadge: {
    backgroundColor: '#f5f5f5',
    borderRadius: 30,
    paddingVertical: 6,
    paddingHorizontal: 12,
  },
  progressText: {
    fontWeight: '600',
    color: '#000',
  },
  subTitle: {
    fontSize: 14,
    color: '#555',
    marginTop: 8,
  },
  label: {
    fontSize: 16,
    color: '#555',
    marginTop: 20,
  },
  valorPago: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#000',
    marginTop: 4,
  },
  detalhesPagos: {
    fontSize: 14,
    color: '#666',
    marginTop: 2,
  },
  divider: {
    marginVertical: 16,
  },
  itemContainer: {
    marginBottom: 14,
  },
  itemLabel: {
    fontWeight: 'bold',
    fontSize: 14,
    color: '#333',
  },
  itemValue: {
    fontSize: 14,
    color: '#555',
    marginTop: 2,
  },
  duvidasButton: {
    marginTop: 24,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f4f4f4',
    alignSelf: 'flex-start',
    borderRadius: 20,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  duvidasText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
  },
  containerPorcent: {
    marginTop: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  labelContainer: {
    position: 'absolute',
    justifyContent: 'center',
    alignItems: 'center',
  },
  label: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
  },
});

const ProgressCircle = ({ percentage = 0, size = 50, strokeWidth = 4 }) => {
  const radius = (size - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference * (1 - percentage / 100);

  return (
    <View style={[styles.containerPorcent, { width: size, height: size }]}>
      <Svg width={size} height={size}>
        {/* Background circle */}
        <Circle
          stroke="#eee"
          fill="none"
          cx={size / 2}
          cy={size / 2}
          r={radius}
          strokeWidth={strokeWidth}
        />
        {/* Progress circle */}
        <Circle
          stroke="#fcbf49"
          fill="none"
          cx={size / 2}
          cy={size / 2}
          r={radius}
          strokeWidth={strokeWidth}
          strokeDasharray={`${circumference} ${circumference}`}
          strokeDashoffset={strokeDashoffset}
          strokeLinecap="round"
          rotation="-90"
          origin={`${size / 2}, ${size / 2}`}
        />
      </Svg>
      <View style={styles.labelContainer}>
        <Text style={styles.label}>{percentage}%</Text>
      </View>
    </View>
  );
};

export default DetalhesEmprestimos;
