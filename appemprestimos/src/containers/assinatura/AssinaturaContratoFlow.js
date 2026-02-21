import React, {useEffect, useMemo, useState} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Alert,
  Platform,
  PermissionsAndroid,
  Linking,
} from 'react-native';
import {Card, Text, Button, Checkbox} from 'react-native-paper';
import OTPInputView from '@twotalltotems/react-native-otp-input';
import {launchCamera} from 'react-native-image-picker';
import Pdf from 'react-native-pdf';
import ReactNativeBlobUtil from 'react-native-blob-util';

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
  const [pdfProgress, setPdfProgress] = useState(null);
  const [pdfError, setPdfError] = useState(null);
  const [pdfLocalUri, setPdfLocalUri] = useState(null);
  const [pdfDownloading, setPdfDownloading] = useState(false);

  const [docFrenteOk, setDocFrenteOk] = useState(false);
  const [docVersoOk, setDocVersoOk] = useState(false);
  const [selfieOk, setSelfieOk] = useState(false);
  const [videoOk, setVideoOk] = useState(false);

  const pdfRemoteUrl = useMemo(() => {
    if (!contratoId) return null;
    return `${baseUrl}/assinatura/contratos/${contratoId}/pdf-original`;
  }, [contratoId]);

  const buildPdfHeaders = async () => {
    const token = await getAuthToken();
    const company = await getAuthCompany();
    const headers = {};
    if (token) headers.Authorization = `Bearer ${token}`;
    if (company?.id) headers['company-id'] = String(company.id);
    return headers;
  };

  const baixarPdf = async () => {
    if (!pdfRemoteUrl) return;
    setPdfDownloading(true);
    setPdfError(null);
    setPdfProgress(0);
    setPdfLocalUri(null);
    try {
      const headers = await buildPdfHeaders();
      if (!headers.Authorization) {
        setPdfError({message: 'Sem token de autenticação.'});
        return;
      }

      const task = ReactNativeBlobUtil.config({
        fileCache: true,
        appendExt: 'pdf',
      }).fetch('GET', pdfRemoteUrl, headers);

      task.progress({interval: 200}, (received, total) => {
        if (!total || total <= 0) return;
        const percent = Math.round((received / total) * 100);
        setPdfProgress(Math.max(0, Math.min(100, percent)));
      });

      const res = await task;
      const info = res?.info?.() || {};
      const httpStatus = info.status;
      const contentType = info.headers?.['Content-Type'] || info.headers?.['content-type'];

      if (httpStatus >= 200 && httpStatus < 300) {
        const path = res.path();
        setPdfLocalUri(`file://${path}`);
        setPdfProgress(100);
        return;
      }

      let bodyText = '';
      try {
        bodyText = await res.text();
      } catch {
        bodyText = '';
      }
      setPdfError({
        message: `HTTP ${httpStatus} (content-type: ${contentType || '—'}) ${bodyText ? `\n${String(bodyText).slice(0, 300)}` : ''}`,
      });
    } catch (e) {
      setPdfError({message: String(e?.message || e)});
    } finally {
      setPdfDownloading(false);
    }
  };

  const ensureCameraPermissions = async mediaType => {
    if (Platform.OS !== 'android') return true;
    const toRequest = [PermissionsAndroid.PERMISSIONS.CAMERA];
    if (mediaType === 'video') {
      toRequest.push(PermissionsAndroid.PERMISSIONS.RECORD_AUDIO);
    }
    const res = await PermissionsAndroid.requestMultiple(toRequest);
    const camOk = res[PermissionsAndroid.PERMISSIONS.CAMERA] === PermissionsAndroid.RESULTS.GRANTED;
    const micOk =
      mediaType !== 'video' ||
      res[PermissionsAndroid.PERMISSIONS.RECORD_AUDIO] === PermissionsAndroid.RESULTS.GRANTED;
    if (!camOk || !micOk) {
      Alert.alert(
        'Permissão necessária',
        mediaType === 'video'
          ? 'Precisamos de permissão de câmera e microfone para gravar o vídeo.'
          : 'Precisamos de permissão de câmera para tirar a foto do documento.',
      );
      return false;
    }
    return true;
  };

  const testPdfEndpoint = async () => {
    if (!pdfRemoteUrl) return;
    try {
      const headers = await buildPdfHeaders();
      const head = await fetch(pdfRemoteUrl, {
        method: 'HEAD',
        headers,
      });
      if (head.ok) {
        Alert.alert('PDF OK', `Resposta: ${head.status} (${head.headers.get('content-type') || '—'})`);
        return;
      }
      // Alguns servidores não suportam HEAD. Tenta Range (1 byte).
      const range = await fetch(pdfRemoteUrl, {
        method: 'GET',
        headers: {
          ...headers,
          Range: 'bytes=0-0',
        },
      });
      Alert.alert(
        'Teste PDF',
        `HEAD: ${head.status}\nGET Range: ${range.status} (${range.headers.get('content-type') || '—'})`,
      );
    } catch (e) {
      Alert.alert('Erro ao testar PDF', String(e?.message || e));
    }
  };

  useEffect(() => {
    if (!contratoId) return;
    baixarPdf();
  }, [contratoId, pdfRemoteUrl]);

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
      const allowed = await ensureCameraPermissions(mediaType);
      if (!allowed) return;

      const cameraType =
        tipo === 'selfie' || tipo === 'video' ? 'front' : 'back';
      const res = await launchCamera({
        mediaType,
        cameraType,
        quality: 0.8,
        saveToPhotos: false,
      });

      if (res?.didCancel) {
        return;
      }

      if (res?.errorCode) {
        Alert.alert('Câmera', res?.errorMessage || `Erro: ${res.errorCode}`);
        return;
      }

      const asset = res?.assets?.[0];
      if (!asset?.uri) {
        Alert.alert('Câmera', 'Não foi possível obter o arquivo capturado.');
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
            {pdfLocalUri ? (
              <Pdf
                source={{uri: pdfLocalUri}}
                style={styles.pdf}
                onError={e => {
                  setPdfError(e);
                  console.log('PDF error', e);
                  Alert.alert(
                    'Erro ao carregar PDF',
                    'Não foi possível abrir o PDF baixado. Tente baixar novamente.',
                  );
                }}
              />
            ) : (
              <Text style={styles.muted}>
                {pdfDownloading ? 'Baixando PDF...' : 'PDF não carregado.'}
              </Text>
            )}
          </View>

          <View style={styles.spacer} />
          <Text style={styles.muted}>
            Progresso: {pdfProgress != null ? `${pdfProgress}%` : '—'}
          </Text>
          {pdfError ? <Text style={styles.muted}>Erro: {String(pdfError?.message || pdfError)}</Text> : null}

          <View style={styles.spacer} />
          <Button
            mode="outlined"
            disabled={!pdfRemoteUrl}
            onPress={() => {
              if (!pdfRemoteUrl) return;
              Linking.openURL(pdfRemoteUrl).catch(() =>
                Alert.alert('Erro', 'Não foi possível abrir o link do PDF.'),
              );
            }}>
            Abrir PDF no navegador
          </Button>
          <View style={styles.spacer} />
          <Button mode="outlined" disabled={!pdfRemoteUrl} onPress={testPdfEndpoint}>
            Testar download do PDF
          </Button>
          <View style={styles.spacer} />
          <Button mode="outlined" loading={pdfDownloading} disabled={!pdfRemoteUrl || pdfDownloading} onPress={baixarPdf}>
            Baixar novamente
          </Button>
          <View style={styles.spacer} />
          <Text style={styles.muted}>Base URL: {baseUrl}</Text>
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

