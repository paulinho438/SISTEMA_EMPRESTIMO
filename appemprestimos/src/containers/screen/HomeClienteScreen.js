import React, {useEffect, useState, useCallback} from 'react';
import {useFocusEffect} from '@react-navigation/native';
import {
  View,
  ScrollView,
  StyleSheet,
  Text,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import {Card, Button, List, Avatar} from 'react-native-paper';
import {useNavigation} from '@react-navigation/native';
import {StackNav} from '../../navigation/navigationKeys';
import api from '../../services/api';
import {getUser} from '../../utils/asyncStorage';

const HomeClienteScreen = () => {
  const navigation = useNavigation();

  const [user, setUser] = useState(null);
  const [emp, setEmp] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useFocusEffect(
    useCallback(() => {
      const fetchData = async () => {
        loadData();
      };
      fetchData();
      return () => {};
    }, []),
  );

  const loadData = async () => {
    setLoading(true);
    const userReq = await getUser();
    setUser(userReq);
    await getInformacoesEmprestimo();
    setLoading(false);
  };

  const getInformacoesEmprestimo = async () => {
    const response = await api.getInformacoesEmprestimo();
    if (response.emprestimos.length > 0) {
      setEmp(response.emprestimos[0]);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  const formatarParaReal = valor => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      minimumFractionDigits: 2,
    }).format(valor);
  };

  const moveToDetalhesEmprestimos = () => {
    navigation.navigate(StackNav.DetalhesEmprestimos, {
      emprestimo: emp,
      user: user,
    });
  };

  const moveToAcompanharMensalidades = () => {
    navigation.navigate(StackNav.AcompanharMensalidades, {
      emprestimo: emp,
      user: user,
    });
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#fcbf49" />
      </View>
    );
  }

  const moveToPixQuitacao = () => {
    navigation.navigate(StackNav.PixParcela, {
      emprestimo: emp,
      parcela: {
        chave_pix: emp?.quitacao?.chave_pix,
        valor: emp?.quitacao?.valor,
        title: 'Quitar Empréstimo',
        msgPagamento: 'Copie o código Pix e pague no aplicativo do seu banco. O pagamento é confirmado na hora.',
      },
    });
  };

  const moveToPixSaldoPendente = () => {
    navigation.navigate(StackNav.PixParcela, {
      emprestimo: emp,
      parcela: {
        chave_pix: emp?.pagamentosaldopendente?.chave_pix,
        valor: emp?.pagamentosaldopendente?.valor,
        title: 'Saldo Pendente',
        msgPagamento: 'Copie o código Pix e pague no aplicativo do seu banco. O pagamento é confirmado na hora.',
      },
    });
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          colors={['#fcbf49']}
        />
      }>
      <Header user={user} />
      <Text style={styles.sectionTitle}>Seu plano</Text>

      <Card style={styles.planCard}>
        <Card.Title
          title={'#' + (emp?.id ?? '0000')}
          left={props => (
            <Avatar.Icon
              {...props}
              size={30}
              icon="currency-usd"
              style={{backgroundColor: 'black'}}
            />
          )}
          right={props => (
            <Text
              style={{
                ...styles.paymentStatus,
                color:
                  emp?.status === 'Em Dias' || emp?.status === 'Pago'
                    ? 'green'
                    : 'red',
              }}>
              {emp?.status}
            </Text>
          )}
        />
        <Card.Content>
          <Text style={styles.planValue}>
            {formatarParaReal(emp?.valor ?? 0)}
          </Text>
          <Text style={styles.monthlyFee}>
            Mensalidade de {emp?.parcelas?.[0]?.valor}
          </Text>
        </Card.Content>
        <Card.Actions>
          <Button onPress={moveToDetalhesEmprestimos} mode="outlined">
            Ver detalhes
          </Button>
        </Card.Actions>
      </Card>

      <Text style={styles.sectionTitle}>Você também pode</Text>

      <List.Section>
        <View style={styles.cardItem}>
          <List.Item
            title="Acompanhar mensalidades"
            titleStyle={styles.title}
            description="Área de pagamentos"
            descriptionStyle={styles.description}
            left={props => (
              <List.Icon {...props} icon="currency-usd" color="#000" />
            )}
            right={props => (
              <List.Icon {...props} icon="chevron-right" color="#aaa" />
            )}
            onPress={moveToAcompanharMensalidades}
          />
        </View>

        {emp?.pagamentosaldopendente != null && (
          <View style={styles.cardItem}>
            <List.Item
              title="Saldo pendente"
              titleStyle={styles.title}
              description="Realizar pagamento do saldo pendente do empréstimo"
              descriptionStyle={styles.description}
              left={props => (
                <List.Icon {...props} icon="currency-usd" color="#000" />
              )}
              right={props => (
                <List.Icon {...props} icon="chevron-right" color="#aaa" />
              )}
              onPress={moveToPixSaldoPendente}
            />
          </View>
        )}

        {emp?.quitacao != null && (
          <View style={styles.cardItem}>
            <List.Item
              title="Quitar empréstimo"
              titleStyle={styles.title}
              description="Realizar pagamento do saldo total do empréstimo"
              descriptionStyle={styles.description}
              left={props => (
                <List.Icon {...props} icon="currency-usd" color="#000" />
              )}
              right={props => (
                <List.Icon {...props} icon="chevron-right" color="#aaa" />
              )}
              onPress={moveToPixQuitacao}
            />
          </View>
        )}

        {emp?.pagamentominimo != null && (
          <View style={styles.cardItem}>
            <List.Item
              title="Pagamento minimo"
              titleStyle={styles.title}
              description="Realizar pagamento mínimo do empréstimo"
              descriptionStyle={styles.description}
              left={props => (
                <List.Icon {...props} icon="currency-usd" color="#000" />
              )}
              right={props => (
                <List.Icon {...props} icon="chevron-right" color="#aaa" />
              )}
              onPress={moveToAcompanharMensalidades}
            />
          </View>
        )}

        {/* <View style={styles.cardItem}>
          <List.Item
            title="Acessar benefícios"
            titleStyle={styles.title}
            description="AGE benefícios"
            descriptionStyle={styles.description}
            left={props => <List.Icon {...props} icon="gift" color="#000" />}
            right={props => <List.Icon {...props} icon="chevron-right" color="#aaa" />}
            onPress={() => console.log('Ir para benefícios')}
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
            right={props => <List.Icon {...props} icon="chevron-right" color="#aaa" />}
            onPress={() => console.log('Ir para recomendação')}
          />
        </View> */}
      </List.Section>
    </ScrollView>
  );
};

const Header = ({user}) => (
  <View style={styles.headerContainer}>
    <View style={styles.headerContent}>
      <Text style={styles.logo}>
        AG<Text style={styles.dot}>E</Text>
      </Text>
      <Text style={styles.greeting}>Olá, {user?.nome_completo}</Text>
    </View>
    <View style={styles.curvedCorner} />
  </View>
);

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#fff',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
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
    elevation: 0,
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
    color: '#fcbf49',
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
    backgroundColor: '#fcbf49',
    width: 150,
    height: 150,
    borderBottomLeftRadius: 150,
    zIndex: 0,
  },
});

export default HomeClienteScreen;
