const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { Pool } = require('pg');
const QRCodeImage = require('qrcode');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Cargar variables de entorno desde el directorio raíz de la aplicación (.env) y del motor
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });
require('dotenv').config({ path: path.join(__dirname, '.env') });

// 1. CONFIGURACIÓN DE LA BASE DE DATOS (Soporte para Render & PostgreSQL Local)
const poolConfig = process.env.DATABASE_URL
    ? { connectionString: process.env.DATABASE_URL, ssl: { rejectUnauthorized: false } }
    : {
        user: process.env.DB_USERNAME || process.env.DB_USER || 'crm_admin',
        host: process.env.DB_HOST || '127.0.0.1',
        database: process.env.DB_DATABASE || 'capital_gestor_db',
        password: process.env.DB_PASSWORD || 'Carlosaraiza2810',
        port: parseInt(process.env.DB_PORT || '5432'),
        ssl: (process.env.DB_HOST && process.env.DB_HOST !== '127.0.0.1') ? { rejectUnauthorized: false } : false
    };

const pool = new Pool(poolConfig);
let currentDbInfo = {
    database: process.env.DB_DATABASE || 'capital_gestor_db',
    host: process.env.DATABASE_URL ? 'Render Cloud PostgreSQL' : (process.env.DB_HOST || '127.0.0.1'),
    user: process.env.DB_USERNAME || process.env.DB_USER || 'crm_admin',
    connected: false
};

// Verificar la conexión activa a PostgreSQL e imprimir la BD y Usuario conectados al iniciar
pool.query('SELECT current_database(), current_user', (err, res) => {
    if (err) {
        console.error('❌ Error de conexión a la base de datos PostgreSQL:', err.message);
        currentDbInfo.connected = false;
        guardarEstado('error', 'Error de conexión a la Base de Datos PostgreSQL', {
            error_type: 'Base de Datos',
            detail: err.stack || err.message,
            solution_hint: 'Verifica la variable DATABASE_URL en Render o las variables DB_USERNAME / DB_PASSWORD en el archivo .env.'
        });
    } else {
        const dbName = res.rows[0].current_database;
        const dbUser = res.rows[0].current_user;
        const hostTarget = process.env.DATABASE_URL ? 'Render Cloud PostgreSQL' : (process.env.DB_HOST || '127.0.0.1');
        currentDbInfo = {
            database: dbName,
            host: hostTarget,
            user: dbUser,
            connected: true
        };
        console.log(`🗄️ Conexión exitosa a la Base de Datos PostgreSQL: "${dbName}" en [${hostTarget}] como usuario "${dbUser}"`);
    }
});

// Capturar errores globales en la piscina de la base de datos
pool.on('error', (err) => {
    console.error('❌ Error en el cliente de base de datos PostgreSQL:', err.message);
    currentDbInfo.connected = false;
    guardarEstado('error', 'Error de conexión a la Base de Datos PostgreSQL', {
        error_type: 'Base de Datos',
        detail: err.stack || err.message,
        solution_hint: 'Verifica la variable DATABASE_URL o credenciales DB_HOST/DB_USERNAME/DB_PASSWORD en el servidor.'
    });
});

// Función para registrar y guardar el estado actual de la sesión de WhatsApp con diagnóstico detallado
function guardarEstado(estado, mensaje, opciones = {}) {
    const payload = {
        status: estado, // 'cargando', 'qr_pendiente', 'conectado', 'error', 'desconectado'
        message: mensaje,
        error_type: opciones.error_type || null,
        detail: opciones.detail || null,
        solution_hint: opciones.solution_hint || null,
        db_info: currentDbInfo,
        db_name: currentDbInfo.database,
        updated_at: new Date().toISOString()
    };
    try {
        const publicStatusPath = path.join(__dirname, '..', 'public', 'wa-status.json');
        const localStatusPath = path.join(__dirname, 'status.json');
        fs.writeFileSync(publicStatusPath, JSON.stringify(payload, null, 2));
        fs.writeFileSync(localStatusPath, JSON.stringify(payload, null, 2));
        console.log(`📌 Estado de WhatsApp [${estado}]: ${mensaje}`);
    } catch (e) {
        console.error('Error guardando estado de WhatsApp:', e.message);
    }
}

// Eliminar automáticamente archivos de bloqueo de Chromium (SingletonLock) que impiden el inicio
function removerLockFiles(dirPath) {
    try {
        if (!fs.existsSync(dirPath)) return;
        const items = fs.readdirSync(dirPath, { withFileTypes: true });
        for (const item of items) {
            const fullPath = path.join(dirPath, item.name);
            if (item.isDirectory()) {
                removerLockFiles(fullPath);
            } else if (item.name.includes('Singleton') || item.name === 'DevToolsActivePort') {
                try {
                    fs.unlinkSync(fullPath);
                    console.log(`🧹 Lockfile de Chromium eliminado automáticamente: ${item.name}`);
                } catch (e) {}
            }
        }
    } catch (e) {}
}

// Limpiar cerrojos antes de iniciar Chromium
removerLockFiles(path.join(__dirname, '.wwebjs_auth'));
removerLockFiles(path.join(__dirname, '..', '.wwebjs_auth'));
removerLockFiles(path.join(__dirname, '..', 'public', '.wwebjs_auth'));

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
    guardarEstado('cargando', 'Navegador no encontrado en el sistema. Intentando instalación automática de Chromium...', {
        error_type: 'Navegador',
        solution_hint: 'Si la descarga falla, instala Chromium manualmente en tu servidor Linux (apt install -y chromium).'
    });

    try {
        execSync('npx puppeteer browsers install chrome', { cwd: __dirname, stdio: 'inherit' });
        chromePath = findChromePath();
    } catch (e) {
        console.error('Error al descargar navegador automáticamente:', e.message);
        guardarEstado('error', 'No se pudo iniciar el navegador Chromium en el servidor.', {
            error_type: 'Navegador',
            detail: e.message,
            solution_hint: 'Ejecuta en la consola de tu servidor Linux: apt update && apt install -y chromium'
        });
    }
}

console.log(`🌐 Navegador detectado para WhatsApp: ${chromePath || 'Chromium nativo de Puppeteer'}`);
guardarEstado('cargando', 'Iniciando navegador Chromium y conectando a WhatsApp Web...');

// Parámetros sin cuellos de botella de renderizado para procesamiento multi-hilo en tiempo real de los Web Workers
const puppeteerOptions = {
    headless: true,
    bypassCSP: true,
    timeout: 120000, // Extiende el tiempo límite a 120s para prevenir "Timed out after 30000 ms" en servidores con RAM restringida
    protocolTimeout: 300000,
    args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--no-zygote',
        '--disable-accelerated-2d-canvas',
        '--no-first-run',
        '--disable-gpu',
        '--disable-software-rasterizer',
        '--disable-extensions',
        '--disable-web-security',
        '--disable-background-networking',
        '--disable-background-timer-throttling',
        '--disable-backgrounding-occluded-windows',
        '--disable-breakpad',
        '--disable-component-extensions-with-background-pages',
        '--disable-ipc-flooding-protection',
        '--disable-renderer-backgrounding',
        '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
    ]
};

if (chromePath) {
    puppeteerOptions.executablePath = chromePath;
}

// 2. CLIENTE WHATSAPP CON CACHÉ LOCAL Y HANDSHAKE INSTANTÁNEO
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './.wwebjs_auth'
    }),
    authTimeoutMs: 300000,
    qrMaxRetries: 10,
    takeoverOnConflict: true,
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
        
        guardarEstado('qr_pendiente', 'Código QR generado correctamente. Esperando escaneo desde teléfono móvil.');
        console.log('📸 Imagen del QR actualizada en public/img/qr.png');
    } catch (e) {
        console.error('Error guardando imagen QR:', e.message);
        guardarEstado('error', 'No se pudo guardar la imagen del código QR en el servidor.', {
            error_type: 'Generación de QR',
            detail: e.message,
            solution_hint: 'Verifica que el servidor tenga permisos de escritura en la carpeta public/img.'
        });
    }
});

client.on('authenticated', () => {
    console.log('🔑 AUTENTICADO: Sesión validada por el dispositivo móvil.');
    guardarEstado('conectado', 'WhatsApp vinculado y activo en el sistema. Dispositivo autenticado.');
    limpiarQRArchivos();
});

client.on('ready', () => {
    console.log('🚀 MOTOR LISTO: WhatsApp está conectado y escuchando la base de datos PostgreSQL.');
    guardarEstado('conectado', 'WhatsApp vinculado y activo en el sistema. Dispositivo autenticado.');
    limpiarQRArchivos();
    iniciarBucleEnvio();
});

client.on('auth_failure', msg => {
    console.error('❌ Error de autenticación en WhatsApp:', msg);
    guardarEstado('error', 'Falló la autenticación con WhatsApp. La sesión expiró o fue revocada.', {
        error_type: 'Autenticación',
        detail: typeof msg === 'string' ? msg : JSON.stringify(msg),
        solution_hint: 'Haz clic en "Cerrar Sesión & Nuevo QR" para borrar la sesión anterior e intentar de nuevo.'
    });
});

client.on('disconnected', (reason) => {
    console.log('⚠️ Cliente desconectado:', reason);
    guardarEstado('desconectado', 'La sesión fue desconectada del dispositivo móvil.', {
        error_type: 'Desconexión',
        detail: typeof reason === 'string' ? reason : JSON.stringify(reason),
        solution_hint: 'Comprueba que tu celular tenga conexión a internet y vuelve a vincular el dispositivo.'
    });
    console.log('Reiniciando cliente...');
    client.initialize();
});

// Captura de excepciones globales para evitar bloqueos silenciosos
process.on('uncaughtException', (err) => {
    console.error('❌ Excepción no capturada en el motor:', err);
    const detailStr = err.stack || err.message;

    if (detailStr.includes('The browser is already running') || detailStr.includes('SingletonLock') || detailStr.includes('WS endpoint') || detailStr.includes('Timed out after')) {
        removerLockFiles(path.join(__dirname, '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', 'public', '.wwebjs_auth'));
        console.log('🔄 Reintento automático tras timeout de Chromium...');
        guardarEstado('cargando', 'Iniciando WhatsApp Web (Tiempo de carga extendido en servidor)...');
        setTimeout(() => client.initialize().catch(e => console.error('Error al reinicializar cliente:', e)), 3000);
        return;
    }

    guardarEstado('error', 'Ocurrió una excepción no controlada en el motor de WhatsApp.', {
        error_type: 'Excepción del Sistema',
        detail: detailStr,
        solution_hint: 'Haz clic en "Cerrar Sesión & Nuevo QR" para reiniciar el motor de notificaciones.'
    });
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Promesa rechazada no manejada:', reason);
    const detailStr = reason ? (reason.stack || reason.message || String(reason)) : 'Rechazo desconocido';
    
    if (detailStr.includes('The browser is already running') || detailStr.includes('SingletonLock') || detailStr.includes('WS endpoint') || detailStr.includes('Timed out after')) {
        removerLockFiles(path.join(__dirname, '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', 'public', '.wwebjs_auth'));
        console.log('🔄 Reintento automático tras timeout de Chromium...');
        guardarEstado('cargando', 'Iniciando WhatsApp Web (Tiempo de carga extendido en servidor)...');
        setTimeout(() => client.initialize().catch(e => console.error('Error al reinicializar cliente:', e)), 3000);
        return;
    }

    if (detailStr.includes('timed out') || detailStr.includes('ProtocolError')) {
        guardarEstado('cargando', 'El servidor está inyectando WhatsApp Web (Tiempo de carga extendido)...');
    } else {
        guardarEstado('error', 'Error en promesa asíncrona del motor de WhatsApp.', {
            error_type: 'Promesa Asíncrona',
            detail: detailStr,
            solution_hint: 'Reinicia la sesión con el botón de "Cerrar Sesión & Nuevo QR".'
        });
    }
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
            guardarEstado('error', 'Error al consultar mensajes pendientes en PostgreSQL.', {
                error_type: 'Base de Datos',
                detail: err.message,
                solution_hint: 'Verifica que la tabla whatsapp_pending_messages exista en la base de datos.'
            });
        }
    }, 10000);
}

// 5. INICIALIZAR EL CLIENTE
client.initialize();
