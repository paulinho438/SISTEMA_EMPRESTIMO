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
      case "1.1":
        mensagem = `Oi ${nomeFormatado} escute com atenção o áudio abaixo para ficar bem intendido!`;
        await client.sendMessage(chatId, mensagem);
        const audioPath11 = path.join(__dirname, "uploads", "mensagem_1_atraso_2d.ogg");
        // Aguarda 20 segundos
        const media11 = MessageMedia.fromFilePath(audioPath11);
        await client.sendMessage(chatId, media11, {
          sendAudioAsVoice: true,
        });
        break;
      case "1.2":
        const audioPath12 = path.join(__dirname, "uploads", "mensagem_2_atraso_2d.ogg");
        const media12 = MessageMedia.fromFilePath(audioPath12);
        await client.sendMessage(chatId, media12, {
          sendAudioAsVoice: true,
        });
        break;
      case "1.3":
        const audioPath13 = path.join(__dirname, "uploads", "mensagem_3_atraso_2d.ogg");
        const media13 = MessageMedia.fromFilePath(audioPath13);
        await client.sendMessage(chatId, media13, {
          sendAudioAsVoice: true,
        });
        break;
      case "2.1":
        mensagem = `E aí ${nomeFormatado} olha só vamos organizar sua questão!`;
        await client.sendMessage(chatId, mensagem);
        const audioPath21 = path.join(__dirname, "uploads", "mensagem_1_atraso_4d.ogg");
        // Aguarda 20 segundos
        const media21 = MessageMedia.fromFilePath(audioPath21);
        await client.sendMessage(chatId, media21, {
          sendAudioAsVoice: true,
        });
        break;
      case "2.2":
        const audioPath22 = path.join(__dirname, "uploads", "mensagem_1_atraso_4d.ogg");
        const media22 = MessageMedia.fromFilePath(audioPath22);
        await client.sendMessage(chatId, media22, {
          sendAudioAsVoice: true,
        });
        break;
      case "2.3":
        const audioPath23 = path.join(__dirname, "uploads", "mensagem_3_atraso_4d.ogg");
        const media23 = MessageMedia.fromFilePath(audioPath23);
        await client.sendMessage(chatId, media23, {
          sendAudioAsVoice: true,
        });
        break;
      case "3.1":
        mensagem = `E aí ${nomeFormatado} olha só vamos organizar sua questão!`;
        await client.sendMessage(chatId, mensagem);
        const audioPath31 = path.join(__dirname, "uploads", "mensagem_1_atraso_6d.ogg");
        // Aguarda 20 segundos
        const media31 = MessageMedia.fromFilePath(audioPath31);
        await client.sendMessage(chatId, media31, {
          sendAudioAsVoice: true,
        });
        break;
      case "3.2":
        const audioPath32 = path.join(__dirname, "uploads", "mensagem_2_atraso_6d.ogg");
        const media32 = MessageMedia.fromFilePath(audioPath32);
        await client.sendMessage(chatId, media32, {
          sendAudioAsVoice: true,
        });
        break;
      case "3.3":
        const audioPath33 = path.join(__dirname, "uploads", "mensagem_3_atraso_6d.ogg");
        const media33 = MessageMedia.fromFilePath(audioPath33);
        await client.sendMessage(chatId, media33, {
          sendAudioAsVoice: true,
        });
        break;
      case "4.1":
        mensagem = `${nomeFormatado} olha só atenção que vamos organizar essa parada agora`;
        await client.sendMessage(chatId, mensagem);
        const audioPath41 = path.join(__dirname, "uploads", "mensagem_1_atraso_8d.ogg");
        // Aguarda 20 segundos
        const media41 = MessageMedia.fromFilePath(audioPath41);
        await client.sendMessage(chatId, media41, {
          sendAudioAsVoice: true,
        });
        break;
      case "4.2":
        const audioPath42 = path.join(__dirname, "uploads", "mensagem_2_atraso_8d.ogg");
        const media42 = MessageMedia.fromFilePath(audioPath42);
        await client.sendMessage(chatId, media42, {
          sendAudioAsVoice: true,
        });
        break;
      case "4.3":
        const audioPath43 = path.join(__dirname, "uploads", "mensagem_3_atraso_8d.ogg");
        const media43 = MessageMedia.fromFilePath(audioPath43);
        await client.sendMessage(chatId, media43, {
          sendAudioAsVoice: true,
        });
        break;
      case "5.1":
        mensagem = `E aí ${nomeFormatado} olha só vamos organizar sua questão!`;
        await client.sendMessage(chatId, mensagem);
        const audioPath51 = path.join(__dirname, "uploads", "mensagem_1_atraso_10d.ogg");
        // Aguarda 20 segundos
        await wait(20000);
        const media51 = MessageMedia.fromFilePath(audioPath51);
        await client.sendMessage(chatId, media51, {
          sendAudioAsVoice: true,
        });
        break;
      case "5.2":
        const audioPath52 = path.join(__dirname, "uploads", "mensagem_2_atraso_10d.ogg");
        const media52 = MessageMedia.fromFilePath(audioPath52);
        await client.sendMessage(chatId, media52, {
          sendAudioAsVoice: true,
        });
        break;
      case "5.3":
        const audioPath53 = path.join(__dirname, "uploads", "mensagem_3_atraso_10d.ogg");
        const media53 = MessageMedia.fromFilePath(audioPath53);
        await client.sendMessage(chatId, media53, {
          sendAudioAsVoice: true,
        });
        break;
      case "6.1":
        mensagem = `${nomeFormatado} Seu caso tá sério mesmo`;
        await client.sendMessage(chatId, mensagem);
        const audioPath61 = path.join(__dirname, "uploads", "mensagem_1_atraso_15d.ogg");
        // Aguarda 20 segundos
        const media61 = MessageMedia.fromFilePath(audioPath61);
        await client.sendMessage(chatId, media61, {
          sendAudioAsVoice: true,
        });
        break;
      case "6.2":
        const audioPath62 = path.join(__dirname, "uploads", "mensagem_2_atraso_15d.ogg");
        const media62 = MessageMedia.fromFilePath(audioPath62);
        await client.sendMessage(chatId, media62, {
          sendAudioAsVoice: true,
        });
        break;
      case "6.3":
        const audioPath63 = path.join(__dirname, "uploads", "mensagem_3_atraso_15d.ogg");
        const media63 = MessageMedia.fromFilePath(audioPath63);
        await client.sendMessage(chatId, media63, {
          sendAudioAsVoice: true,
        });
        break;
      default:
        const audioPathpersonaliado = path.join(__dirname, "uploads", tipo+".ogg");
        const mediapersonaliado = MessageMedia.fromFilePath(audioPathpersonaliado);
        await client.sendMessage(chatId, mediapersonaliado, {
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
