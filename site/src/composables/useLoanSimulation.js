import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const apiPath = import.meta.env.VITE_APP_BASE_URL;

export function useLoanSimulation() {
    const loading = ref(false);
    const error = ref(null);
    const result = ref(null);
    const debounceTimer = ref(null);

    const form = reactive({
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
        garantias: {
            sem_garantia: true,
            avalistas: false,
            imovel: false,
            veiculo: false,
            devedor_solidario: false,
            recebiveis: false,
            outras_garantias: false,
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
     * Simula empréstimo chamando API
     */
    async function simulate() {
        loading.value = true;
        error.value = null;

        try {
            // InputNumber retorna número puro, não precisa parseCurrency
            const valorSolicitado = typeof form.valor_solicitado === 'number' 
                ? form.valor_solicitado 
                : parseFloat(parseCurrency(form.valor_solicitado)) || 0;
            
            const payload = {
                valor_solicitado: valorSolicitado,
                taxa_juros_mensal: parseTaxa(form.taxa_juros_mensal),
                quantidade_parcelas: form.quantidade_parcelas,
                modelo_amortizacao: removeAccents(form.modelo_amortizacao),
                periodo_amortizacao: removeAccents(form.periodo_amortizacao),
                data_assinatura: dateToString(form.data_assinatura),
                data_primeira_parcela: dateToString(form.data_primeira_parcela),
                calcular_iof: form.calcular_iof,
                tipo_operacao: form.tipo_operacao,
                cliente_simples_nacional: form.cliente_simples_nacional,
            };

            const response = await axios.post(`${apiPath}/loan/simulate`, payload);
            result.value = response.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Erro ao simular empréstimo';
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

    return {
        form,
        loading,
        error,
        result,
        isValid,
        simulate,
        simulateDebounced,
        formatCurrency,
        formatPercent,
        formatDate,
        exportJSON,
        exportCSV,
    };
}
