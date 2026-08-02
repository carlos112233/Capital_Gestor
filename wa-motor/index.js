const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { Pool } = require('pg');
const QRCodeImage = require('qrcode');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
require('dotenv').config();

// 1. CONFIGURACIÓN DE LA BASE DE DATOS (PostgreSQL Local / Server)
const pool = new Pool({
    user: process.env.DB_USER || 'crm_admin',
    host: process.env.DB_HOST || '127.0.0.1',
    database: process.env.DB_DATABASE || 'capital_gestor_db',
    password: process.env.DB_PASSWORD || 'Carlosaraiza2810',
    port: parseInt(process.env.DB_PORT || '5432'),
});

// Detectar ejecutable de Chrome / Chromium en el servidor
function findChromePath() {
    const paths = [
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/usr/lib/chromium/chromium',
        '/usr/local/bin/chromium'
    ];
    for (const p of paths) {
        if (fs.existsSync(p)) return p;
    }

    try {
        const found = execSync('which chromium || which chromium-browser || which google-chrome || which google-chrome-stable 2>/dev/null', { encoding: 'utf8' }).trim();
        if (found && fs.existsSync(found)) return found;
    } catch (e) {}

    try {
        const puppeteer = require('puppeteer');
        const pPath = puppeteer.executablePath();
        if (pPath && fs.existsSync(pPath)) return pPath;
    } catch (e) {}

    return undefined;
}

const chromePath = findChromePath();
console.log(`🌐 Navegador detectado para WhatsApp: ${chromePath || 'Chromium nativo de Puppeteer'}`);

const puppeteerOptions = {
    headless: true,
    bypassCSP: true,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--no-zygote',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-web-security',
        '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
    ]
};

if (chromePath) {
    puppeteerOptions.executablePath = chromePath;
}

// 2. CONFIGURACIÓN DEL CLIENTE WHATSAPP CON OPCIONES ACTUALIZADAS
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './.wwebjs_auth' // Guarda la sesión permanentemente
    }),
    puppeteer: puppeteerOptions,
    webVersionCache: {
        type: 'remote',
        remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.3000.1014587000-alpha.html'
    }
});

// 3. EVENTOS DEL SISTEMA
client.on('qr', async (qr) => {
    console.log('\n=============================================');
    console.log('--- NUEVO CÓDIGO QR DE WHATSAPP ---');
    console.log('Escanea este código con tu celular (WhatsApp > Dispositivos vinculados):');
    console.log('=============================================\n');
    qrcode.generate(qr, { small: true });

    try {
        const qrPath = path.join(__dirname, 'qr.png');
        await QRCodeImage.toFile(qrPath, qr, { width: 400 });
        const brainPath = '/home/araiza/.gemini/antigravity-ide/brain/20e4ef77-10df-4090-9fac-89a163a822e6/qr.png';
        const publicImgPath = path.join(__dirname, '..', 'public', 'img', 'qr.png');
        const publicPath = path.join(__dirname, '..', 'public', 'qr.png');
        
        if (fs.existsSync(brainPath)) fs.copyFileSync(qrPath, brainPath);
        fs.copyFileSync(qrPath, publicImgPath);
        fs.copyFileSync(qrPath, publicPath);
        console.log('📸 Imagen del QR actualizada en public/img/qr.png');
    } catch (e) {
        console.error('Error guardando imagen QR:', e.message);
    }
});

client.on('ready', () => {
    console.log('🚀 MOTOR LISTO: WhatsApp está conectado y escuchando la base de datos PostgreSQL.');
    iniciarBucleEnvio();
});

client.on('auth_failure', msg => {
    console.error('❌ Error de autenticación en WhatsApp:', msg);
});

client.on('disconnected', (reason) => {
    console.log('⚠️ Cliente desconectado:', reason);
    console.log('Reiniciando cliente...');
    client.initialize();
});

// 4. BUCLE DE CONSULTA A POSTGRESQL CADA 10 SEGUNDOS
function iniciarBucleEnvio() {
    setInterval(async () => {
        try {
            const res = await pool.query(
                "SELECT * FROM whatsapp_pending_messages WHERE status = 'pendiente' ORDER BY created_at ASC LIMIT 5"
            );

            if (res.rows.length > 0) {
                console.log(`\n📩 Procesando ${res.rows.length} mensajes pendientes...`);

                for (const msg of res.rows) {
                    const chatId = `${msg.numero}@c.us`;

                    try {
                        await client.sendMessage(chatId, msg.mensaje);
                        await pool.query(
                            "UPDATE whatsapp_pending_messages SET status = 'enviado', updated_at = NOW() WHERE id = $1",
                            [msg.id]
                        );
                        console.log(`✅ Mensaje enviado con éxito a: ${msg.numero}`);
                    } catch (err) {
                        console.error(`❌ Error enviando a ${msg.numero}:`, err.message);
                        await pool.query(
                            "UPDATE whatsapp_pending_messages SET status = 'fallido', updated_at = NOW() WHERE id = $1",
                            [msg.id]
                        );
                    }
                    // Espera de 3 segundos entre envíos para evitar bloqueos
                    await new Promise(resolve => setTimeout(resolve, 3000));
                }
            }
        } catch (err) {
            console.error('Error consultando la base de datos:', err.message);
        }
    }, 10000);
}

// 5. INICIALIZAR EL CLIENTE
client.initialize();
