import React, {useEffect, useMemo, useState} from 'react';
import {View, StyleSheet, ScrollView, Alert, Platform} from 'react-native';
import {Card, Text, Button, Checkbox, TextInput} from 'react-native-paper';
import OTPInputView from '@twotalltotems/react-native-otp-input';
import {launchCamera} from 'react-native-image-picker';
import Pdf from 'react-native-pdf';

import api from '../../services/api';
import {baseUrl} from '../../services/Config';
import {getAuthToken, getAuthCompany} from '../../utils/asyncStorage';
import {colors} from '../../themes/colors';

function deviceInfo() {
  return {
    os: Platform.OS,
    os_version: String(Platform.Version),
  };
}

export default function AssinaturaContratoFlow({route}) {
  const contratoId = route?.params?.contratoId;
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(null);
  const [aceito, setAceito] = useState(false);
  const [otp, setOtp] = useState('');
  const [desafio, setDesafio] = useState(null);

  const [docFrenteOk, setDocFrenteOk] = useState(false);
  const [docVersoOk, setDocVersoOk] = useState(false);
  const [selfieOk, setSelfieOk] = useState(false);
  const [videoOk, setVideoOk] = useState(false);

  const pdfSource = useMemo(() => {
    return (async () => {
      const token = await getAuthToken();
      const company = await getAuthCompany();
      return {
        uri: `${baseUrl}/assinatura/contratos/${contratoId}/pdf-original`,
        headers: {
          Authorization: `Bearer ${token}`,
          'company-id': company?.id,
        },
        cache: true,
      };
    })();
  }, [contratoId]);

  const [pdfResolvedSource, setPdfResolvedSource] = useState(null);

  useEffect(() => {
    (async () => {
      const src = await pdfSource;
      setPdfResolvedSource(src);
    })();
  }, [pdfSource]);

  const refreshStatus = async () => {
    // A API do app não tem endpoint de detalhes; usamos o list para pegar o status.
    const res = await api.assinaturaContratos();
    const list = res?.data || [];
    const item = (list || []).find(x => Number(x.id) === Number(contratoId));
    if (item) setStatus(item.assinatura_status);
  };

  useEffect(() => {
    refreshStatus();
  }, [contratoId]);

  const aceitar = async () => {
    setLoading(true);
    try {
      const res = await api.assinaturaAceite(contratoId, deviceInfo());
      if (res?.error) {
        Alert.alert('Erro', res.message || 'Não foi possível aceitar.');
      } else {
        Alert.alert('Ok', 'Aceite registrado.');
        setStatus(res?.assinatura_status || 'evidence_pending');
      }
    } finally {
      setLoading(false);
    }
  };

  const uploadEvidencia = async (tipo, setOk, extra = {}) => {
    setLoading(true);
    try {
      const mediaType = tipo === 'video' ? 'video' : 'photo';
      const res = await launchCamera({mediaType, cameraType: 'front', quality: 0.8});
      const asset = res?.assets?.[0];
      if (!asset?.uri) {
        setLoading(false);
        return;
      }

      const up = await api.assinaturaUploadEvidencia(contratoId, {
        tipo,
        uri: asset.uri,
        name: asset.fileName || `${tipo}.${tipo === 'video' ? 'mp4' : 'jpg'}`,
        type: asset.type || (tipo === 'video' ? 'video/mp4' : 'image/jpeg'),
        captured_at: new Date().toISOString(),
        device: deviceInfo(),
        ...extra,
      });

      if (up?.error) {
        Alert.alert('Erro', up.message || 'Falha ao enviar evidência.');
      } else {
        setOk(true);
        Alert.alert('Ok', 'Evidência enviada.');
        setStatus(up?.assinatura_status || status);
      }
    } finally {
      setLoading(false);
    }
  };

  const gerarDesafio = async () => {
    setLoading(true);
    try {
      const res = await api.assinaturaDesafioVideo(contratoId, deviceInfo());
      if (res?.error) {
        Alert.alert('Erro', res.message || 'Falha ao gerar desafio.');
      } else {
        setDesafio(res?.desafio || null);
      }
    } finally {
      setLoading(false);
    }
  };

  const enviarOtp = async () => {
    setLoading(true);
    try {
      const res = await api.assinaturaEnviarOtp(contratoId, deviceInfo());
      if (res?.error) {
        Alert.alert('Erro', res.message || 'Falha ao enviar código.');
      } else {
        Alert.alert('Código enviado', 'Verifique seu WhatsApp.');
        setStatus(res?.assinatura_status || 'otp_pending');
      }
    } finally {
      setLoading(false);
    }
  };

  const validarOtp = async () => {
    if (!otp) return;
    setLoading(true);
    try {
      const res = await api.assinaturaValidarOtp(contratoId, otp, deviceInfo());
      if (res?.error) {
        Alert.alert('Erro', res.message || 'Código inválido.');
      } else {
        Alert.alert('Ok', 'Código validado.');
      }
    } finally {
      setLoading(false);
    }
  };

  const finalizar = async () => {
    setLoading(true);
    try {
      const res = await api.assinaturaFinalizar(contratoId, deviceInfo());
      if (res?.error) {
        Alert.alert('Erro', res.message || 'Falha ao finalizar.');
      } else {
        Alert.alert('Assinatura finalizada', 'Contrato enviado para revisão.');
        setStatus(res?.assinatura_status || 'signed_pending_review');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.title}>Assinatura do Contrato #{contratoId}</Text>
      <Text style={styles.muted}>Status: {status || '—'}</Text>

      <Card style={styles.card}>
        <Card.Title title="Documento" />
        <Card.Content>
          <View style={styles.pdfBox}>
            {pdfResolvedSource ? (
              <Pdf
                source={pdfResolvedSource}
                style={styles.pdf}
                onError={e => console.log('PDF error', e)}
              />
            ) : (
              <Text style={styles.muted}>Carregando PDF...</Text>
            )}
          </View>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="1) Aceite" />
        <Card.Content>
          <View style={styles.row}>
            <Checkbox
              status={aceito ? 'checked' : 'unchecked'}
              onPress={() => setAceito(v => !v)}
            />
            <Text style={styles.flex}>Li e concordo com os termos</Text>
          </View>
          <Button
            mode="contained"
            loading={loading}
            disabled={!aceito || loading}
            onPress={aceitar}>
            Confirmar aceite
          </Button>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="2) Evidências" />
        <Card.Content>
          <Button
            mode={docFrenteOk ? 'contained' : 'outlined'}
            loading={loading}
            disabled={loading}
            onPress={() => uploadEvidencia('doc_frente', setDocFrenteOk)}>
            Documento (frente)
          </Button>
          <View style={styles.spacer} />
          <Button
            mode={docVersoOk ? 'contained' : 'outlined'}
            loading={loading}
            disabled={loading}
            onPress={() => uploadEvidencia('doc_verso', setDocVersoOk)}>
            Documento (verso)
          </Button>
          <View style={styles.spacer} />
          <Button
            mode={selfieOk ? 'contained' : 'outlined'}
            loading={loading}
            disabled={loading}
            onPress={() => uploadEvidencia('selfie', setSelfieOk)}>
            Selfie
          </Button>

          <View style={styles.hr} />
          <Text style={styles.muted}>
            Opcional: vídeo com desafio (maior segurança)
          </Text>
          <View style={styles.spacer} />
          <Button mode="outlined" loading={loading} disabled={loading} onPress={gerarDesafio}>
            Gerar desafio de vídeo
          </Button>
          {desafio?.texto ? (
            <>
              <Text style={styles.desafio}>{desafio.texto}</Text>
              <Button
                mode={videoOk ? 'contained' : 'outlined'}
                loading={loading}
                disabled={loading}
                onPress={() =>
                  uploadEvidencia('video', setVideoOk, {desafio_id: desafio.id})
                }>
                Gravar e enviar vídeo
              </Button>
            </>
          ) : null}
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="3) 2FA (WhatsApp)" />
        <Card.Content>
          <Button mode="outlined" loading={loading} disabled={loading} onPress={enviarOtp}>
            Enviar código
          </Button>
          <View style={styles.spacer} />
          <OTPInputView
            style={styles.otp}
            pinCount={6}
            code={otp}
            onCodeChanged={setOtp}
            autoFocusOnLoad={false}
            codeInputFieldStyle={styles.otpCell}
            codeInputHighlightStyle={styles.otpCellActive}
          />
          <Button
            mode="contained"
            loading={loading}
            disabled={loading || otp.length !== 6}
            onPress={validarOtp}>
            Validar código
          </Button>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="4) Finalizar" />
        <Card.Content>
          <Text style={styles.muted}>
            Ao finalizar, o sistema gera o PDF final (com registro) e envia para revisão.
          </Text>
          <View style={styles.spacer} />
          <Button mode="contained" loading={loading} disabled={loading} onPress={finalizar}>
            Finalizar assinatura
          </Button>
        </Card.Content>
      </Card>

      <View style={{height: 24}} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {flex: 1, backgroundColor: '#fff', padding: 12},
  title: {fontSize: 18, fontWeight: '700'},
  muted: {color: '#666', marginBottom: 6},
  card: {marginTop: 12},
  row: {flexDirection: 'row', alignItems: 'center'},
  flex: {flex: 1},
  spacer: {height: 10},
  hr: {height: 1, backgroundColor: '#eee', marginVertical: 12},
  desafio: {marginVertical: 10, fontWeight: '600', color: colors.primary || '#fcbf49'},
  pdfBox: {height: 380, borderWidth: 1, borderColor: '#ddd', borderRadius: 8, overflow: 'hidden'},
  pdf: {flex: 1, width: '100%'},
  otp: {height: 90},
  otpCell: {
    width: 40,
    height: 45,
    borderWidth: 1,
    borderRadius: 8,
    borderColor: '#ddd',
    color: '#111',
    fontSize: 18,
  },
  otpCellActive: {
    borderColor: colors.primary || '#fcbf49',
  },
});

