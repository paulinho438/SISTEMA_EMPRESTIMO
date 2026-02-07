import React from "react";
import { View, Text, StyleSheet } from "react-native";

const ResumoFinanceiro = ({ resumoFinanceiro }) => {

  const renderRow = (label, value, style) => {
    const displayValue = resumoFinanceiro != null
      ? (typeof value === 'number' && isNaN(value) ? '—' : String(value ?? '—'))
      : "Carregando...";
    return (
      <View style={styles.row}>
        <Text style={styles.label}>{label}</Text>
        <Text style={style}>{displayValue}</Text>
      </View>
    );
  };

  const formatCurrency = (value) => {
    const num = Number(value);
    if (isNaN(num)) return '—';
    return num.toLocaleString("pt-BR", { minimumFractionDigits: 2 });
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Resumo 📈</Text>

      {renderRow("Contratos Ativos:", (resumoFinanceiro?.total_emprestimos_em_dias ?? 0) + (resumoFinanceiro?.total_emprestimos_muito_atrasados ?? 0), styles.valueGreen)}
      {renderRow("Total investido:", `R$${formatCurrency(resumoFinanceiro?.total_ja_investido)}`, styles.valueBlue)}
      {renderRow("Previsão de lucro:", `R$${formatCurrency((resumoFinanceiro?.total_a_receber ?? 0) + (resumoFinanceiro?.total_ja_recebido ?? 0) - (resumoFinanceiro?.total_ja_investido ?? 0))}`, styles.valueGreen)}
      {renderRow("Total a Receber:", `R$${formatCurrency(resumoFinanceiro?.total_a_receber)}`, styles.valueBold)}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: "#F8F9FA",
    padding: 20,
    borderRadius: 10,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 5,
    marginHorizontal: 10,
    marginTop: 25,
  },
  title: {
    fontSize: 18,
    fontWeight: "bold",
    marginBottom: 10,
  },
  row: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginVertical: 4,
  },
  label: {
    fontSize: 16,
    color: "#333",
  },
  valueGreen: {
    fontSize: 16,
    color: "green",
    fontWeight: "bold",
  },
  valueBlue: {
    fontSize: 16,
    color: "blue",
    fontWeight: "bold",
  },
  valueBold: {
    fontSize: 16,
    fontWeight: "bold",
  },
});

export default ResumoFinanceiro;