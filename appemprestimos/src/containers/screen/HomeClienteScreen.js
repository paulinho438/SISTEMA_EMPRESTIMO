import React, {useEffect, useState, useCallback, useMemo} from 'react';
import {useFocusEffect} from '@react-navigation/native';
import {
  View,
  ScrollView,
  StyleSheet,
  Text,
  ActivityIndicator,
  Platform,
  PermissionsAndroid,
  RefreshControl,
} from 'react-native';
import {Card, Button, List, Avatar} from 'react-native-paper';
import {useNavigation, useRoute} from '@react-navigation/native';
import {StackNav} from '../../navigation/navigationKeys';
import api from '../../services/api';
import Geolocation from 'react-native-geolocation-service';
import BackgroundGeolocation from 'react-native-background-geolocation';

import {
  getAuthCompany,
  getUser,
  getPermissions,
} from '../../utils/asyncStorage';

const HomeClienteScreen = ({navigation}) => {
  const route = useRoute();

  const [user, setUser] = useState(null);
  const [emp, setEmp] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      requestLocationPermission();
    });

    return unsubscribe;
  }, [navigation, route.params?.onNavigateBack]);

  useEffect(() => {
    requestLocationPermission();
  }, []);    

  const requestLocationPermission = async () => {
    if (Platform.OS === 'ios') {
      const auth = await Geolocation.requestAuthorization('whenInUse');
      if (auth === 'granted') {
      } else {
        console.log('Location permission denied on iOS');
      }
      return;
    }

    // Solicita primeiro a permissão de localização fina
    const grantedFine = await PermissionsAndroid.request(
      PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION,
      {
        title: 'Permissão de Localização',
        message:
          'Precisamos da sua localização para o app funcionar corretamente',
        buttonNeutral: 'Perguntar depois',
        buttonNegative: 'Cancelar',
        buttonPositive: 'OK',
      },
    );

    if (grantedFine !== PermissionsAndroid.RESULTS.GRANTED) {
      console.log('Permissão de localização negada');
      return;
    }

    // Após `ACCESS_FINE_LOCATION`, solicita `ACCESS_BACKGROUND_LOCATION`
    const grantedBackground = await PermissionsAndroid.request(
      PermissionsAndroid.PERMISSIONS.ACCESS_BACKGROUND_LOCATION,
      {
        title: 'Permissão de Localização em Background',
        message: 'Precisamos da sua localização mesmo com o app fechado',
        buttonNeutral: 'Perguntar depois',
        buttonNegative: 'Cancelar',
        buttonPositive: 'OK',
      },
    );

    if (grantedBackground !== PermissionsAndroid.RESULTS.GRANTED) {
      console.log('Permissão de localização em background negada');
    }
  };

  useEffect(async () => {
    const userReq = await getUser();
    let authCompany = await getAuthCompany();

    console.log(
      'Iniciando BackgroundGeolocation com usuário:',
      userReq
    );

    console.log(
      'Iniciando BackgroundGeolocation com empresa:',
      authCompany);
    // 1.  Subscribe to events.
    const onLocation = BackgroundGeolocation.onLocation(location => {
      informarLocalizacao(location);
    });

    const onMotionChange = BackgroundGeolocation.onMotionChange(event => {
      console.log('[onMotionChange]', event);
      informarLocalizacao(event);
    });

    const onActivityChange = BackgroundGeolocation.onActivityChange(event => {
      console.log('[onActivityChange]', event);
    });

    const onProviderChange = BackgroundGeolocation.onProviderChange(event => {
      console.log('[onProviderChange]', event);
    });

    /// 2. ready the plugin.
    BackgroundGeolocation.ready({
      // Geolocation Config
      desiredAccuracy: BackgroundGeolocation.DESIRED_ACCURACY_HIGH,
      distanceFilter: 5,
      // Activity Recognition
      stopTimeout: 5,
      // Application config
      debug: false, // <-- enable this hear sounds for background-geolocation life-cycle.
      logLevel: BackgroundGeolocation.LOG_LEVEL_VERBOSE,
      stopOnTerminate: false, // <-- Allow the background-service to continue tracking when user closes the app.
      startOnBoot: true, // <-- Auto start tracking when device is powered-up.
      // HTTP / SQLite config
      url: 'https://api.agecontrole.com.br/api/informar_localizacao_app',
      batchSync: false, // <-- [Default: false] Set true to sync locations to server in a single HTTP request.
      autoSync: true, // <-- [Default: true] Set true to sync each location to server as it arrives.
      params: {
        user_id: userReq?.id,
        company_id: authCompany?.id,
        tipoUsuario: 'cliente',
      },
    })
      .then(state => {
        console.log(
          '- BackgroundGeolocation is configured and ready: ',
          state.enabled,
        );

        if (!state.enabled) {
          BackgroundGeolocation.start(() => {
            console.log('- Start success');
          });
        }
      })
      .catch(error => {
        console.error('BackgroundGeolocation ready() failed: ', error);
      });

    return () => {
      console.log('Cleaning up BackgroundGeolocation events...');
      if (onLocation) onLocation.remove();
      if (onMotionChange) onMotionChange.remove();
      if (onActivityChange) onActivityChange.remove();
      if (onProviderChange) onProviderChange.remove();
    };
  }, []);

  useFocusEffect(
    useCallback(() => {
      const fetchData = async () => {
        loadData();
      };
      fetchData();
      return () => {};
    }, []),
  );

  const informarLocalizacao = async position => {
    const userReq = await getUser();

    let dados = {
      user_id: userReq.id,
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
    };

    await api.informarLocalizacao(dados);
  };

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
        msgPagamento:
          'Copie o código Pix e pague no aplicativo do seu banco. O pagamento é confirmado na hora.',
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
        msgPagamento:
          'Copie o código Pix e pague no aplicativo do seu banco. O pagamento é confirmado na hora.',
      },
    });
  };

  const moveToPixPagamentoMinimo = () => {
    navigation.navigate(StackNav.PixParcela, {
      emprestimo: emp,
      parcela: {
        chave_pix: emp?.pagamentominimo?.chave_pix,
        valor: emp?.pagamentominimo?.valor,
        title: 'Pagamento Mínimo',
        msgPagamento:
          'Copie o código Pix e pague no aplicativo do seu banco. O pagamento é confirmado na hora.',
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

      <Text style={styles.sectionTitle}>Assinatura</Text>
      <Card style={styles.planCard}>
        <Card.Title
          title="Contratos pendentes"
          subtitle="Assine seus contratos diretamente no app"
          left={props => (
            <Avatar.Icon
              {...props}
              size={30}
              icon="file-sign"
              style={{backgroundColor: 'black'}}
            />
          )}
        />
        <Card.Actions>
          <Button
            onPress={() => navigation.navigate(StackNav.AssinaturaContratos)}
            mode="contained">
            Ver contratos
          </Button>
        </Card.Actions>
      </Card>

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
          {emp && (
            <Text style={styles.monthlyFee}>
            Mensalidade de {emp?.parcelas?.[0]?.valor}
          </Text>
          )}
          
        </Card.Content>
        <Card.Actions>
          {emp && (
            <Button onPress={moveToDetalhesEmprestimos} mode="outlined">
              Ver detalhes
            </Button>
          )}
        </Card.Actions>
      </Card>

      {emp && (
        <>
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
                  onPress={moveToPixPagamentoMinimo}
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
        </>
      )}
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
    alignSelf: 'flex-start',
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
