/**
 * Calculadora de empréstimo Price - lógica idêntica à página admin (system.escsolutions.ai)
 * IOF: prazo médio ponderado (diário = dias corridos; semanal/mensal = média ponderada pela amortização)
 * CET: via IRR e conversões (dia→365, semana→52, mês→12)
 */

const IOF_DAILY_RATE_STD = 0.000082;     // 0,0082% ao dia (padrão)
const IOF_DAILY_RATE_SIMPLES = 0.0000274; // 0,00274% ao dia (Simples até 30.000)
const IOF_ADDITIONAL_RATE = 0.0038;      // 0,38%

const DAYS_PER_MONTH_DAILY = 30;       // para converter taxa mensal -> diária
const DAYS_PER_MONTH_WEEKLY = 30.415;  // para converter taxa mensal -> semanal

const MS_PER_DAY = 86400000;

function round2(x) {
  return Math.round((x + Number.EPSILON) * 100) / 100;
}

function parseISODate(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  return new Date(y, m - 1, d);
}

function addDays(date, days) {
  const d = new Date(date);
  d.setDate(d.getDate() + days);
  return d;
}

function addMonths(date, months) {
  const d = new Date(date);
  d.setMonth(d.getMonth() + months);
  return d;
}

function diffDays(a, b) {
  return Math.round((b - a) / MS_PER_DAY);
}

function ratePerPeriodFromMonthly(monthlyRate, periodo) {
  const p = String(periodo).toUpperCase();
  if (p === 'MENSAL') return monthlyRate;
  if (p === 'DIARIO' || p === 'DIÁRIO') return Math.pow(1 + monthlyRate, 1 / DAYS_PER_MONTH_DAILY) - 1;
  if (p === 'SEMANAL') return Math.pow(1 + monthlyRate, 7 / DAYS_PER_MONTH_WEEKLY) - 1;
  return monthlyRate;
}

function pricePayment(pv, i, n) {
  if (n <= 0) return 0;
  if (Math.abs(i) < 1e-12) return pv / n;
  return (pv * i) / (1 - Math.pow(1 + i, -n));
}

function amortWeightsPrice(i, n) {
  const pv = 1;
  const pmt = pricePayment(pv, i, n);
  let bal = pv;
  const w = [];
  for (let k = 1; k <= n; k++) {
    const juros = bal * i;
    const amort = pmt - juros;
    bal -= amort;
    w.push(amort);
  }
  return w;
}

function dataParaLocal(date) {
  const d = new Date(date);
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

function formatarDataDDMMYYYY(date) {
  const d = new Date(date);
  const dia = String(d.getDate()).padStart(2, '0');
  const mes = String(d.getMonth() + 1).padStart(2, '0');
  const ano = d.getFullYear();
  return `${dia}/${mes}/${ano}`;
}

function isFeriado(date, feriados) {
  const dataStr = formatarDataDDMMYYYY(date);
  return feriados.some((f) => f.data_feriado === dataStr);
}

function buildDueDates(periodo, primeiraDate, n, intervalo = null, opcaoCobranca = null, feriados = []) {
  const dates = [];
  const p = String(periodo).toUpperCase();
  const step = intervalo != null ? Number(intervalo) : (p === 'DIARIO' || p === 'DIÁRIO' ? 1 : p === 'SEMANAL' ? 7 : 30);
  const opc = String(opcaoCobranca || '');
  let dataInicial = dataParaLocal(primeiraDate);

  for (let k = 1; k <= n; k++) {
    if (k > 1) {
      if (p === 'MENSAL' && (intervalo == null || intervalo === 30)) {
        dataInicial = addMonths(dataInicial, 1);
      } else {
        dataInicial.setDate(dataInicial.getDate() + step);
      }
    }

    if (opc === '1') {
      while (dataInicial.getDay() === 0 || dataInicial.getDay() === 6) {
        dataInicial.setDate(dataInicial.getDate() + 1);
      }
    } else if (opc === '2') {
      while (dataInicial.getDay() === 0) {
        dataInicial.setDate(dataInicial.getDate() + 1);
      }
    }

    if (isFeriado(dataInicial, feriados)) {
      dataInicial.setDate(dataInicial.getDate() + 1);
      if (opc === '1') {
        while (dataInicial.getDay() === 0 || dataInicial.getDay() === 6) {
          dataInicial.setDate(dataInicial.getDate() + 1);
        }
      } else if (opc === '2') {
        while (dataInicial.getDay() === 0) {
          dataInicial.setDate(dataInicial.getDate() + 1);
        }
      }
      if (isFeriado(dataInicial, feriados)) {
        dataInicial.setDate(dataInicial.getDate() + 1);
      }
    }

    dates.push(new Date(dataInicial));
  }
  return dates;
}

function computePrazoMedioDias(periodo, contratoDate, dueDates, i, n) {
  if (n <= 0) return 0;

  const p = String(periodo).toUpperCase();

  if (p === 'DIARIO' || p === 'DIÁRIO') {
    const last = dueDates[dueDates.length - 1];
    return Math.min(Math.max(diffDays(contratoDate, last), 0), 365);
  }

  const weights = amortWeightsPrice(i, n);
  let prazo = 0;
  for (let k = 0; k < n; k++) {
    const d = Math.min(Math.max(diffDays(contratoDate, dueDates[k]), 0), 365);
    prazo += weights[k] * d;
  }
  return prazo;
}

function irrFromCashflows(principal, payment, n) {
  if (n <= 0) return 0;
  if (payment <= 0) return 0;

  let r = 0.05;
  for (let it = 0; it < 50; it++) {
    let f = principal;
    let df = 0;

    for (let k = 1; k <= n; k++) {
      const denom = Math.pow(1 + r, k);
      f -= payment / denom;
      df -= (-k) * payment / (denom * (1 + r));
    }

    const step = f / df;
    r -= step;

    if (!Number.isFinite(r)) r = 0.01;
    if (r <= -0.9999) r = -0.9999;
    if (Math.abs(step) < 1e-12) break;
  }

  if (!Number.isFinite(r) || r < -0.999 || r > 10) {
    let lo = -0.9, hi = 10;
    for (let it = 0; it < 80; it++) {
      const mid = (lo + hi) / 2;
      let pv = 0;
      for (let k = 1; k <= n; k++) pv += payment / Math.pow(1 + mid, k);
      if (pv > principal) lo = mid;
      else hi = mid;
    }
    r = (lo + hi) / 2;
  }

  return r;
}

function annualFromPeriodIRR(periodo, r) {
  const p = String(periodo).toUpperCase();
  if (p === 'DIARIO' || p === 'DIÁRIO') return Math.pow(1 + r, 365) - 1;
  if (p === 'SEMANAL') return Math.pow(1 + r, 52) - 1;
  return Math.pow(1 + r, 12) - 1;
}

function formatDateISO(date) {
  const d = new Date(date);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

/**
 * Converte taxa do período para taxa mensal (para exibição)
 */
export function periodRateToMonthly(periodRate, periodo) {
  const p = String(periodo).toUpperCase();
  if (p === 'MENSAL') return periodRate;
  if (p === 'DIARIO' || p === 'DIÁRIO') return Math.pow(1 + periodRate, DAYS_PER_MONTH_DAILY) - 1;
  if (p === 'SEMANAL') return Math.pow(1 + periodRate, DAYS_PER_MONTH_WEEKLY / 7) - 1;
  return periodRate;
}

/**
 * Calcula simulação completa (Price + IOF + CET)
 * @param {Object} params
 * @param {number} params.valorSolicitado
 * @param {string} params.periodoAmortizacao - 'Diário'|'Semanal'|'Mensal'
 * @param {number} params.quantidadeParcelas
 * @param {number} params.taxaJurosMensal - em decimal (ex: 0.20 para 20%) - usado quando definicaoTaxa='taxa_juros'
 * @param {number} params.valorParcela - valor fixo da parcela - usado quando definicaoTaxa='valor_parcela'
 * @param {string} params.definicaoTaxa - 'valor_parcela' | 'taxa_juros'
 * @param {Date|string} params.dataAssinatura
 * @param {Date|string} params.dataPrimeiraParcela
 * @param {number} params.intervalo - dias entre parcelas (opcional)
 * @param {string} params.opcaoCobranca - '1' Segunda-Sexta | '2' Segunda-Sábado | '3' Segunda-Domingo
 * @param {Array} params.feriados - lista de { data_feriado: 'DD/MM/YYYY' }
 * @param {boolean} params.calcularIof
 * @param {boolean} params.simplesNacional
 * @returns {Object} Resultado no formato esperado pelo template Vue
 */
export function calculateLoan(params) {
  const {
    valorSolicitado,
    periodoAmortizacao,
    quantidadeParcelas,
    taxaJurosMensal,
    valorParcela,
    definicaoTaxa = 'taxa_juros',
    dataAssinatura,
    dataPrimeiraParcela,
    intervalo,
    opcaoCobranca,
    feriados = [],
    calcularIof = true,
    simplesNacional = false,
  } = params;

  const principal = Number(valorSolicitado) || 0;
  const n = Math.max(1, Math.floor(Number(quantidadeParcelas) || 1));

  const contratoDate = dataAssinatura instanceof Date ? dataAssinatura : new Date(dataAssinatura);
  const primeiraDate = dataPrimeiraParcela instanceof Date ? dataPrimeiraParcela : new Date(dataPrimeiraParcela);

  const periodo = periodoAmortizacao || 'Diário';
  const dueDates = buildDueDates(periodo, primeiraDate, n, intervalo, opcaoCobranca, feriados);

  let i, parcela, taxaMensal;
  let iofDaily = 0, iofAdd = 0, iofTotal = 0, prazoMedio = 0;
  const usaTaxaSimples = simplesNacional && principal <= 30000;
  const dailyRate = usaTaxaSimples ? IOF_DAILY_RATE_SIMPLES : IOF_DAILY_RATE_STD;

  if (definicaoTaxa === 'valor_parcela' && valorParcela != null && Number(valorParcela) > 0) {
    parcela = Number(valorParcela);
    let valorContratoEst = principal;
    for (let iter = 0; iter < 5; iter++) {
      i = irrFromCashflows(valorContratoEst, parcela, n);
      prazoMedio = computePrazoMedioDias(periodo, contratoDate, dueDates, i, n);
      iofDaily = calcularIof ? principal * dailyRate * prazoMedio : 0;
      iofAdd = calcularIof ? principal * IOF_ADDITIONAL_RATE : 0;
      iofTotal = iofDaily + iofAdd;
      const vcNew = principal + iofTotal;
      if (Math.abs(vcNew - valorContratoEst) < 0.01) break;
      valorContratoEst = vcNew;
    }
    i = irrFromCashflows(principal + iofTotal, parcela, n);
    taxaMensal = periodRateToMonthly(i, periodo);
  } else {
    taxaMensal = Number(taxaJurosMensal) || 0;
    i = ratePerPeriodFromMonthly(taxaMensal, periodo);
    parcela = null;
    if (calcularIof) {
      prazoMedio = computePrazoMedioDias(periodo, contratoDate, dueDates, i, n);
      iofDaily = principal * dailyRate * prazoMedio;
      iofAdd = principal * IOF_ADDITIONAL_RATE;
      iofTotal = iofDaily + iofAdd;
    }
  }

  const valorContrato = principal + iofTotal;
  const parcelaValor = parcela != null ? parcela : pricePayment(valorContrato, i, n);

  // Cronograma
  let bal = valorContrato;
  const cronograma = [];

  for (let k = 1; k <= n; k++) {
    const juros = bal * i;
    const amort = parcelaValor - juros;
    bal -= amort;
    if (k === n && Math.abs(bal) < 1e-7) bal = 0;

    cronograma.push({
      numero: k,
      parcela: round2(parcelaValor).toFixed(2),
      vencimento: formatDateISO(dueDates[k - 1]),
      juros: round2(juros).toFixed(2),
      amortizacao: round2(amort).toFixed(2),
      saldo_devedor: round2(bal).toFixed(2),
    });
  }

  // CET
  const rPeriod = irrFromCashflows(principal, parcelaValor, n);
  const cetAno = annualFromPeriodIRR(periodo, rPeriod);
  const cetMes = Math.pow(1 + cetAno, 1 / 12) - 1;

  const totalParcelas = parcelaValor * n;

  return {
    inputs: {
      valor_solicitado: principal.toFixed(2),
      taxa_juros_mensal: taxaMensal.toFixed(2),
      definicao_taxa: definicaoTaxa,
      valor_parcela: definicaoTaxa === 'valor_parcela' ? parcelaValor.toFixed(2) : undefined,
      quantidade_parcelas: n,
      periodo_amortizacao: periodo,
      intervalo: intervalo != null ? intervalo : undefined,
      opcao_cobranca: opcaoCobranca || undefined,
      modelo_amortizacao: 'Price',
      data_assinatura: formatDateISO(contratoDate),
      data_primeira_parcela: formatDateISO(primeiraDate),
      simples_nacional: simplesNacional,
      calcular_iof: !!calcularIof,
    },
    iof: {
      adicional: round2(iofAdd).toFixed(2),
      diario: round2(iofDaily).toFixed(2),
      total: round2(iofTotal).toFixed(2),
      aliquota_diaria: usaTaxaSimples ? '0,00274%' : '0,0082%',
    },
    valor_contrato: round2(valorContrato).toFixed(2),
    parcela: round2(parcelaValor).toFixed(2),
    cronograma,
    totais: {
      total_parcelas: round2(totalParcelas).toFixed(2),
      cet_mes: (cetMes * 100).toFixed(2),
      cet_ano: (cetAno * 100).toFixed(2),
      juros_acerto: '0.00',
    },
  };
}
