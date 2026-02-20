import React, {useCallback, useState} from 'react';
import {View, StyleSheet, FlatList, RefreshControl} from 'react-native';
import {Card, Text, Button, ActivityIndicator} from 'react-native-paper';
import {useFocusEffect} from '@react-navigation/native';

import api from '../../services/api';
import {StackNav} from '../../navigation/navigationKeys';
import {colors} from '../../themes/colors';

function statusLabel(s) {
  if (!s) return 'Não iniciado';
  const map = {
    pending_acceptance: 'Pendente de aceite',
    evidence_pending: 'Aguardando evidências',
    evidence_submitted: 'Evidências enviadas',
    otp_pending: 'Aguardando 2FA',
    signed_pending_review: 'Assinado (aguardando revisão)',
    signed: 'Assinado (aprovado)',
    rejected: 'Reprovado',
    resubmit_required: 'Reenvio solicitado',
  };
  return map[s] || s;
}

export default function AssinaturaContratos({navigation}) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [contratos, setContratos] = useState([]);

  const carregar = async () => {
    setLoading(true);
    try {
      const res = await api.assinaturaContratos();
      if (res?.error) {
        setContratos([]);
      } else {
        setContratos(res?.data || []);
      }
    } catch (e) {
      setContratos([]);
    } finally {
      setLoading(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      carregar();
    }, []),
  );

  const onRefresh = async () => {
    setRefreshing(true);
    await carregar();
    setRefreshing(false);
  };

  const renderItem = ({item}) => (
    <Card style={styles.card}>
      <Card.Content>
        <Text variant="titleMedium">Contrato #{item.id}</Text>
        <Text style={styles.muted}>Status: {statusLabel(item.assinatura_status)}</Text>
      </Card.Content>
      <Card.Actions>
        <Button
          mode="contained"
          onPress={() =>
            navigation.navigate(StackNav.AssinaturaContratoFlow, {
              contratoId: item.id,
            })
          }>
          Abrir
        </Button>
      </Card.Actions>
    </Card>
  );

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator animating color={colors.primary || '#fcbf49'} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={contratos}
        keyExtractor={item => String(item.id)}
        renderItem={renderItem}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[colors.primary || '#fcbf49']}
          />
        }
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.muted}>Nenhum contrato pendente.</Text>
          </View>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#fff', padding: 12},
  card: {marginBottom: 10},
  muted: {color: '#666', marginTop: 4},
  empty: {padding: 20, alignItems: 'center'},
  center: {flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#fff'},
});

