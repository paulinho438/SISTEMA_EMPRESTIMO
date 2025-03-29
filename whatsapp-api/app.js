const express = require("express");
const cors = require("cors");
const path = require("path");
const qrcode = require("qrcode-terminal");
const qrcode2 = require("qrcode");
const fs = require("fs").promises;
const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const multer = require("multer");
const upload = multer({ dest: "uploads/" });
const app = express();
const port = 3000;

const puppeteer = require("puppeteer");

const OpenAI = require("openai");

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY, // Use a variável de ambiente
});

// Middleware para analisar o corpo da solicitação como JSON
const corsOptions = {
  origin: function (origin, callback) {
    const allowedOrigins = [
      "https://sistema.agecontrole.com.br",
      "https://api.agecontrole.com.br",
      "http://localhost:5173",
    ];
    if (!origin || allowedOrigins.indexOf(origin) !== -1) {
      callback(null, true);
    } else {
      callback(new Error("Not allowed by CORS"));
    }
  },
};

app.use(cors(corsOptions));

app.use(express.json());

let isClientLoggedIn = false;

console.log("passou");

// Configurações do cliente do WhatsApp
const client = new Client({
  puppeteer: {
    headless: true,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-accelerated-2d-canvas",
      "--no-first-run",
      "--no-zygote",
      "--single-process",
      "--disable-gpu",
    ],
  },
  webVersionCache: { type: "none" },
  authStrategy: new LocalAuth(),
});

const minhaString =
  "2@1pEsoTpn6mw/2nCvtfOnd2cbinxV4s+C85RiA5NPAdEcWSYdbuZVjewJImnOEzZUk7BnXYS5+VHs/A==,dbS6uLDmGoBAE/Cmy8eo2YFMR8ukQDmk99oL9/oV8EQ=,Q0YxCg/SbEU8yGFlMQNEAHkqg7XVD3zfW1ZhG0qfUT8=,mklbm++D24DTNuZRvh94BzaY2EvZvZz+PAZK/vDTZOI=,1";

// Função para gerar e salvar o QR code
const gerarESalvarQRCode = async (qr) => {
  try {
    // Gera o QR code como um buffer de imagem
    const qrCodeBuffer = await qrcode2.toBuffer(qr, { type: "png" });

    // Salva o buffer como uma imagem PNG no servidor
    const caminhoDaImagem = path.join(__dirname, "static", "qrcode.png");

    await fs.writeFile(caminhoDaImagem, qrCodeBuffer);

    console.log(`QR code salvo em: ${caminhoDaImagem}`);
  } catch (erro) {
    console.error("Erro ao gerar e salvar o QR code:", erro.message);
  }
};

let qr;

client.on("qr", (qrCode) => {
  qr = qrCode;

  gerarESalvarQRCode(qrCode);

  console.log(qr);
  // Exibe o código QR para autenticação
  // qrcode.generate(qrCode, { small: true });
});

// Configuração para servir arquivos estáticos (qrcode.png)
app.use("/static", express.static(path.join(__dirname, "static")));

app.get("/logar", (req, res) => {
  // const qrCode = qrcode2.generate(qr, { small: true });

  res.send({
    loggedIn: isClientLoggedIn,
    url: `https://node.agecontrole.com.br/static/qrcode.png`,
  });
});

client.on("authenticated", (session) => {
  console.log("Autenticado com sucesso!");
  isClientLoggedIn = true;
});

client.on("disconnected", (reason) => {
  console.log(`Desconectado: ${reason}`);
  client.destroy();

  isClientLoggedIn = false;
  client.initialize();
});

app.get("/status", (req, res) => {
  res.json({ loggedIn: isClientLoggedIn });
});

client.on("message", (message) => {
  console.log(`Nova mensagem de ${message.from}: ${message.body}`);
});

// Rota para enviar mensagem
app.post("/enviar-mensagem", async (req, res) => {
  // Desestruturação dos dados do corpo da solicitação
  const { numero, mensagem } = req.body;

  try {
    // Envia a mensagem para o número fornecido
    const chatId = `${numero}@c.us`;
    await client.sendMessage(chatId, mensagem);
    res.send("Mensagem enviada com sucesso!");
  } catch (error) {
    console.error("Erro ao enviar mensagem:", error.message);
    res.status(500).send("Erro ao enviar mensagem");
  }
});

app.post("/enviar-pdf", upload.single("arquivo"), async (req, res) => {
  const { numero } = req.body;

  try {
    // Verifica se o arquivo foi enviado
    if (!req.file) {
      return res.status(400).send("Nenhum arquivo enviado.");
    }

    // Obtém a extensão do arquivo original
    const extensao = path.extname(req.file.originalname);

    // Lista de extensões permitidas
    const extensoesPermitidas = [".pdf", ".mp4", ".jpg", ".png"];

    if (!extensoesPermitidas.includes(extensao.toLowerCase())) {
      return res.status(400).send("Tipo de arquivo não suportado.");
    }

    // Renomeia o arquivo com a extensão correta
    const novoCaminho = path.join(
      __dirname,
      "uploads",
      `${req.file.filename}${extensao}`
    );
    await fs.rename(req.file.path, novoCaminho);

    // Cria a mídia com o MessageMedia
    const media = MessageMedia.fromFilePath(novoCaminho);

    // Envia o arquivo para o número fornecido
    const chatId = `${numero}@c.us`;
    await client.sendMessage(chatId, media);

    // Opcional: Remove o arquivo do servidor após o envio
    await fs.unlink(novoCaminho);

    res.send(`${extensao.toUpperCase()} enviado com sucesso!`);
  } catch (error) {
    console.error("Erro ao enviar arquivos:", error.message);
    res.status(500).send("Erro ao enviar arquivo");
  }
});

app.post("/enviar-video", upload.single("arquivo"), async (req, res) => {
  const { numero } = req.body;

  try {
    const videoPath = path.join(__dirname, "uploads", "output2.mp4");

    console.log(videoPath);

    console.log("Preparando o vídeo para envio...");
    const media = MessageMedia.fromFilePath(videoPath);

    console.log("Passou pelo video");

    // Envia o arquivo para o número fornecido
    const chatId = `${numero}@c.us`;
    await client.sendMessage(chatId, media);

    res.send(`enviado com sucesso!`);
  } catch (error) {
    console.error("Erro ao enviar arquivos:", error.message);
    console.error("Erro ao enviar arquivos:", error);
    res.status(500).send("Erro ao enviar arquivo");
  }
});

function formatarNome(nomeCompleto) {
  if (!nomeCompleto) return '';
  const primeiroNome = nomeCompleto.split(' ')[0];
  return primeiroNome.charAt(0).toUpperCase() + primeiroNome.slice(1).toLowerCase();
}

app.post("/enviar-audio", async (req, res) => {
  const { numero, tipo, nomeCliente } = req.body;
  // Função para aguardar 20 segundos
  const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
  const nomeFormatado = formatarNome(nomeCliente);

  try {
    const chatId = `${numero}@c.us`;
    let mensagem = '';
    switch (tipo) {
      case "1":
        mensagem = `Bom dia, ${nomeFormatado} tudo bem, poxa vou te mandar um audio para ficar mais claro.`;
        await client.sendMessage(chatId, mensagem);
        const audioPath1 = path.join(__dirname, "uploads", "atraso1.ogg");
        // Aguarda 20 segundos
        await wait(20000);
        const media1 = MessageMedia.fromFilePath(audioPath1);
        await client.sendMessage(chatId, media1, {
          sendAudioAsVoice: true,
        });
        break;
      case "2":
        mensagem = `Bom dia, ${nomeFormatado} tudo bem, estou olhando aqui o sistema e vi que vc está com bastante dias em atraso, vou te enviar um audio para ficar mais claro.`;
        await client.sendMessage(chatId, mensagem);
        const audioPath2 = path.join(__dirname, "uploads", "atraso1dia.ogg");
        // Aguarda 20 segundos
        await wait(20000);
        const media2 = MessageMedia.fromFilePath(audioPath2);
        await client.sendMessage(chatId, media2, {
          sendAudioAsVoice: true,
        });
        break;
      case "3":
        mensagem = `Bom dia, ${nomeFormatado} tudo bem, poxa vou te mandar um audio para ficar mais claro.`;
        await client.sendMessage(chatId, mensagem);
        const audioPath3 = path.join(__dirname, "uploads", "atraso3.ogg");
        // Aguarda 20 segundos
        await wait(20000);
        const media3 = MessageMedia.fromFilePath(audioPath3);
        await client.sendMessage(chatId, media3, {
          sendAudioAsVoice: true,
        });
        break;
    }

    res.send("Mensagem de voz enviada com sucesso!");
  } catch (error) {
    console.error("Erro ao enviar o áudio:", error.message);
    res.status(500).send("Erro ao enviar o áudio");
  }
});

app.get("/logout", async (req, res) => {
  try {
    if (!client || !client.info) {
      console.warn("Cliente ainda não está pronto ou já foi destruído.");
      return res.status(400).send({ message: "Cliente não está conectado." });
    }

    await client.logout();
    isClientLoggedIn = false;

    console.log("✅ Cliente desconectado com sucesso.");
    res.send({ message: "Cliente desconectado com sucesso." });
  } catch (error) {
    console.error("❌ Erro ao deslogar cliente:", error);
    res
      .status(500)
      .send({ error: "Erro ao deslogar cliente.", details: error.message });
  }
});

app.post("/renovar", async (req, res) => {
  // Desestruturação dos dados do corpo da solicitação
  const { numero } = req.body;

  let mensagem = `
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
    res.send("Mensagem enviada com sucesso!");
  } catch (error) {
    console.error("Erro ao enviar mensagem:", error.message);
    res.status(500).send("Erro ao enviar mensagem");
  }
});

client.on("message", async (message) => {
  // Exibe a mensagem recebida
  console.log(`Mensagem recebida de ${message.from}: ${message.body}`);
  try {
    const response = await openai.chat.completions.create({
      model: "gpt-3.5-turbo",
      messages: [{ role: "user", content: message.body }],
    });

    const reply = response.choices[0].message.content;
    await client.sendMessage(message.from, reply);

    const { custoReais, tokens } = calcularCusto(response.usage);
    console.log(`✅ Mensagem processada: ${tokens} tokens | R$ ${custoReais}`);
  } catch (error) {
    console.error("❌ Erro ao consultar ChatGPT:", error.message);

    if (error.status === 429 || error.error?.type === "insufficient_quota") {
      // await client.sendMessage(message.from, "Limite de uso da API foi atingido.");
    } else {
      await client.sendMessage(message.from, "Erro ao processar sua mensagem.");
    }
  }
  // Verifica se a mensagem é do cliente que estamos esperando
  // Exibe a mensagem recebida

  // Verifica a resposta do cliente
  // switch (message.body.toLowerCase()) {
  //   case "1":
  //     await client.sendMessage(
  //       message.from,
  //       "Você escolheu a opção 1. Pronto agora no aplicativo já está disponivel no home a opção para renovação."
  //     );
  //     break;
  //   case "2":
  //     await client.sendMessage(
  //       message.from,
  //       "Você escolheu a opção 2. Ok, iremos aguardar."
  //     );
  //     break;
  //   case "3":
  //     await client.sendMessage(
  //       message.from,
  //       "Você escolheu a opção 3. Muito Obrigado pelo retorno."
  //     );
  //     break;
  //   default:
  //     // await client.sendMessage(message.from, 'Opção inválida. Por favor, digite uma opção válida.');
  //     break;
  // }
});

client.on("message", (message) => {
  if (message.body === "Desejo renovar o meu emprestimo!") {
    client.sendMessage(message.from, "pong");
  }
});

// Inicia o servidor
app.listen(port, () => {
  console.log(`Servidor iniciado em http://localhost:${port}`);
});

// Inicia a sessão do cliente do WhatsApp
client.initialize();
