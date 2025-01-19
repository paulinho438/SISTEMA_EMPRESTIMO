import {
  StyleSheet,
  View,
  Image,
  Text,
  Linking,
  Alert,
  ScrollView,
  TextInput,
} from 'react-native';
import React, {useRef, useEffect, useState} from 'react';
import ActionSheet from 'react-native-actions-sheet';
import Community from 'react-native-vector-icons/MaterialCommunityIcons';

// Local imports
import images from '../../assets/images/index';
import {moderateScale} from '../../common/constant';
import {styles} from '../../themes/index';
import CText from '../common/CText';
import {colors} from '../../themes/colors';
import CButton from '../common/CButton';
import {TouchableOpacity} from 'react-native-gesture-handler';
import {useNavigation} from '@react-navigation/native';
import {StackNav} from '../../navigation/navigationKeys';
import api from '../../services/api';
import TelaAprovacaoTitulo from './TelaAprovacaoTitulo';

import FullScreenLoader from '../FullScreenLoader';

export default function EmprestimosPendentesHoje(props) {
  let {sheetRef, parcelas, clientes, titulosPendentes, onAtualizarClientes} =
    props;
  const navigation = useNavigation();
  const [visible, setVisible] = useState(false);
  const [cliente, setCliente] = useState({});
  const [searchText, setSearchText] = useState('');

  const textInputRef = useRef(null);

  const obterDataAtual = () => {
    const data = new Date();
    const ano = data.getFullYear();
    let mes = data.getMonth() + 1; // Os meses vão de 0 a 11 em JavaScript, então adicionamos 1
    let dia = data.getDate();

    // Adicionar um zero à esquerda se o mês ou o dia for menor que 10
    mes = mes < 10 ? '0' + mes : mes;
    dia = dia < 10 ? '0' + dia : dia;

    return `${ano}-${mes}-${dia}`;
  };

  const baixaManual = async () => {
    let req = await api.baixaManual(parcelas.id, obterDataAtual());

    Alert.alert('Baixa realizada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const cobrarAmanha = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobrança alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const infoParcelas = async () => {
    let req = await api.cobrarAmanha(parcelas.id, obterDataAtual());

    Alert.alert('Cobrança alterada com sucesso!');

    navigation.navigate(StackNav.TabNavigation);
  };

  const cancelModel = () => {
    sheetRef.current?.hide();
  };

  const onPressClose = item => {
    if (item?.id) {
      setCliente(item);
    } else {
      onAtualizarClientes();
    }
    setVisible(!visible);
  };

  const onPressCloseExcluir = async item => {
    if (item?.fornecedor) {
      let req = await api.reprovarPagamentoContasAPagar(item.id);
      alert('Pagamento reprovado com sucesso!');
      onAtualizarClientes();
    } else {
      let req = await api.reprovarEmprestimo(item.emprestimo.id);
      alert('Pagamento reprovado com sucesso!');
      onAtualizarClientes();
    }
  };

  const formatDate = dateStr => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
  };

  // Filtrar parcelas com base no texto de pesquisa
  const filteredtitulosPendentes = titulosPendentes.filter(item => {
    const clienteNome = item.cliente?.nome_completo?.toLowerCase() || '';
    const fornecedorNome = item.fornecedor?.nome_completo?.toLowerCase() || '';
    const searchTextLower = searchText.toLowerCase();

    return (
      clienteNome.includes(searchTextLower) ||
      fornecedorNome.includes(searchTextLower)
    );
  });

  // Ordenar os itens para que aqueles com valor_recebido_pix igual a null venham primeiro
  // const sortedtitulosPendentes = filteredtitulosPendentes.sort((a, b) => {
  //   const aValorRecebidoPix = a.parcelas_vencidas[0]?.valor_recebido_pix;
  //   const bValorRecebidoPix = b.parcelas_vencidas[0]?.valor_recebido_pix;

  //   if (aValorRecebidoPix === null && bValorRecebidoPix !== null) {
  //     return -1;
  //   }
  //   if (aValorRecebidoPix !== null && bValorRecebidoPix === null) {
  //     return 1;
  //   }
  //   return 0;
  // });

  return (
    <View>
      <ActionSheet
        containerStyle={localStyles.actionSheet}
        ref={sheetRef}
        onOpen={() => {
          textInputRef.current?.focus();
        }}>
        {/* <FullScreenLoader visible={filteredtitulosPendentes.length == 0} /> */}
        <View
          style={{
            flexDirection: 'row',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}>
          <TextInput
            ref={textInputRef}
            style={localStyles.searchInput}
            placeholder="Pesquisar pelo nome do cliente"
            value={searchText}
            onChangeText={setSearchText}
          />
          <TouchableOpacity
            style={localStyles.parentDepEnd}
            onPress={cancelModel}>
            <Community size={40} name={'close'} color={colors.black} />
          </TouchableOpacity>
        </View>

        <ScrollView showsVerticalScrollIndicator={false}>
          <View style={localStyles.mainContainer}>
            <View style={localStyles.outerComponent}>
              <View style={{gap: moderateScale(7)}}>
                <CText color={colors.black} type={'B24'}>
                  Títulos Pendentes para Hoje
                </CText>
              </View>
            </View>

            {filteredtitulosPendentes.map(item => (
              <View key={item.id} style={styles2.container}>
                {item?.fornecedor && (
                  <>
                    <Text style={styles2.title}>{item.descricao}</Text>
                    {item.wallet && (
                      <Text style={styles2.subTitleRed}>
                        <Text style={{fontWeight: 'bold'}}>
                          Saldo no banco wallet:{' '}
                        </Text>
                        {item.saldo.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                      </Text>
                    )}
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>Valor a Pagar: </Text>
                      {item.valor.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                      })}
                    </Text>

                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>
                        Nome Fornecedor:{' '}
                      </Text>
                      {item.fornecedor.nome_completo}
                    </Text>

                    <View style={styles2.buttonContainer}>
                      <TouchableOpacity
                        onPress={() => onPressCloseExcluir(item)}
                        style={styles2.actionButton}>
                        <Text style={styles2.buttonText}>Reprovar</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        onPress={() => onPressClose(item)}
                        style={styles2.actionButtonSuccess}>
                        <Text style={styles2.buttonTextSuccess}>
                          Efetuar Pagamento
                        </Text>
                      </TouchableOpacity>
                      {/* <Text style={styles2.valorHoje}>
                    Valor Hoje {item.saldoatrasado.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL',
            })}
                  </Text> */}
                    </View>
                  </>
                )}

                {item?.emprestimo && (
                  <>
                    <Text style={styles2.title}>{item.descricao}</Text>
                    {item.banco?.wallet && (
                      <Text style={styles2.subTitleRed}>
                        <Text style={{fontWeight: 'bold'}}>
                          Saldo no banco wallet:{' '}
                        </Text>
                        {item.banco?.saldo.toLocaleString('pt-BR', {
                          style: 'currency',
                          currency: 'BRL',
                        })}
                      </Text>
                    )}
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>Valor a Pagar: </Text>
                      {item.valor.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                      })}
                    </Text>
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>
                        Valor a Receber:{' '}
                      </Text>
                      {(
                        item.emprestimo.parcelas[0].valor *
                        item.emprestimo.parcelas.length
                      ).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                      })}
                    </Text>
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>Lucro: </Text>
                      {item.emprestimo.lucro.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                      })}
                    </Text>
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>Juros: </Text>
                      {item.emprestimo.juros}%
                    </Text>
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>
                        Quantidade de parcelas:{' '}
                      </Text>
                      {item.qt_parcelas}
                    </Text>
                    <Text style={styles2.subTitle}>
                      <Text style={{fontWeight: 'bold'}}>
                        Valor da parcela:{' '}
                      </Text>
                      {item.emprestimo.parcelas[0].valor.toLocaleString(
                        'pt-BR',
                        {
                          style: 'currency',
                          currency: 'BRL',
                        },
                      )}
                    </Text>

                    <View style={styles2.buttonContainer}>
                      <TouchableOpacity
                        onPress={() => onPressCloseExcluir(item)}
                        style={styles2.actionButton}>
                        <Text style={styles2.buttonText}>Reprovar</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        onPress={() => onPressClose(item)}
                        style={styles2.actionButtonSuccess}>
                        <Text style={styles2.buttonTextSuccess}>
                          Efetuar Pagamento
                        </Text>
                      </TouchableOpacity>
                      {/* <Text style={styles2.valorHoje}>
                    Valor Hoje {item.saldoatrasado.toLocaleString('pt-BR', {
              style: 'currency',
              currency: 'BRL',
            })}
                  </Text> */}
                    </View>
                  </>
                )}
              </View>
            ))}
          </View>
          <TelaAprovacaoTitulo
            visible={visible}
            onPressClose={onPressClose}
            cliente={cliente}
            tela="baixa_pendentes_hoje"
            //valores={valores}
            //feriados={feriados}
          />
        </ScrollView>
      </ActionSheet>
    </View>
  );
}

const styles2 = StyleSheet.create({
  container: {
    padding: 20,
    backgroundColor: '#F7F6F6FF',
    flex: 1,
    marginTop: 10,
    marginBottom: 10,
    borderRadius: 20,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 5,
  },
  subTitle: {
    fontSize: 14,
    color: '#888',
    marginBottom: 10,
  },
  subTitleRed: {
    fontSize: 14,
    color: '#F00',
    marginBottom: 10,
  },
  subTitleValor: {
    fontSize: 14,
    color: '#3CA454FF',
    marginTop: -15,
    marginBottom: 20,
  },
  addressLabel: {
    fontSize: 16,
    marginBottom: 5,
  },
  phoneButton: {
    backgroundColor: '#f1f1f1',
    padding: 10,
    borderRadius: 5,
    marginBottom: 10,
    alignItems: 'center',
  },
  phoneText: {
    fontSize: 16,
    color: '#000',
  },
  infoText: {
    fontSize: 16,
    marginBottom: 5,
  },
  totalDueText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#d9534f',
    marginBottom: 20,
  },
  buttonContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    backgroundColor: '#ececec',
    paddingVertical: 10,
    paddingHorizontal: 15,
    borderRadius: 5,
  },
  actionButtonSuccess: {
    backgroundColor: '#17a2b8',
    paddingVertical: 10,
    paddingHorizontal: 15,
    borderRadius: 5,
  },
  buttonText: {
    fontSize: 14,
  },
  buttonTextSuccess: {
    fontSize: 14,
    color: '#fff',
  },
  valorHoje: {
    fontSize: 14,
    color: '#666',
    marginTop: 15,
  },
});

const localStyles = StyleSheet.create({
  imgSty: {
    width: moderateScale(330),
    height: moderateScale(100),
    ...styles.selfCenter,
  },
  actionSheet: {
    borderTopLeftRadius: moderateScale(40),
    borderTopRightRadius: moderateScale(40),
  },
  mainContainer: {
    ...styles.m20,
  },
  outerComponent: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
  },
  outerComponent2: {
    ...styles.flexRow,
    ...styles.alignCenter,
    ...styles.justifyBetween,
    ...styles.mt50,
  },
  imageStyle: {
    width: moderateScale(40),
    height: moderateScale(40),
  },
  mainComponent: {
    gap: moderateScale(10),
    ...styles.justifyEvenly,
    ...styles.alignCenter,
    ...styles.p15,
    ...styles.mh5,
    width: moderateScale(101),
    height: moderateScale(106),
    borderWidth: moderateScale(1),
    borderRadius: moderateScale(16),
    borderColor: colors.bottomBorder,
  },
  outerContainer: {
    ...styles.mt25,
  },
  buttonContainer: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.red,
  },
  buttonContainerRed: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.red,
  },
  buttonContainerGreen: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Green,
  },
  buttonContainerPrimary: {
    ...styles.flexRow,
    ...styles.justifyBetween,
    ...styles.ph20,
    backgroundColor: colors.Primary,
  },
  parentDepEnd: {
    ...styles.alignEnd,
    ...styles.mr25,
    ...styles.mt30,
    ...styles.mb20,
  },
  searchInput: {
    flex: 1,
    height: moderateScale(40),
    borderColor: '#c6c6c6',
    borderWidth: 1,
    borderRadius: 5,
    paddingHorizontal: 10,
    marginRight: moderateScale(10),
    marginLeft: moderateScale(20),
  },
});
