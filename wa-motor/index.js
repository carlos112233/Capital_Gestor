const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { Pool } = require('pg');
const QRCodeImage = require('qrcode');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
require('dotenv').config();

// 1. CONFIGURACIÓN DE LA BASE DE DATOS (Soporte para Render & PostgreSQL Local)
const poolConfig = process.env.DATABASE_URL
    ? { connectionString: process.env.DATABASE_URL, ssl: { rejectUnauthorized: false } }
    : {
        user: process.env.DB_USER || 'crm_admin',
        host: process.env.DB_HOST || '127.0.0.1',
        database: process.env.DB_DATABASE || 'capital_gestor_db',
        password: process.env.DB_PASSWORD || 'Carlosaraiza2810',
        port: parseInt(process.env.DB_PORT || '5432'),
        ssl: (process.env.DB_HOST && process.env.DB_HOST !== '127.0.0.1') ? { rejectUnauthorized: false } : false
    };

const pool = new Pool(poolConfig);

// Detectar ejecutable de Chrome / Chromium en el servidor o entorno local
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

let chromePath = findChromePath();

if (!chromePath) {
    console.log('⏳ No se detectó navegador en el sistema. Descargando Chromium automáticamente...');
    try {
        execSync('npx puppeteer browsers install chrome', { cwd: __dirname, stdio: 'inherit' });
        chromePath = findChromePath();
    } catch (e) {
        console.error('Error al descargar navegador automáticamente:', e.message);
    }
}

console.log(`🌐 Navegador detectado para WhatsApp: ${chromePath || 'Chromium nativo de Puppeteer'}`);
console.log('⌛ Iniciando Chromium y cargando WhatsApp Web... Por favor espera unos segundos.');

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
        '--disable-background-networking',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-breakpad',
        '--disable-component-extensions-with-background-pages',
        '--disable-ipc-flooding-protection',
        '--disable-renderer-backgrounding',
        '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
    ]
};

if (chromePath) {
    puppeteerOptions.executablePath = chromePath;
}

// 2. CONFIGURACIÓN DEL CLIENTE WHATSAPP CON CACHÉ LOCAL VÁLIDO
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './.wwebjs_auth' // Guarda la sesión permanentemente
    }),
    puppeteer: puppeteerOptions,
    webVersionCache: {
        type: 'local'
    }
});

// Función para limpiar imágenes QR cuando la sesión esté conectada
function limpiarQRArchivos() {
    try {
        const qrPath = path.join(__dirname, 'qr.png');
        const publicImgPath = path.join(__dirname, '..', 'public', 'img', 'qr.png');
        const publicPath = path.join(__dirname, '..', 'public', 'qr.png');
        const brainPath = '/home/araiza/.gemini/antigravity-ide/brain/20e4ef77-10df-4090-9fac-89a163a822e6/qr.png';

        if (fs.existsSync(qrPath)) fs.unlinkSync(qrPath);
        if (fs.existsSync(publicImgPath)) fs.unlinkSync(publicImgPath);
        if (fs.existsSync(publicPath)) fs.unlinkSync(publicPath);
        if (fs.existsSync(brainPath)) fs.unlinkSync(brainPath);
        console.log('🧹 QR eliminado automáticamente porque la sesión ya se encuentra vinculada y activa.');
    } catch (e) {
        console.error('Error al limpiar archivos QR:', e.message);
    }
}

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
        
        const publicImgDir = path.join(__dirname, '..', 'public', 'img');
        if (!fs.existsSync(publicImgDir)) {
            fs.mkdirSync(publicImgDir, { recursive: true });
        }

        const publicImgPath = path.join(publicImgDir, 'qr.png');
        const publicPath = path.join(__dirname, '..', 'public', 'qr.png');
        const brainPath = '/home/araiza/.gemini/antigravity-ide/brain/20e4ef77-10df-4090-9fac-89a163a822e6/qr.png';
        
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
    limpiarQRArchivos();
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
