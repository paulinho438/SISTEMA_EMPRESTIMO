/**
 * Converte valor numérico em extenso (reais e centavos) em português.
 * Versão simplificada para valores comuns em contratos.
 */
const unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
const dezA19 = ['dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
const dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
const centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

function centena(num) {
    if (num === 0) return '';
    if (num < 100) return dezena(num);
    if (num === 100) return 'cem';
    const c = Math.floor(num / 100);
    const resto = num % 100;
    return centenas[c] + (resto > 0 ? ' e ' + dezena(resto) : '');
}

function dezena(num) {
    if (num === 0) return '';
    if (num < 10) return unidades[num];
    if (num < 20) return dezA19[num - 10];
    const d = Math.floor(num / 10);
    const u = num % 10;
    return dezenas[d] + (u > 0 ? ' e ' + unidades[u] : '');
}

export function valorPorExtenso(valor) {
    const v = Math.round(parseFloat(valor) * 100) / 100;
    const reais = Math.floor(v);
    const centavos = Math.round((v - reais) * 100);

    let parteReais = '';
    if (reais === 0) {
        parteReais = 'zero';
    } else if (reais === 1) {
        parteReais = 'um';
    } else if (reais < 1000) {
        parteReais = centena(reais);
    } else if (reais < 1000000) {
        const mil = Math.floor(reais / 1000);
        const resto = reais % 1000;
        parteReais = (mil === 1 ? 'mil' : centena(mil) + ' mil') + (resto > 0 ? ' e ' + centena(resto) : '');
    } else {
        parteReais = String(reais);
    }

    const realExt = reais === 1 ? 'real' : 'reais';
    const centavoExt = centavos === 1 ? 'centavo' : 'centavos';

    if (centavos === 0) {
        return `${parteReais} ${realExt}`;
    }
    return `${parteReais} ${realExt} e ${dezena(centavos)} ${centavoExt}`;
}
