import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { calculateLoan } from '@/utils/loanCalculator';
import { valorPorExtenso } from '@/utils/valorPorExtenso';

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
        quantidade_parcelas: 20,
        taxa_juros_mensal: '20.00',
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
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
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
    function simulate() {
        loading.value = true;
        error.value = null;

        try {
            const valorSolicitado = typeof form.valor_solicitado === 'number'
                ? form.valor_solicitado
                : parseFloat(parseCurrency(form.valor_solicitado)) || 0;

            const taxaDecimal = parseTaxa(form.taxa_juros_mensal);
            const dataAssinatura = form.data_assinatura instanceof Date
                ? form.data_assinatura
                : new Date(form.data_assinatura);
            const dataPrimeira = form.data_primeira_parcela instanceof Date
                ? form.data_primeira_parcela
                : new Date(form.data_primeira_parcela);

            result.value = calculateLoan({
                valorSolicitado,
                periodoAmortizacao: form.periodo_amortizacao,
                quantidadeParcelas: form.quantidade_parcelas,
                taxaJurosMensal: taxaDecimal,
                dataAssinatura,
                dataPrimeiraParcela: dataPrimeira,
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
     * Valida se primeira parcela >= data assinatura
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
        return dataPrimeira >= dataAssinatura;
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
     * Exporta Contrato Inicial em PDF (modelo de contrato de mútuo)
     */
    function exportContratoInicial(empresa = {}, cliente = {}, options = {}) {
        if (!result.value || !result.value.cronograma) return;

        const r = result.value;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const pageWidth = doc.internal.pageSize.getWidth();
        let y = 15;

        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.text('CONTRATO DE MÚTUO FINANCEIRO', pageWidth / 2, y, { align: 'center' });
        y += 10;

        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        const ano = new Date().getFullYear();
        const numeroContrato = `${ano}/000001`;
        doc.text(`Nº DO CONTRATO: ${numeroContrato}`, 14, y);
        doc.text(`VALOR DO CRÉDITO: ${formatCurrency(r.inputs.valor_solicitado)}`, 110, y);
        y += 12;

        doc.setFont(undefined, 'bold');
        doc.text('QUADRO 01 - PARTES CONTRATANTES', 14, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        doc.text('1. CONTRATADA MUTUANTE', 14, y);
        y += 5;
        doc.text(`Razão Social: ${empresa.company || empresa.razao_social || '—'}`, 14, y);
        y += 5;
        doc.text(`CNPJ: ${empresa.cnpj || '—'}`, 14, y);
        y += 5;
        doc.text(`Endereço: ${empresa.endereco || '—'}`, 14, y);
        y += 5;
        doc.text(`Telefone: ${empresa.telefone || empresa.whatsapp || '—'}`, 14, y);
        y += 5;
        doc.text(`E-mail: ${empresa.email || '—'}`, 14, y);
        y += 10;

        doc.text('3. CONTRATANTE MUTUÁRIA', 14, y);
        y += 5;
        doc.text(`Razão Social: ${cliente.razao_social || cliente.nome_completo || '—'}`, 14, y);
        doc.text(`CNPJ: ${cliente.cnpj || '—'}`, 110, y);
        y += 5;
        const endCliente = cliente.address?.[0];
        doc.text(`Endereço: ${endCliente ? [endCliente.address, endCliente.number, endCliente.neighborhood, endCliente.city].filter(Boolean).join(', ') : '—'}`, 14, y);
        y += 10;

        doc.setFont(undefined, 'bold');
        doc.text('QUADRO 02 – COMPOSIÇÃO DO CRÉDITO', 14, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        doc.text(`Valor do Crédito: ${formatCurrency(r.inputs.valor_solicitado)}`, 14, y);
        y += 5;
        doc.text(`Quantidade de parcelas: ${r.inputs.quantidade_parcelas}`, 14, y);
        y += 5;
        doc.text(`1º vencimento: ${formatDate(r.inputs.data_primeira_parcela)}`, 14, y);
        y += 5;
        doc.text(`Juros remuneratórios ao mês: ${r.inputs.taxa_juros_mensal}%`, 14, y);
        y += 5;
        doc.text(`Sistema de Amortização: ${r.inputs.modelo_amortizacao || 'Price'}`, 14, y);
        y += 5;
        doc.text(`Periodicidade: ${r.inputs.periodo_amortizacao}`, 14, y);
        y += 5;
        doc.text(`IOF: ${formatCurrency(r.iof.total)} (diário ${formatCurrency(r.iof.diario)} + adicional ${formatCurrency(r.iof.adicional)})`, 14, y);
        y += 5;
        doc.text(`Valor do Contrato: ${formatCurrency(r.valor_contrato)}`, 14, y);
        y += 5;
        doc.text(`CET ao ano: ${formatPercent(parseFloat(r.totais.cet_ano))} | CET ao mês: ${formatPercent(parseFloat(r.totais.cet_mes))}`, 14, y);
        y += 5;
        doc.text(`Valor total a prazo: ${formatCurrency(r.totais.total_parcelas)}`, 14, y);
        y += 10;

        doc.setFont(undefined, 'bold');
        doc.text('QUADRO 03 – CRONOGRAMA DE REEMBOLSO', 14, y);
        y += 7;
        doc.setFont(undefined, 'normal');

        const tableData = r.cronograma.map(p => [
            String(p.numero),
            formatDate(p.vencimento),
            p.parcela,
            p.juros,
            p.amortizacao,
            p.saldo_devedor,
        ]);
        autoTable(doc, {
            startY: y,
            head: [['Parcela', 'Vencimento', 'Valor', 'Juros', 'Amortização', 'Saldo Devedor']],
            body: tableData,
            theme: 'grid',
            styles: { fontSize: 7 },
            headStyles: { fillColor: [100, 100, 100] },
            margin: { left: 14, right: 14 },
        });
        y = doc.lastAutoTable.finalY + 15;

        doc.setFontSize(9);
        doc.text('Por este instrumento particular, as partes acima qualificadas celebram o presente CONTRATO DE MÚTUO FINANCEIRO.', 14, y, { maxWidth: pageWidth - 28 });
        y += 10;
        doc.text('E, por estarem assim justas e acertadas, firmam o presente instrumento.', 14, y, { maxWidth: pageWidth - 28 });
        y += 15;

        const dataAssinatura = formatDate(r.inputs.data_assinatura);
        doc.text(`Data: ${dataAssinatura}`, 14, y);

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
