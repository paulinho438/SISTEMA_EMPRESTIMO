import React, {useEffect, useState, useCallback, useMemo} from 'react';

import {View, ScrollView, StyleSheet, Text} from 'react-native';
import {Card, Button, List, Avatar} from 'react-native-paper';
import {StackNav, TabNav} from '../../navigation/navigationKeys';
import {useNavigation} from '@react-navigation/native';
import api from '../../services/api';

import {
  authToken,
  authCompany,
  user,
  permissions,
  removeAuthToken,
  getUser,
} from '../../utils/asyncStorage';

const HomeClienteScreen = () => {
  const navigation = useNavigation();

  const [company, setCompany] = useState(null);
  const [user, setUser] = useState(null);
  const [emp, setEmp] = useState(null);
  const [clientesOrig, setClientesOrig] = useState([]);
  const [location, setLocation] = useState(null);
  const [tipoCliente, setTipoCliente] = useState('');
  const [search, setSearch] = useState('');
  const [permissoesHoje, setPermissoesHoje] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [enabled, setEnabled] = useState(false);

  const [enviandoLocalizacao, setEnviandoLocalizacao] = useState(false);

  const [resumoFinanceiro, setResumoFinanceiro] = useState(null);

  useEffect(async () => {
    const userReq = await getUser();
    setUser(userReq);
    await getInformacoesEmprestimo();
  }, []);

  const moveToDetalhesEmprestimos = () => {
    navigation.navigate(StackNav.DetalhesEmprestimos);
  };

  const getInformacoesEmprestimo = async position => {
    let emp = await api.getInformacoesEmprestimo();
    if(emp.emprestimos.length > 0) {
      setEmp(emp.emprestimos[0]);
    }
  };

  function formatarParaReal(valor) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      minimumFractionDigits: 2,
    }).format(valor);
  }

  return (
    <ScrollView style={styles.container}>
      <Header user={user} />
      <Text style={styles.sectionTitle}>Seu plano</Text>

      <Card style={styles.planCard}>
        <Card.Title
          title = { '#' + (emp?.id ?? '0000') }
          left={props => (
            <Avatar.Icon
              {...props}
              size={30}
              icon="currency-usd"
              style={{backgroundColor: 'black'}}
            />
          )}
          right={props => (
            <Text style={styles.paymentStatus}>Pagamento em dia</Text>
          )}
        />
        <Card.Content>
          <Text style={styles.planValue}>{ formatarParaReal(emp?.valor ?? 0) }</Text>
          <Text style={styles.monthlyFee}>Mensalidade de { formatarParaReal( emp?.parcelas[0].valor ?? 0 ) } </Text>
        </Card.Content>
        <Card.Actions>
          <Button onPress={moveToDetalhesEmprestimos} mode="outlined">
            Ver detalhes{' '}
          </Button>
        </Card.Actions>
      </Card>

      <Text style={styles.sectionTitle}>Você também pode</Text>

      <List.Section>
        <View style={styles.cardItem}>
          <List.Item
            title="Acompanhar mensalidades"
            titleStyle={styles.title}
            description="área de pagamentos"
            descriptionStyle={styles.description}
            left={props => (
              <List.Icon {...props} icon="currency-usd" color="#000" />
            )}
            right={props => (
              <List.Icon {...props} icon="chevron-right" color="#aaa" />
            )}
            onPress={() => console.log('Acessar benefícios pressed')}
          />
        </View>

        <View style={styles.cardItem}>
          <List.Item
            title="Acessar benefícios"
            titleStyle={styles.title}
            description="AGE benefícios"
            descriptionStyle={styles.description}
            left={props => <List.Icon {...props} icon="gift" color="#000" />}
            right={props => (
              <List.Icon {...props} icon="chevron-right" color="#aaa" />
            )}
            onPress={() => console.log('Acessar benefícios pressed')}
          />
        </View>

        <View style={styles.cardItem}>
          <List.Item
            title="Recomendar"
            titleStyle={styles.title}
            description="e ganhar R$ 200/indicação"
            descriptionStyle={styles.description}
            left={props => (
              <List.Icon {...props} icon="account-multiple-plus" color="#000" />
            )}
            right={props => (
              <List.Icon {...props} icon="chevron-right" color="#aaa" />
            )}
            onPress={() => console.log('Acessar benefícios pressed')}
          />
        </View>
      </List.Section>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#fff',
  },
  greeting: {
    marginLeft: 20,
    marginTop: 40,
    fontSize: 22,
    fontWeight: 'bold',
    marginBottom: 4,
  },
  sectionTitle: {
    marginLeft: 20,
    fontSize: 16,
    marginTop: 16,
    marginBottom: 8,
    fontWeight: '500',
  },
  planCard: {
    margin: 20,
    backgroundColor: '#f9f9f9',
    paddingHorizontal: 5,
    paddingBottom: 5,
  },
  planValue: {
    fontSize: 20,
    fontWeight: 'bold',
  },
  monthlyFee: {
    fontSize: 12,
    color: '#555',
    marginTop: 4,
  },
  paymentStatus: {
    color: 'green',
    fontWeight: '500',
    marginRight: 10,
    alignSelf: 'left',
  },
  cardItem: {
    backgroundColor: '#f4f4f4',
    borderRadius: 16,
    marginHorizontal: 16,
    marginVertical: 8,
    elevation: 0, // sem sombra
  },
  title: {
    fontWeight: 'bold',
    fontSize: 14,
    color: '#000',
  },
  description: {
    fontSize: 13,
    color: '#666',
  },
  headerContainer: {
    backgroundColor: '#fff',
    paddingTop: 50,
    paddingHorizontal: 20,
    paddingBottom: 16,
  },
  headerContent: {
    zIndex: 2,
  },
  logo: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#000',
  },
  dot: {
    color: '#fcbf49', // amarelo do "i"
  },
  greeting: {
    fontSize: 18,
    marginTop: 10,
    fontWeight: '600',
  },
  curvedCorner: {
    position: 'absolute',
    top: 0,
    right: 0,
    backgroundColor: '#fcbf49', // amarelo da imagem
    width: 150,
    height: 150,
    borderBottomLeftRadius: 150,
    zIndex: 0,
    justifyContent: 'flex-end',
    alignItems: 'flex-end',
    padding: 10,
  },
  menuIcon: {
    margin: 0,
  },
});

function Header({user}) {
  return (
    <View style={styles.headerContainer}>
      {/* Logo e título */}
      <View style={styles.headerContent}>
        <Text style={styles.logo}>
          AG<Text style={styles.dot}>E</Text>
        </Text>
        <Text style={styles.greeting}>Olá, {user?.nome_completo}</Text>
      </View>

      {/* Canto superior direito com a curva e os três pontos */}
      <View style={styles.curvedCorner}>
        {/* <IconButton
          icon="dots-vertical"
          size={20}
          iconColor="#000"
          style={styles.menuIcon}
          onPress={() => {}}
        /> */}
      </View>
    </View>
  );
}

export default HomeClienteScreen;
