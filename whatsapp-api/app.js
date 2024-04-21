const express = require('express');
const cors = require('cors');
const path = require('path');
const qrcode = require('qrcode-terminal');
const qrcode2 = require('qrcode');
const fs = require('fs').promises;
const { Client } = require('whatsapp-web.js');
const app = express();
const port = 3000;

// Middleware para analisar o corpo da solicitação como JSON
app.use(cors({
  origin: 'https://sistema.rjemprestimos.com.br'
}));

app.use(express.json());

let isClientLoggedIn = false;

// Configurações do cliente do WhatsApp
const client = new Client({
  puppeteer: {args: ["--no-sandbox"]},
  webVersionCache: {
  type: 'remote',
  remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html',
  }
});

const minhaString = '2@1pEsoTpn6mw/2nCvtfOnd2cbinxV4s+C85RiA5NPAdEcWSYdbuZVjewJImnOEzZUk7BnXYS5+VHs/A==,dbS6uLDmGoBAE/Cmy8eo2YFMR8ukQDmk99oL9/oV8EQ=,Q0YxCg/SbEU8yGFlMQNEAHkqg7XVD3zfW1ZhG0qfUT8=,mklbm++D24DTNuZRvh94BzaY2EvZvZz+PAZK/vDTZOI=,1';

// Função para gerar e salvar o QR code
const gerarESalvarQRCode = async (qr) => {
  try {
    // Gera o QR code como um buffer de imagem
    const qrCodeBuffer = await qrcode2.toBuffer(qr, { type: 'png' });

    // Salva o buffer como uma imagem PNG no servidor
    const caminhoDaImagem = path.join(__dirname, 'static', 'qrcode.png');

    await fs.writeFile(caminhoDaImagem, qrCodeBuffer);

    console.log(`QR code salvo em: ${caminhoDaImagem}`);
  } catch (erro) {
    console.error('Erro ao gerar e salvar o QR code:', erro.message);
  }
};

let qr;

client.on('qr', (qrCode) => {

  qr = qrCode;

  gerarESalvarQRCode(qrCode);

  console.log(qr)
  // Exibe o código QR para autenticação
  // qrcode.generate(qrCode, { small: true });

});

// Configuração para servir arquivos estáticos (qrcode.png)
app.use('/static', express.static(path.join(__dirname, 'static')));


app.get('/logar', (req, res) => {
  // const qrCode = qrcode2.generate(qr, { small: true });

  res.send(
    { 
      loggedIn: isClientLoggedIn,
      url:  `https://node1.rjemprestimos.com.br/static/qrcode.png`,
    }
    );
});

client.on('authenticated', (session) => {
  console.log('Autenticado com sucesso!');
  isClientLoggedIn = true;
});

client.on('disconnected', (reason) => {
  console.log(`Desconectado: ${reason}`);
  client.destroy();

  isClientLoggedIn = false;
  client.initialize();

});

app.get('/status', (req, res) => {
  res.json({ loggedIn: isClientLoggedIn });
});

client.on('message', (message) => {
  console.log(`Nova mensagem de ${message.from}: ${message.body}`);
});

// Rota para enviar mensagem
app.post('/enviar-mensagem', async (req, res) => {
  // Desestruturação dos dados do corpo da solicitação
  const { numero, mensagem } = req.body;

  try {
    // Envia a mensagem para o número fornecido
    const chatId = `${numero}@c.us`;
    await client.sendMessage(chatId, mensagem);
    res.send('Mensagem enviada com sucesso!');
  } catch (error) {
    console.error('Erro ao enviar mensagem:', error.message);
    res.status(500).send('Erro ao enviar mensagem');
  }
});

app.post('/renovar', async (req, res) => {
  // Desestruturação dos dados do corpo da solicitação
  const { numero } = req.body;

  const mensagem = `
Boas notícias, a penúltima parcela foi paga, você deseja renovar seu emprestimo?

    Digite uma opção:

      1 - Sim Adoraria.
      2 - Vou esperar pagar a última.
      3 - Por agora não.
  `;

  try {
    // Envia a mensagem para o número fornecido
    const chatId = `${numero}@c.us`;
    await client.sendMessage(chatId, mensagem);
    res.send('Mensagem enviada com sucesso!');
  } catch (error) {
    console.error('Erro ao enviar mensagem:', error.message);
    res.status(500).send('Erro ao enviar mensagem');
  }
});

client.on('message', async (message) => {
  // Exibe a mensagem recebida
  console.log(`Mensagem recebida de ${message.from}: ${message.body}`);
  // Verifica se a mensagem é do cliente que estamos esperando
    // Exibe a mensagem recebida

  // Verifica a resposta do cliente
  switch (message.body.toLowerCase()) {
    case '1':
      await client.sendMessage(message.from, 'Você escolheu a opção 1. Pronto agora no aplicativo já está disponivel no home a opção para renovação.');
      break;
    case '2':
      await client.sendMessage(message.from, 'Você escolheu a opção 2. Ok, iremos aguardar.');
      break;
    case '3':
      await client.sendMessage(message.from, 'Você escolheu a opção 3. Muito Obrigado pelo retorno.');
      break;
    default:
      // await client.sendMessage(message.from, 'Opção inválida. Por favor, digite uma opção válida.');
      break;
  }
});

client.on('message', message => {
	if(message.body === 'Desejo renovar o meu emprestimo!') {
		client.sendMessage(message.from, 'pong');
	}
});
 

// Inicia o servidor
app.listen(port, () => {
  console.log(`Servidor iniciado em http://localhost:${port}`);
});

// Inicia a sessão do cliente do WhatsApp
client.initialize();
