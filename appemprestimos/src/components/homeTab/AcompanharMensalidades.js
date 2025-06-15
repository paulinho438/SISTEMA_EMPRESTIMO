import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import {IconButton} from 'react-native-paper';
import {useNavigation} from '@react-navigation/native';

import { StackNav } from '../../navigation/navigationKeys';

import {useRoute} from '@react-navigation/native';

const AcompanharMensalidades = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const {emprestimo, user} = route.params;

  const historico = [
    {
      title: 'Mensalidade pendente',
      sub: '4 de 36 meses',
      data: '10 JUN',
      valor: 'R$ 318,45',
      riscado: false,
      tag: false,
      noAvancar: true,
    },
    {
      title: 'Mensalidade paga',
      sub: '3 de 36 meses',
      data: '10 JUN',
      valor: 'R$ 318,45',
      riscado: false,
      tag: true,
      isPago: true,
    },
    {
      title: 'Mensalidade paga',
      sub: '2 de 36 meses',
      data: '10 MAI',
      valor: 'R$ 318,45',
      riscado: false,
      tag: true,
      isPago: true,
    },
    {
      title: 'Mensalidade paga',
      sub: '1 de 36 meses',
      data: '19 ABR',
      valor: 'R$ 238,84',
      riscado: false,
      tag: true,
      isPago: true,
    },
    {
      title: 'Início do empréstimo realizado',
      sub: 'Plano #7269',
      data: '19 ABR',
      icon: 'login',
      noValor: true,
    },
  ];

  const moveToPixParcela = (parcela) => {
    navigation.navigate(StackNav.PixParcela, {
      emprestimo: emprestimo,
      parcela: parcela
    });
  };

  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <IconButton icon="arrow-left" onPress={() => navigation.goBack()} />
      </View>

      {/* Título */}
      <Text style={styles.subtitle}>AGE #{emprestimo.codigo_emprestimo}</Text>
      <Text style={styles.valorTotal}>R$ {emprestimo.valor_total}</Text>
      <Text style={styles.mensalidade}>
        Mensalidade de R$ {emprestimo.mensalidade}
      </Text>
      <Text style={styles.progresso}>{emprestimo.progresso_meses}</Text>

      {/* Botões rápidos
      <View style={styles.actionRow}>
        <QuickAction icon="currency-usd" label="Mensalidades" />
      </View> */}

      {/* Histórico */}
      <Text style={styles.historicoTitle}>Histórico</Text>

      {emprestimo?.historico_formatado.map((item, index) => (
        <TouchableOpacity
          key={index}
          onPress={() => item.noAvancar ? moveToPixParcela(item) : null}
          style={{
            ...styles.itemContainer,
            paddingBottom: item.noAvancar ? 0 : 20,
          }}>
          <View style={styles.itemIcon}>
            <IconButton
              icon={item.icon || 'circle-slice-8'}
              size={20}
              iconColor={item.isPago ? '#0BA090' : '#000'}
              style={{margin: 0}}
            />
          </View>
          <View style={styles.itemText}>
            <Text
              style={[
                styles.itemTitle,
                item.isPago && {textDecorationLine: 'line-through'},
              ]}>
              {item.title}
            </Text>
            <Text
              style={[
                styles.itemSub,
                item.isPago && {textDecorationLine: 'line-through'},
              ]}>
              {item.sub}
            </Text>
          </View>
          <View style={{...styles.itemRight}}>
            <Text style={styles.itemDate}>{item.data}</Text>
            {!item.noValor && (
              <View style={{alignItems: 'flex-end'}}>
                {item.riscado && (
                  <Text style={styles.valorRiscado}>{item.riscado}</Text>
                )}
                <Text
                  style={[
                    styles.itemValor,
                    item.tag && {color: '#0BA090', fontWeight: 'bold'},
                    item.isPago && {textDecorationLine: 'line-through'},
                  ]}>
                  {item.valor}
                </Text>
              </View>
            )}
            {item.noAvancar && (
              <IconButton icon="chevron-right" size={18} iconColor="#aaa" />
            )}
          </View>
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
};

const QuickAction = ({icon, label}) => (
  <View style={styles.quickItem}>
    <IconButton
      icon={icon}
      size={20}
      iconColor="#000"
      style={styles.quickIcon}
    />
    <Text style={styles.quickText}>{label}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#fff',
    paddingHorizontal: 16,
    marginTop: 20,
  },
  header: {
    marginTop: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
    marginLeft: 4,
  },
  valorTotal: {
    fontSize: 24,
    fontWeight: 'bold',
    marginLeft: 4,
    color: '#000',
  },
  mensalidade: {
    fontSize: 14,
    color: '#333',
    marginTop: 4,
    marginLeft: 4,
  },
  progresso: {
    fontSize: 14,
    color: '#555',
    marginBottom: 16,
    marginLeft: 4,
  },
  actionRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: '#fff',
    marginBottom: 16,
    paddingHorizontal: 4,
  },
  quickItem: {
    width: '32%',
    backgroundColor: '#f6f6f6',
    padding: 12,
    borderRadius: 12,
    alignItems: 'center',
  },
  quickIcon: {
    margin: 0,
  },
  quickText: {
    fontSize: 12,
    textAlign: 'center',
    marginTop: 4,
    color: '#333',
  },
  historicoTitle: {
    fontSize: 16,
    fontWeight: '500',
    color: '#000',
    marginBottom: 8,
    marginTop: 16,
  },
  itemContainer: {
    flexDirection: 'row',
    paddingTop: 10,
    borderBottomColor: '#eee',
    borderBottomWidth: 1,
    alignItems: 'center',
  },
  itemIcon: {
    marginRight: 8,
  },
  itemText: {
    flex: 1,
  },
  itemTitle: {
    fontSize: 14,
    color: '#000',
  },
  itemSub: {
    fontSize: 12,
    color: '#666',
  },
  itemRight: {
    alignItems: 'flex-end',
  },
  itemDate: {
    fontSize: 12,
    color: '#666',
  },
  itemValor: {
    fontSize: 14,
    color: '#000',
  },
  valorRiscado: {
    fontSize: 12,
    color: '#999',
    textDecorationLine: 'line-through',
  },
});

export default AcompanharMensalidades;
