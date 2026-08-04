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

// 1. CONFIGURACIÓN DE LA BASE DE DATOS DUAL (Soporte Nativo para MySQL en Local y PostgreSQL en Render/Cloud)
let isMysql = (!process.env.DATABASE_URL && (process.env.DB_CONNECTION === 'mysql' || process.env.DB_USERNAME === 'root'));
let mysqlPool = null;
let pgPool = null;

function getPgPoolConfig(useSsl, fallbackCandidate = null) {
    if (process.env.DATABASE_URL) {
        return {
            connectionString: process.env.DATABASE_URL,
            ssl: useSsl ? { rejectUnauthorized: false } : false
        };
    }

    if (fallbackCandidate) {
        return {
            user: fallbackCandidate.user,
            host: fallbackCandidate.host,
            database: fallbackCandidate.db,
            password: fallbackCandidate.pass,
            port: 5432,
            ssl: fallbackCandidate.ssl ? { rejectUnauthorized: false } : false
        };
    }

    let user = process.env.DB_PG_USER || process.env.PGUSER || process.env.DB_USERNAME || 'crm_admin';
    let password = process.env.DB_PG_PASSWORD || process.env.PGPASSWORD || process.env.DB_PASSWORD || 'Carlosaraiza2810';
    let host = process.env.DB_HOST || '127.0.0.1';
    let db = process.env.DB_DATABASE || 'gestor_capital_db';
    let port = parseInt(process.env.DB_PORT || '5432');
    if (port === 3306) port = 5432;

    return {
        user: user,
        host: (host === 'localhost') ? '127.0.0.1' : host,
        database: db,
        password: password,
        port: port,
        ssl: useSsl ? { rejectUnauthorized: false } : false
    };
}

let currentSslSetting = !!(process.env.DATABASE_URL || (process.env.DB_HOST && !['127.0.0.1', 'localhost'].includes(process.env.DB_HOST)));

if (isMysql) {
    const mysql = require('mysql2');
    const mysqlHost = (process.env.DB_HOST === 'localhost' || !process.env.DB_HOST) ? '127.0.0.1' : process.env.DB_HOST;
    mysqlPool = mysql.createPool({
        host: mysqlHost,
        port: parseInt(process.env.DB_PORT || '3306'),
        user: process.env.DB_USERNAME || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_DATABASE || 'gestor_capital_db',
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0
    });
    console.log(`🗄️ Inicializado pool MySQL para host "${mysqlHost}" en base de datos "${process.env.DB_DATABASE || 'gestor_capital_db'}"`);
} else {
    pgPool = new Pool(getPgPoolConfig(currentSslSetting));
}

// Función unificada de consulta asíncrona compatible con MySQL y PostgreSQL
function queryDb(sql, params = []) {
    return new Promise((resolve, reject) => {
        if (isMysql && mysqlPool) {
            // Reemplazar sintaxis $1, $2 por ? para MySQL
            const mysqlSql = sql.replace(/\$\d+/g, '?');
            mysqlPool.query(mysqlSql, params, (err, results) => {
                if (err) return reject(err);
                const rows = Array.isArray(results) ? results : [results];
                resolve({ rows: rows });
            });
        } else if (pgPool) {
            pgPool.query(sql, params, (err, res) => {
                if (err) return reject(err);
                resolve(res);
            });
        } else {
            reject(new Error('No hay pool de Base de Datos inicializado.'));
        }
    });
}

let currentDbInfo = {
    database: process.env.DB_DATABASE || 'gestor_capital_db',
    host: isMysql ? (process.env.DB_HOST || '127.0.0.1') : (process.env.DATABASE_URL ? 'Render Cloud PostgreSQL' : (process.env.DB_HOST || '127.0.0.1')),
    user: process.env.DB_USERNAME || process.env.DB_USER || 'root',
    connected: false
};

function probarConexionBaseDatos() {
    const testQuery = isMysql ? 'SELECT DATABASE() as current_db_name, USER() as current_user_name' : 'SELECT current_database(), current_user';
    queryDb(testQuery)
        .then(res => {
            const dbName = res.rows[0]?.current_database || res.rows[0]?.current_db_name || res.rows[0]?.['DATABASE()'] || process.env.DB_DATABASE;
            const dbUser = res.rows[0]?.current_user || res.rows[0]?.current_user_name || res.rows[0]?.['USER()'] || process.env.DB_USERNAME;
            const hostTarget = isMysql ? (process.env.DB_HOST || '127.0.0.1') : (process.env.DATABASE_URL ? 'Render Cloud PostgreSQL' : (process.env.DB_HOST || '127.0.0.1'));
            currentDbInfo = {
                database: dbName,
                host: hostTarget,
                user: dbUser,
                connected: true
            };
            console.log(`🗄️ Conexión exitosa a la Base de Datos (${isMysql ? 'MySQL' : 'PostgreSQL'}): "${dbName}" en [${hostTarget}] como usuario "${dbUser}"`);
        })
        .catch(err => {
            console.error(`❌ Error de conexión a la base de datos (${isMysql ? 'MySQL' : 'PostgreSQL'}):`, err.message);
            currentDbInfo.connected = false;
            guardarEstado('error', `Error de conexión a la Base de Datos ${isMysql ? 'MySQL' : 'PostgreSQL'}`, {
                error_type: 'Base de Datos',
                detail: err.stack || err.message,
                solution_hint: 'Verifica la variable DATABASE_URL en Render o las variables DB_USERNAME / DB_PASSWORD en el archivo .env.'
            });
        });
}

probarConexionBaseDatos();

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
        const puppeteer = require('puppeteer');
        const pPath = puppeteer.executablePath();
        if (pPath && fs.existsSync(pPath)) return pPath;
    } catch (e) {}

    try {
        const found = execSync('which chromium || which chromium-browser || which google-chrome || which google-chrome-stable 2>/dev/null', { encoding: 'utf8' }).trim();
        if (found && fs.existsSync(found)) return found;
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
    qrMaxRetries: 3,
    takeoverOnConflict: true,
    puppeteer: puppeteerOptions
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

let qrGeneratedCount = 0;

// 3. EVENTOS DEL SISTEMA
client.on('qr', async (qr) => {
    qrGeneratedCount++;
    console.log(`📸 Código QR #${qrGeneratedCount} generado correctamente.`);

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
        
        guardarEstado('qr_pendiente', 'Código QR listo para escanear. Abre WhatsApp en tu celular > Dispositivos vinculados.');
    } catch (e) {
        console.error('Error guardando imagen QR:', e.message);
        guardarEstado('error', 'No se pudo guardar la imagen del código QR en el servidor.', {
            error_type: 'Generación de QR',
            detail: e.message,
            solution_hint: 'Verifica que el servidor tenga permisos de escritura en la carpeta public/img.'
        });
    }
});

let bucleIniciado = false;

function arrancarBucle() {
    if (!bucleIniciado) {
        bucleIniciado = true;
        console.log('🚀 Bucle de procesamiento de mensajes de WhatsApp activado.');
        iniciarBucleEnvio();
    }
}

client.on('authenticated', () => {
    console.log('🔑 AUTENTICADO: Sesión validada por el dispositivo móvil.');
    guardarEstado('conectado', 'WhatsApp vinculado y activo en el sistema. Dispositivo autenticado.');
    limpiarQRArchivos();
    arrancarBucle();
});

client.on('ready', () => {
    console.log('🚀 MOTOR LISTO: WhatsApp está conectado y listo para enviar mensajes.');
    guardarEstado('conectado', 'WhatsApp vinculado y activo en el sistema. Dispositivo autenticado.');
    limpiarQRArchivos();
    arrancarBucle();
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
    bucleIniciado = false;
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
    const lowerDetail = detailStr.toLowerCase();
    
    if (lowerDetail.includes('the browser is already running') || lowerDetail.includes('singletonlock') || lowerDetail.includes('ws endpoint') || lowerDetail.includes('timed out after')) {
        removerLockFiles(path.join(__dirname, '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', 'public', '.wwebjs_auth'));
        console.log('🔄 Reintento automático tras timeout de Chromium...');
        guardarEstado('cargando', 'Iniciando WhatsApp Web (Tiempo de carga extendido en servidor)...');
        setTimeout(() => client.initialize().catch(e => console.error('Error al reinicializar cliente:', e)), 3000);
        return;
    }

    if (lowerDetail.includes('auth timeout') || lowerDetail.includes('qr read timeout') || lowerDetail.includes('protocolerror')) {
        removerLockFiles(path.join(__dirname, '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', '.wwebjs_auth'));
        removerLockFiles(path.join(__dirname, '..', 'public', '.wwebjs_auth'));
        console.log('🔄 Reintento automático por expiración del QR / Auth Timeout...');
        guardarEstado('cargando', 'Generando automáticamente nuevo código QR (Expiró el tiempo del código anterior)...');
        setTimeout(() => client.initialize().catch(e => console.error('Error al reinicializar cliente tras auth timeout:', e)), 3000);
        return;
    }

    guardarEstado('error', 'Error en promesa asíncrona del motor de WhatsApp.', {
        error_type: 'Promesa Asíncrona',
        detail: detailStr,
        solution_hint: 'Reinicia la sesión con el botón de "Cerrar Sesión & Nuevo QR".'
    });
});

// 4. BUCLE DE CONSULTA DUAL CADA 5 SEGUNDOS
function iniciarBucleEnvio() {
    setInterval(async () => {
        try {
            const res = await queryDb(
                "SELECT * FROM whatsapp_pending_messages WHERE status = 'pendiente' ORDER BY created_at ASC LIMIT 5"
            );

            if (res.rows && res.rows.length > 0) {
                console.log(`\n📩 Procesando ${res.rows.length} mensajes pendientes...`);

                for (const msg of res.rows) {
                    try {
                        // 1. Limpiamos el número y le damos formato
                        let rawNum = (msg.numero || '').toString().trim();
                        let cleanNum = rawNum.includes('@c.us') ? rawNum.replace('@c.us', '') : rawNum;
                        cleanNum = cleanNum.replace(/\D/g, ''); // Deja solo dígitos

                        if (!cleanNum) {
                            console.error(`⚠️ Número inválido en mensaje #${msg.id}`);
                            await queryDb(
                                "UPDATE whatsapp_pending_messages SET status = 'fallido', updated_at = NOW() WHERE id = $1",
                                [msg.id]
                            );
                            continue;
                        }

                        // Formato estándar de chat ID de WhatsApp
                        let targetId = `${cleanNum}@c.us`;
                        try {
                            const contactId = await client.getNumberId(cleanNum);
                            if (contactId && contactId._serialized) {
                                targetId = contactId._serialized;
                            }
                        } catch (eId) {
                            console.log(`⚠️ No se pudo obtener ID verificado para ${cleanNum}, usando destino por defecto ${targetId}`);
                        }

                        console.log(`🚀 Enviando mensaje #${msg.id} a ${targetId}...`);
                        await client.sendMessage(targetId, msg.mensaje);

                        await queryDb(
                            "UPDATE whatsapp_pending_messages SET status = 'enviado', updated_at = NOW() WHERE id = $1",
                            [msg.id]
                        );
                        console.log(`✅ Mensaje #${msg.id} enviado con éxito a ${msg.numero} (${targetId})`);

                    } catch (err) {
                        console.error(`❌ Error procesando el mensaje #${msg.id} para ${msg.numero}:`, err.message);
                        await queryDb(
                            "UPDATE whatsapp_pending_messages SET status = 'fallido', updated_at = NOW() WHERE id = $1",
                            [msg.id]
                        ).catch(() => {});
                    }

                    // Espera de 3 segundos entre envíos para evitar bloqueos por spam
                    await new Promise(resolve => setTimeout(resolve, 3000));
                }
            }
        } catch (err) {
            console.error('Error consultando la base de datos:', err.message);
        }
    }, 5000);
}

// 5. INICIALIZAR EL CLIENTE
client.initialize();
