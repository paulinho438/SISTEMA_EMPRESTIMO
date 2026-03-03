import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { calculateLoan, periodRateToMonthly } from '@/utils/loanCalculator';
import { valorPorExtenso } from '@/utils/valorPorExtenso';
import { CLAUSULAS_CONTRATO } from '@/utils/clausulasContrato';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export function useLoanSimulation() {
    const loading = ref(false);
    const saving = ref(false);
    const error = ref(null);
    const result = ref(null);
    const debounceTimer = ref(null);

    const form = reactive({
        client_id: null,
        banco_id: null,
        tipo_operacao: 'Empréstimo',
        valor_solicitado: '500.00',
        periodo_amortizacao: 'Diário',
        modelo_amortizacao: 'Price',
        definicao_taxa: 'taxa_juros', // 'valor_parcela' | 'taxa_juros'
        quantidade_parcelas: 20,
        taxa_juros_mensal: '20.00',
        valor_parcela: null,
        opcao_cobranca: null,
        data_assinatura: null,
        data_primeira_parcela: null,
        calcular_iof: true,
        cliente_simples_nacional: false,
        garantias: [], // Array de { tipo, pessoa_id?, dados: {} }
        inadimplencia: {
            multa_percentual: 2,
            juros_mora_diario: 0.1,
        },
    });

    // Inicializar datas padrão
    const hoje = new Date();
    const amanha = new Date(hoje);
    amanha.setDate(amanha.getDate() + 1);
    
    if (!form.data_assinatura) {
        form.data_assinatura = hoje;
    }
    if (!form.data_primeira_parcela) {
        form.data_primeira_parcela = amanha;
    }

    /**
     * Formata data para input HTML (YYYY-MM-DD)
     */
    function formatDateForInput(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Formata valor monetário para exibição (R$ 1.234,56)
     */
    function formatCurrency(value) {
        if (!value && value !== 0) return 'R$ 0,00';
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return num.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });
    }

    /**
     * Formata percentual para exibição (12,34%)
     */
    function formatPercent(value) {
        if (!value && value !== 0) return '0,00%';
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return num.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + '%';
    }

    /**
     * Formata data para exibição (DD/MM/YYYY)
     * Evita timezone: ISO YYYY-MM-DD é interpretado como UTC e desloca 1 dia no Brasil
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            const [y, m, d] = dateString.split('-');
            return `${d}/${m}/${y}`;
        }
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    /**
     * Formata data de feriado para DD/MM/YYYY (formato esperado pelo loanCalculator)
     */
    function formatFeriado(f) {
        if (!f) return '';
        if (typeof f === 'string' && /^\d{2}\/\d{2}\/\d{4}$/.test(f)) return f;
        const d = f instanceof Date ? f : new Date(f);
        const dia = String(d.getDate()).padStart(2, '0');
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const ano = d.getFullYear();
        return `${dia}/${mes}/${ano}`;
    }

    /**
     * Converte valor formatado BR para número
     */
    function parseCurrency(value) {
        if (!value && value !== 0) return '0';
        
        // Se já for número, retornar como string
        if (typeof value === 'number') {
            return String(value);
        }
        
        // Converter string para número
        let str = String(value);
        
        // Remover formatação brasileira (R$, espaços)
        str = str.replace(/R\$/g, '').replace(/\s/g, '');
        
        // Se tem vírgula, assumir formato brasileiro (1.234,56)
        if (str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
        }
        // Se não tem vírgula mas tem ponto, pode ser formato americano (1234.56)
        // Nesse caso, manter como está
        
        // Converter para número e depois para string para garantir formato correto
        const num = parseFloat(str);
        return isNaN(num) ? '0' : String(num);
    }

    /**
     * Converte taxa de percentual para decimal
     */
    function parseTaxa(value) {
        const num = parseFloat(value);
        // Se maior que 1, assumir que está em percentual
        return num > 1 ? num / 100 : num;
    }

    /**
     * Remove acentos de uma string
     */
    function removeAccents(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    /**
     * Converte Date para string YYYY-MM-DD
     */
    function dateToString(date) {
        if (!date) return null;
        const d = date instanceof Date ? date : new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Define data da primeira parcela conforme período (como na página admin)
     */
    function setDefaultFirstDue() {
        const periodo = form.periodo_amortizacao;
        const contrato = form.data_assinatura ? new Date(form.data_assinatura) : new Date();

        let primeira;
        if (periodo === 'Diário') {
            primeira = new Date(contrato);
            primeira.setDate(primeira.getDate() + 1);
        } else if (periodo === 'Semanal') {
            primeira = new Date(contrato);
            primeira.setDate(primeira.getDate() + 7);
        } else {
            primeira = new Date(contrato);
            primeira.setMonth(primeira.getMonth() + 1);
        }

        form.data_primeira_parcela = primeira;
    }

    /**
     * Simula empréstimo com cálculo local (lógica idêntica à página admin)
     */
    async function simulate() {
        loading.value = true;
        error.value = null;

        try {
            const valorSolicitado = typeof form.valor_solicitado === 'number'
                ? form.valor_solicitado
                : parseFloat(parseCurrency(form.valor_solicitado)) || 0;

            const taxaPeriodoDecimal = parseTaxa(form.taxa_juros_mensal);
            const periodo = form.periodo_amortizacao || 'Diário';
            const taxaDecimal = periodo === 'Mensal'
                ? taxaPeriodoDecimal
                : periodRateToMonthly(taxaPeriodoDecimal, periodo);
            const dataAssinatura = form.data_assinatura instanceof Date
                ? form.data_assinatura
                : new Date(form.data_assinatura);
            let dataPrimeira = form.data_primeira_parcela instanceof Date
                ? form.data_primeira_parcela
                : new Date(form.data_primeira_parcela);
            dataPrimeira = new Date(dataPrimeira.getFullYear(), dataPrimeira.getMonth(), dataPrimeira.getDate());

            const definicaoTaxa = form.definicao_taxa || 'taxa_juros';
            const valorParcela = definicaoTaxa === 'valor_parcela'
                ? (typeof form.valor_parcela === 'number' ? form.valor_parcela : parseFloat(parseCurrency(form.valor_parcela)) || 0)
                : null;

            let feriados = [];
            try {
                const res = await axios.get(`${apiPath}/feriados`);
                feriados = res.data?.data || [];
            } catch {
                feriados = [];
            }
            const feriadosFormatados = feriados.map((f) => ({
                data_feriado: typeof f.data_feriado === 'string' ? f.data_feriado : formatFeriado(f.data_feriado),
            }));

            result.value = calculateLoan({
                valorSolicitado,
                periodoAmortizacao: form.periodo_amortizacao,
                quantidadeParcelas: form.quantidade_parcelas,
                taxaJurosMensal: taxaDecimal,
                definicaoTaxa,
                valorParcela: valorParcela > 0 ? valorParcela : null,
                dataAssinatura,
                dataPrimeiraParcela: dataPrimeira,
                intervalo: null,
                opcaoCobranca: form.opcao_cobranca != null ? String(form.opcao_cobranca) : null,
                feriados: feriadosFormatados,
                calcularIof: form.calcular_iof ?? true,
                simplesNacional: Boolean(form.cliente_simples_nacional),
            });
        } catch (err) {
            error.value = err.message || 'Erro ao simular empréstimo';
            result.value = null;
            console.error('Erro na simulação:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Simula com debounce (300ms)
     */
    function simulateDebounced() {
        if (debounceTimer.value) {
            clearTimeout(debounceTimer.value);
        }
        debounceTimer.value = setTimeout(() => {
            simulate();
        }, 300);
    }

    /**
     * Valida se primeira parcela >= data assinatura e campos obrigatórios conforme definicao_taxa
     */
    const isValid = computed(() => {
        if (!form.data_assinatura || !form.data_primeira_parcela) {
            return false;
        }
        const dataAssinatura = form.data_assinatura instanceof Date 
            ? form.data_assinatura 
            : new Date(form.data_assinatura);
        const dataPrimeira = form.data_primeira_parcela instanceof Date 
            ? form.data_primeira_parcela 
            : new Date(form.data_primeira_parcela);
        if (dataPrimeira < dataAssinatura) return false;

        const def = form.definicao_taxa || 'taxa_juros';
        if (def === 'valor_parcela') {
            const vp = typeof form.valor_parcela === 'number' ? form.valor_parcela : parseFloat(parseCurrency(form.valor_parcela));
            return Number(form.quantidade_parcelas) > 0 && vp > 0;
        }
        const taxa = parseFloat(form.taxa_juros_mensal);
        return Number(form.quantidade_parcelas) > 0 && !isNaN(taxa) && taxa >= 0.01;
    });

    /**
     * Exporta simulação para JSON
     */
    function exportJSON() {
        if (!result.value) return;
        
        const dataStr = JSON.stringify(result.value, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `simulacao-emprestimo-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Exporta simulação para CSV
     */
    function exportCSV() {
        if (!result.value || !result.value.cronograma) return;

        const headers = ['#', 'Parcela', 'Data de Vencimento', 'Juros', 'Amortização', 'Saldo Devedor'];
        const rows = result.value.cronograma.map(p => [
            p.numero,
            p.parcela,
            formatDate(p.vencimento),
            p.juros,
            p.amortizacao,
            p.saldo_devedor,
        ]);

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.join(',')),
        ].join('\n');

        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `simulacao-emprestimo-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Exporta simulação para PDF
     */
    function exportPDF() {
        if (!result.value || !result.value.cronograma) return;

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const r = result.value;
        const pageWidth = doc.internal.pageSize.getWidth();
        let y = 20;

        // Título
        doc.setFontSize(18);
        doc.text('Simulação de Empréstimo', pageWidth / 2, y, { align: 'center' });
        y += 12;

        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');

        // Dados da operação
        doc.setFont(undefined, 'bold');
        doc.text('Dados da Operação', 14, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        doc.text(`Valor solicitado: ${formatCurrency(r.inputs.valor_solicitado)}`, 14, y);
        y += 6;
        doc.text(`Período: ${r.inputs.periodo_amortizacao} | Parcelas: ${r.inputs.quantidade_parcelas}`, 14, y);
        y += 6;
        doc.text(`Taxa mensal: ${r.inputs.taxa_juros_mensal}% | Data assinatura: ${formatDate(r.inputs.data_assinatura)}`, 14, y);
        y += 6;
        doc.text(`Data 1ª parcela: ${formatDate(r.inputs.data_primeira_parcela)} | Simples Nacional: ${r.inputs.simples_nacional ? 'Sim' : 'Não'}`, 14, y);
        y += 12;

        // Tabela de amortização
        const tableData = r.cronograma.map(p => [
            p.numero,
            p.parcela,
            formatDate(p.vencimento),
            p.juros,
            p.amortizacao,
            p.saldo_devedor,
        ]);

        autoTable(doc, {
            startY: y,
            head: [['#', 'Parcela', 'Vencimento', 'Juros', 'Amortização', 'Saldo Devedor']],
            body: tableData,
            theme: 'grid',
            styles: { fontSize: 8 },
            headStyles: { fillColor: [122, 61, 43] },
            margin: { left: 14, right: 14 },
        });

        y = doc.lastAutoTable.finalY + 12;

        // Outras informações
        doc.setFont(undefined, 'bold');
        doc.text('Outras Informações', 14, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        doc.text(`Data de assinatura e transferência: ${formatDate(r.inputs.data_assinatura)}`, 14, y);
        y += 6;
        doc.text(`Valor do IOF: ${formatCurrency(r.iof.total)} (diário ${formatCurrency(r.iof.diario)} + adicional ${formatCurrency(r.iof.adicional)})`, 14, y);
        y += 6;
        doc.text(`Valor do contrato: ${formatCurrency(r.valor_contrato)}`, 14, y);
        y += 6;
        doc.text(`Total das parcelas: ${formatCurrency(r.totais.total_parcelas)}`, 14, y);
        y += 6;
        doc.text(`CET ao mês: ${formatPercent(parseFloat(r.totais.cet_mes))} | CET ao ano: ${formatPercent(parseFloat(r.totais.cet_ano))}`, 14, y);
        y += 6;
        doc.text(`Juros de acerto: ${formatCurrency(r.totais.juros_acerto)}`, 14, y);
        y += 10;

        doc.setFontSize(8);
        doc.setTextColor(100, 100, 100);
        doc.text('* Cálculo: Price + IOF (diário + 0,38%). Regra Simples: se optante e valor ≤ 30.000, IOF diário = 0,00274%.', 14, y, { maxWidth: pageWidth - 28 });
        doc.setTextColor(0, 0, 0);

        const filename = `simulacao-emprestimo-${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(filename);
    }

    /**
     * Exporta Contrato Inicial em PDF (modelo de contrato de mútuo - 9 páginas)
     * options: { garantias, banco, bancoMutuario, numeroContrato, returnBlob, filename }
     */
    function exportContratoInicial(empresa = {}, cliente = {}, options = {}) {
        if (!result.value || !result.value.cronograma) return;

        const r = result.value;
        const garantias = options?.garantias || form.garantias || [];
        const banco = options?.banco || null;
        const bancoMutuario = options?.bancoMutuario || banco;
        const ano = new Date().getFullYear();
        const numeroContrato = options?.numeroContrato || `${ano}/000001`;

        const devedorSolidario = garantias.find((g) => g?.tipo === 'devedor_solidario')?.dados || null;

        const endEmpresaParts = [empresa.endereco, empresa.cidade, empresa.estado, empresa.cep ? `CEP ${empresa.cep}` : ''].filter(Boolean);
        const endEmpresaStr = endEmpresaParts.length ? endEmpresaParts.join(' - ') : 'Não informado';

        const endCliente = cliente.address?.[0];
        const endClienteStr = endCliente
            ? [endCliente.address, endCliente.number || '0', endCliente.complement || 'Sem complemento', endCliente.neighborhood, endCliente.city, endCliente.estado || '—', endCliente.cep ? `CEP ${endCliente.cep}` : ''].filter(Boolean).join(' - ')
            : 'Não informado';

        const endDevedor = devedorSolidario
            ? [devedorSolidario.endereco, devedorSolidario.numero || '0', devedorSolidario.complemento || 'Sem complemento', devedorSolidario.bairro, devedorSolidario.cidade, devedorSolidario.estado || '—', devedorSolidario.cep ? `CEP ${devedorSolidario.cep}` : ''].filter(Boolean).join(' - ')
            : endClienteStr;

        const periodo = String(r.inputs.periodo_amortizacao || 'Diário').toUpperCase();
        const taxaMensalDecimal = parseFloat(r.inputs.taxa_juros_mensal) || 0;
        const taxaPeriodo = periodo === 'MENSAL' ? taxaMensalDecimal : periodo === 'DIARIO' || periodo === 'DIÁRIO'
            ? Math.pow(1 + taxaMensalDecimal, 1 / 30) - 1
            : Math.pow(1 + taxaMensalDecimal, 7 / 30.415) - 1;
        const taxaPeriodoPercent = (taxaPeriodo * 100).toFixed(2).replace('.', ',');

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 14;
        const marginX = margin;

        let y = 20;

        // ========== PÁGINA 1: Cabeçalho + QUADRO 01 ==========
        autoTable(doc, {
            startY: y,
            head: [['Nº DO CONTRATO', 'VALOR DO CRÉDITO']],
            body: [[numeroContrato, formatCurrency(r.inputs.valor_solicitado)]],
            theme: 'plain',
            styles: { fontSize: 10 },
            headStyles: { fillColor: [240, 240, 240] },
            columnStyles: { 0: { cellWidth: 60 }, 1: { cellWidth: 60 } },
            margin: { left: marginX, right: margin },
        });
        y = doc.lastAutoTable.finalY + 12;

        doc.setFont(undefined, 'bold');
        doc.setFontSize(10);
        doc.text('QUADRO 01 - PARTES CONTRATANTES', marginX, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        doc.text('1. CONTRATADA MUTUANTE', marginX, y);
        y += 5;
        doc.text(`Razão Social: ${empresa.razao_social || empresa.company || '—'}`, marginX, y);
        y += 5;
        doc.text(`CNPJ: ${empresa.cnpj || '—'}`, marginX, y);
        y += 5;
        doc.text(`Endereço: ${endEmpresaStr}`, marginX, y, { maxWidth: pageWidth - 2 * margin });
        y += 5;
        doc.text(`Telefone: ${empresa.numero_contato || empresa.telefone || '—'}`, marginX, y);
        y += 5;
        doc.text(`E-mail: ${empresa.email || '—'}`, marginX, y);
        y += 5;
        doc.text(`Dados bancários: (i) Banco: ${empresa.banco_nome || 'Não informado'}; (ii) Agência: ${empresa.banco_agencia || 'Não informado'}; (iii) Conta: ${empresa.banco_conta || 'Não informado'}; (ix) Pix: ${empresa.banco_pix || 'Não informado'}`, marginX, y, { maxWidth: pageWidth - 2 * margin });
        y += 10;

        doc.text('2. REPRESENTANTE MUTUANTE', marginX, y);
        y += 5;
        doc.text(`Nome Completo: ${empresa.representante_nome || '—'}`, marginX, y);
        y += 5;
        doc.text(`CPF: ${empresa.representante_cpf || '—'}`, marginX, y);
        y += 5;
        doc.text(`RG e órgão Emissor: ${empresa.representante_rg || '—'} ${empresa.representante_orgao_emissor || ''}`, marginX, y);
        y += 5;
        doc.text(`Cargo: ${empresa.representante_cargo || '—'}`, marginX, y);
        y += 10;

        doc.text('3. CONTRATANTE MUTUÁRIA', marginX, y);
        y += 5;
        doc.text(`Razão Social: ${cliente.razao_social || cliente.nome_completo || '—'}`, marginX, y);
        y += 5;
        doc.text(`Nome de Fantasia: ${cliente.nome_fantasia || '—'}`, marginX, y);
        y += 5;
        doc.text(`CNPJ: ${cliente.cnpj || '—'}`, marginX, y);
        y += 5;
        doc.text(`Optante pelo Simples Nacional: ${r.inputs.simples_nacional ? 'Sim' : 'Não'}`, marginX, y);
        y += 5;
        doc.text(`Endereço: ${endClienteStr}`, marginX, y, { maxWidth: pageWidth - 2 * margin });
        y += 5;
        doc.text(`E-mail: ${cliente.email || '—'}`, marginX, y);
        y += 5;
        doc.text(`Telefone: ${cliente.telefone_celular_1 || cliente.telefone || '—'}`, marginX, y);
        y += 15;

        // ========== PÁGINA 2: Dados bancários mutuário + 4. REPRESENTANTE MUTUÁRIA + QUADRO 02 ==========
        doc.addPage();
        y = 20;

        doc.setFont(undefined, 'bold');
        doc.text('Dados bancários para a transferência em conta de titularidade do MUTUÁRIO:', marginX, y);
        y += 7;
        doc.setFont(undefined, 'normal');
        const bancoMut = bancoMutuario || {};
        const pixMut = cliente.pix_cliente || bancoMut.chavepix || 'Não informado';
        doc.text(`(i) Banco: ${bancoMut.name || 'Não informado'}; (ii) Agência: ${bancoMut.agencia || 'Não informado'}; (iii) Conta: ${bancoMut.conta || 'Não informado'}; (iv) Pix: ${pixMut}`, marginX, y, { maxWidth: pageWidth - 2 * margin });
        y += 12;

        doc.setFont(undefined, 'bold');
        doc.text('4. REPRESENTANTE MUTUÁRIA E DEVEDOR SOLIDÁRIO', marginX, y);
        y += 7;
        doc.setFont(undefined, 'normal');
        const repMut = devedorSolidario || cliente;
        doc.text(`Nome Completo: ${(repMut.nome_completo || repMut.razao_social || '—').toUpperCase()}`, marginX, y);
        y += 5;
        doc.text(`CPF: ${(repMut.cpf || '').replace(/\D/g, '') || '—'}`, marginX, y);
        y += 5;
        doc.text(`RG e órgão Emissor: ${repMut.rg || '—'} ${repMut.orgao_emissor || ''}`, marginX, y);
        y += 5;
        doc.text(`Estado Civil: ${repMut.estado_civil || '—'}`, marginX, y);
        y += 5;
        doc.text(`Regime de Comunhão de Bens: ${repMut.regime_bens || '—'}`, marginX, y);
        y += 5;
        doc.text(`Endereço: ${endDevedor}`, marginX, y, { maxWidth: pageWidth - 2 * margin });
        y += 5;
        doc.text(`E-mail: ${repMut.email || '—'}`, marginX, y);
        y += 5;
        doc.text(`Telefone: ${repMut.telefone || repMut.telefone_celular_1 || '—'}`, marginX, y);
        y += 12;

        doc.setFont(undefined, 'bold');
        doc.text('QUADRO 02 – COMPOSIÇÃO DO CRÉDITO', marginX, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        const quadro02Data = [
            ['Valor do Crédito', formatCurrency(r.inputs.valor_solicitado)],
            ['Quantidade de parcelas', String(r.inputs.quantidade_parcelas)],
            ['1° vencimento', formatDate(r.inputs.data_primeira_parcela)],
            ['Sistema de Amortização', r.inputs.modelo_amortizacao || 'Price'],
            ['Periodicidade da Capitalização de Juros', r.inputs.periodo_amortizacao || 'Diário'],
            ['Taxa de juros no período de capitalização (%)', taxaPeriodoPercent + '%'],
            ['Convenção de contagem de dias', '365 dias'],
            ['Valor de juros de acerto', formatCurrency(r.totais.juros_acerto)],
            ['IOF Adicional (alíquota 0,38%)', formatCurrency(r.iof.adicional)],
            ['IOF Diário (alíquota ' + (r.iof.aliquota_diaria || '0,0082%') + ')', formatCurrency(r.iof.diario)],
        ];
        autoTable(doc, {
            startY: y,
            body: quadro02Data,
            theme: 'plain',
            styles: { fontSize: 9 },
            columnStyles: { 0: { cellWidth: 100 }, 1: { cellWidth: 70 } },
            margin: { left: marginX, right: margin },
        });
        y = doc.lastAutoTable.finalY + 12;

        // ========== PÁGINA 3: Resumo + QUADRO 03 (início) ==========
        doc.addPage();
        y = 20;

        const resumoData = [
            ['Total IOF', formatCurrency(r.iof.total)],
            ['Valor do Contrato', formatCurrency(r.valor_contrato)],
            ['CET ao ano (%)', formatPercent(parseFloat(r.totais.cet_ano))],
            ['CET ao mês (%)', formatPercent(parseFloat(r.totais.cet_mes))],
            ['Valor total a prazo', formatCurrency(r.totais.total_parcelas)],
        ];
        autoTable(doc, {
            startY: y,
            body: resumoData,
            theme: 'plain',
            styles: { fontSize: 9 },
            columnStyles: { 0: { cellWidth: 80 }, 1: { cellWidth: 60 } },
            margin: { left: marginX, right: margin },
        });
        y = doc.lastAutoTable.finalY + 10;

        doc.setFont(undefined, 'bold');
        doc.text('QUADRO 03 – CRONOGRAMA DE REEMBOLSO', marginX, y);
        doc.text(`QUANTIDADE DE PARCELAS: ${r.inputs.quantidade_parcelas}`, pageWidth - margin - 50, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        const tableData = r.cronograma.map((p) => [
            String(p.numero).padStart(2, '0'),
            formatDate(p.vencimento),
            formatCurrency(p.parcela),
            formatCurrency(p.juros),
            formatCurrency(p.amortizacao),
            formatCurrency(p.saldo_devedor),
        ]);
        autoTable(doc, {
            startY: y,
            head: [['Parcela', 'Vencimento', 'Valor', 'Juros', 'Amortização', 'Saldo Devedor (após pagamento)']],
            body: tableData,
            theme: 'grid',
            styles: { fontSize: 7 },
            headStyles: { fillColor: [80, 80, 80] },
            margin: { left: marginX, right: margin },
            pageBreak: 'auto',
        });
        y = doc.lastAutoTable.finalY + 10;

        // ========== PÁGINAS 4-7: Cláusulas ==========
        const clausulasTexto = [
            CLAUSULAS_CONTRATO.clausula1,
            CLAUSULAS_CONTRATO.clausula2,
            CLAUSULAS_CONTRATO.clausula3,
            CLAUSULAS_CONTRATO.clausula4,
            CLAUSULAS_CONTRATO.clausula5,
            CLAUSULAS_CONTRATO.clausula6,
            CLAUSULAS_CONTRATO.clausula7,
            CLAUSULAS_CONTRATO.clausula8,
            CLAUSULAS_CONTRATO.clausula9,
            CLAUSULAS_CONTRATO.clausula10,
        ].join('\n\n');

        const clausulasLinhas = doc.splitTextToSize(clausulasTexto, pageWidth - 2 * margin);
        let yClaus = y;
        for (const linha of clausulasLinhas) {
            if (yClaus > pageHeight - 25) {
                doc.addPage();
                yClaus = margin;
            }
            doc.setFontSize(9);
            doc.text(linha, marginX, yClaus);
            yClaus += 5;
        }

        // ========== PÁGINA 9: Assinaturas ==========
        doc.addPage();
        const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        const dataAssinatura = r.inputs.data_assinatura;
        const d = typeof dataAssinatura === 'string' ? new Date(dataAssinatura + 'T12:00:00') : new Date(dataAssinatura);
        const cidadeAssin = empresa.cidade || 'Aparecida de Goiânia';
        const ufAssin = empresa.estado || 'GO';
        const dataExtenso = `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;

        y = 25;
        doc.setFontSize(10);
        doc.text(`${cidadeAssin}/${ufAssin}, ${dataExtenso}`, pageWidth / 2, y, { align: 'center' });
        y += 12;

        doc.setDrawColor(0, 0, 0);
        doc.setLineWidth(0.2);
        doc.line(marginX, y, pageWidth - margin, y);
        y += 15;

        doc.setFont(undefined, 'bold');
        doc.text('REPRESENTANTE MUTUANTE', pageWidth / 2, y, { align: 'center' });
        y += 8;
        doc.setFont(undefined, 'normal');
        doc.text((empresa.representante_nome || empresa.company || '—').trim(), pageWidth / 2, y, { align: 'center' });
        y += 5;
        doc.text((empresa.representante_cpf || '').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') || '—', pageWidth / 2, y, { align: 'center' });
        y += 12;

        doc.line(marginX, y, pageWidth - margin, y);
        y += 15;

        doc.setFont(undefined, 'bold');
        doc.text('REPRESENTANTE MUTUÁRIA', pageWidth / 2, y, { align: 'center' });
        y += 8;
        doc.setFont(undefined, 'normal');
        const repMutNome = (devedorSolidario?.nome_completo || cliente.razao_social || cliente.nome_completo || '—').toUpperCase();
        doc.text(repMutNome, pageWidth / 2, y, { align: 'center' });
        y += 5;
        doc.text((devedorSolidario?.cpf || cliente.cpf || '').replace(/\D/g, '').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') || '—', pageWidth / 2, y, { align: 'center' });
        y += 15;

        doc.setFont(undefined, 'bold');
        doc.text('TESTEMUNHAS:', pageWidth / 2, y, { align: 'center' });
        y += 10;

        doc.line(marginX, y, pageWidth / 2 - 10, y);
        doc.line(pageWidth / 2 + 10, y, pageWidth - margin, y);
        y += 15;
        doc.setFont(undefined, 'normal');
        doc.setFontSize(9);
        doc.text('Nome:', marginX + 5, y);
        doc.text('Nome:', pageWidth / 2 + 15, y);
        y += 6;
        doc.text('CPF:', marginX + 5, y);
        doc.text('CPF:', pageWidth / 2 + 15, y);
        y += 6;
        doc.text('RG:', marginX + 5, y);
        doc.text('RG:', pageWidth / 2 + 15, y);

        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
            doc.setFontSize(9);
            doc.text(String(i), pageWidth - margin, pageHeight - 10, { align: 'right' });
        }

        const filename = options?.filename || `contrato-inicial-${new Date().toISOString().split('T')[0]}.pdf`;

        if (options?.returnBlob) {
            return doc.output('blob');
        }

        doc.save(filename);
    }

    /**
     * Exporta Notas Promissórias em PDF (uma por parcela)
     */
    function exportNotasPromissorias(empresa = {}, cliente = {}, garantias = []) {
        if (!result.value || !result.value.cronograma) return;

        const r = result.value;
        const cronograma = r.cronograma;
        const totalNotas = cronograma.length;

        const mutuanteNome = empresa.company || empresa.razao_social || 'rj emprestimos empresa simples de creditos ltda';
        const mutuanteCnpj = empresa.cnpj || '—';
        const cidadePagavel = empresa.cidade || 'Aparecida de Goiânia';
        const ufPagavel = empresa.estado || 'GO';

        const avalistas = (garantias || [])
            .filter((g) => ['avalista', 'devedor_solidario'].includes(g?.tipo))
            .map((g) => g?.dados || {})
            .filter((d) => d && (d.nome_completo || d.cpf));

        const representante = (garantias || []).find((g) => g?.tipo === 'devedor_solidario')?.dados
            || avalistas[0]
            || null;

        const emitenteNome = cliente?.razao_social || cliente?.nome_completo || '—';
        const emitenteDoc = cliente?.cnpj ? `CNPJ: ${cliente.cnpj}` : (cliente?.cpf ? `CPF: ${cliente.cpf}` : 'CPF/CNPJ: —');
        const endCliente = cliente?.address?.[0];
        const emitenteEndereco = endCliente
            ? [endCliente.address, endCliente.number, endCliente.neighborhood].filter(Boolean).join(', ')
            : '—';

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        const meses = [
            'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
            'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
        ];

        function formatDateLongPt(dateLike) {
            const d = dateLike instanceof Date ? dateLike : new Date(dateLike);
            const day = d.getDate();
            const month = meses[d.getMonth()];
            const year = d.getFullYear();
            return `${day} de ${month} de ${year}`;
        }

        function formatDateShortPt(dateLike) {
            const d = dateLike instanceof Date ? dateLike : new Date(dateLike);
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yy = d.getFullYear();
            return `${dd}/${mm}/${yy}`;
        }

        function numeroPorExtensoAte99(n) {
            const u = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
            const d10 = ['dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
            const dz = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
            if (n < 10) return u[n];
            if (n < 20) return d10[n - 10];
            const dez = Math.floor(n / 10);
            const uni = n % 10;
            return dz[dez] + (uni ? ` e ${u[uni]}` : '');
        }

        function anoPorExtenso(ano) {
            // suficiente para 2000–2099
            if (ano === 2000) return 'dois mil';
            const resto = ano - 2000;
            if (resto < 100) {
                if (resto === 0) return 'dois mil';
                if (resto < 10) return `dois mil e ${numeroPorExtensoAte99(resto)}`;
                return `dois mil e ${numeroPorExtensoAte99(resto)}`;
            }
            return String(ano);
        }

        function dataParaFraseExtensa(dateLike) {
            const d = dateLike instanceof Date ? dateLike : new Date(dateLike);
            const dia = numeroPorExtensoAte99(d.getDate());
            const mes = meses[d.getMonth()];
            const ano = anoPorExtenso(d.getFullYear());
            return `${dia} de ${mes} de ${ano}`;
        }

        const notasPorPagina = 2;
        const pageWidth = doc.internal.pageSize.getWidth();

        const marginX = 14;
        const startY = 18;
        const boxW = pageWidth - marginX * 2; // ~182mm
        const boxH = 125;
        const gapY = 10;

        const rightW = 55;
        const leftW = boxW - rightW;
        const headerH = 10;

        function drawNotaBox(x, y, parcela) {
            // borda externa
            doc.setLineWidth(0.3);
            doc.rect(x, y, boxW, boxH);
            // divisão vertical
            doc.line(x + leftW, y, x + leftW, y + boxH);
            // divisão horizontal (cabeçalho)
            doc.line(x, y + headerH, x + boxW, y + headerH);

            // Cabeçalho
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.text(`Nota Promissória ${parcela.numero} / ${totalNotas}`, x + 3, y + 7);
            doc.text('Avalistas:', x + leftW + rightW / 2, y + 7, { align: 'center' });

            // Conteúdo (coluna esquerda)
            let yy = y + headerH + 8;
            doc.setFont(undefined, 'normal');
            doc.setFontSize(9);
            doc.text(`Vencimento: ${formatDateLongPt(parcela.vencimento)}`, x + 3, yy);
            yy += 7;

            doc.setFont(undefined, 'bold');
            doc.text(`Valor R$ ${Number(parcela.parcela).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, x + 3, yy);
            yy += 8;

            doc.setFont(undefined, 'normal');
            const valorExtenso = valorPorExtenso(parcela.parcela);
            const fraseData = dataParaFraseExtensa(parcela.vencimento);
            const p1 = `Em ${fraseData}, pagarei por esta única via de NOTA PROMISSÓRIA à ${mutuanteNome}, CNPJ: ${mutuanteCnpj} ou à sua ordem, a quantia de ${valorExtenso} em moeda corrente nacional.`; // modelo
            const linhasP1 = doc.splitTextToSize(p1, leftW - 6);
            doc.text(linhasP1, x + 3, yy);
            yy += linhasP1.length * 4.2 + 2;

            doc.text(`Pagável em ${cidadePagavel}–${ufPagavel}.`, x + 3, yy);
            yy += 5;
            const dataEmissao = formatDateShortPt(r.inputs.data_assinatura);
            doc.text(`Nota Promissória emitida em: ${dataEmissao}.`, x + 3, yy);
            yy += 8;

            doc.setFont(undefined, 'bold');
            doc.text('Emitente:', x + 3, yy);
            yy += 5;
            doc.setFont(undefined, 'normal');
            doc.text(`Razão Social: ${emitenteNome}`, x + 3, yy);
            yy += 4.5;
            doc.text(`${emitenteDoc}`, x + 3, yy);
            yy += 4.5;
            doc.text(`Endereço: ${emitenteEndereco}`, x + 3, yy, { maxWidth: leftW - 6 });

            // assinatura (coluna esquerda - parte inferior)
            const ySig = y + boxH - 18;
            doc.setLineWidth(0.2);
            doc.line(x + 20, ySig, x + leftW - 20, ySig);

            doc.setFont(undefined, 'bold');
            doc.text(emitenteNome, x + leftW / 2, ySig + 6, { align: 'center' });

            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            if (cliente?.cnpj && representante?.nome_completo) {
                doc.text('Neste ato representada por:', x + leftW / 2, ySig + 10, { align: 'center' });
                doc.text(`Nome: ${representante.nome_completo}`, x + leftW / 2, ySig + 14, { align: 'center' });
                doc.text(`CPF: ${representante.cpf || '—'}`, x + leftW / 2, ySig + 18, { align: 'center' });
            }

            // Conteúdo (coluna direita)
            let yr = y + headerH + 18;
            const centerX = x + leftW + rightW / 2;
            const maxAvalistas = 3;
            const lista = avalistas.slice(0, maxAvalistas);
            lista.forEach((a) => {
                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.text(String(a.nome_completo || '—'), centerX, yr, { align: 'center' });
                yr += 5;
                doc.setFont(undefined, 'normal');
                doc.text(`CPF: ${a.cpf || '—'}`, centerX, yr, { align: 'center' });
                yr += 10;
            });
        }

        cronograma.forEach((parcela, idx) => {
            const pagina = Math.floor(idx / notasPorPagina);
            const pos = idx % notasPorPagina;
            if (idx > 0 && pos === 0) doc.addPage();

            const y = startY + pos * (boxH + gapY);
            drawNotaBox(marginX, y, parcela);
        });

        const filename = `notas-promissorias-${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(filename);
    }

    /**
     * Salva a simulação no banco para relatórios futuros
     */
    async function saveSimulation(id = null) {
        if (!result.value || !result.value.cronograma) return { success: false, message: 'Nenhuma simulação para salvar.' };

        saving.value = true;
        error.value = null;

        try {
            const garantiasParaSalvar = (form.garantias || []).map((g) => {
                const dados = { ...(g.dados || {}) };
                delete dados.pessoa_selecionada;
                return { tipo: g.tipo, pessoa_id: g.pessoa_id || null, dados };
            });
            const payload = {
                ...result.value,
                client_id: form.client_id,
                banco_id: form.banco_id,
                inputs: {
                    ...result.value.inputs,
                    garantias: garantiasParaSalvar,
                    inadimplencia: form.inadimplencia || {},
                },
            };
            const response = id
                ? await axios.put(`${apiPath}/simulacoes-emprestimo/${id}`, payload)
                : await axios.post(`${apiPath}/simulacoes-emprestimo`, payload);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Erro ao salvar simulação.';
            return { success: false, message: error.value };
        } finally {
            saving.value = false;
        }
    }

    return {
        form,
        loading,
        saving,
        error,
        result,
        isValid,
        simulate,
        simulateDebounced,
        setDefaultFirstDue,
        formatCurrency,
        formatPercent,
        formatDate,
        exportJSON,
        exportCSV,
        exportPDF,
        exportContratoInicial,
        exportNotasPromissorias,
        saveSimulation,
    };
}
