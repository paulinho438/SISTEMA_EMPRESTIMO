import React from "react";
import { View, Text, StyleSheet } from "react-native";

const ResumoFinanceiro = ({ resumoFinanceiro }) => {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Resumo 📈</Text>

      <View style={styles.row}>
        <Text style={styles.label}>Contratos:</Text>
        <Text style={styles.valueGreen}>{resumoFinanceiro?.total_emprestimos}</Text>
      </View>

      <View style={styles.row}>
        <Text style={styles.label}>Total investido:</Text>
        <Text style={styles.valueBlue}>R${resumoFinanceiro?.total_ja_investido.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</Text>
      </View>

      <View style={styles.row}>
        <Text style={styles.label}>Previsão de lucro:</Text>
        <Text style={styles.valueGreen}>R${(resumoFinanceiro?.total_a_receber +  resumoFinanceiro?.total_ja_recebido - resumoFinanceiro?.total_ja_investido).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</Text>
      </View>

      <View style={styles.row}>
        <Text style={styles.label}>Total a Receber:</Text>
        <Text style={styles.valueBold}>R${resumoFinanceiro?.total_a_receber.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</Text>
      </View>
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