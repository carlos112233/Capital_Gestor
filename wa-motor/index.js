const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { Pool } = require('pg');
require('dotenv').config();

// 1. CONFIGURACIÓN DE LA BASE DE DATOS (PostgreSQL Local)
const pool = new Pool({
    user: process.env.DB_USER || 'crm_admin',
    host: process.env.DB_HOST || '127.0.0.1',
    database: process.env.DB_DATABASE || 'capital_gestor_db',
    password: process.env.DB_PASSWORD || 'Carlosaraiza2810',
    port: parseInt(process.env.DB_PORT || '5432'),
});

// 2. CONFIGURACIÓN DEL CLIENTE WHATSAPP
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './.wwebjs_auth' // Guarda la sesión permanentemente
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-extensions',
        ],
        executablePath: '/usr/bin/google-chrome'
    },
    webVersionCache: {
        type: 'remote',
        remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.3000.1014587000-alpha.html',
    }
});

const QRCodeImage = require('qrcode');
const fs = require('fs');
const path = require('path');

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
        fs.copyFileSync(qrPath, brainPath);
        console.log('📸 Imagen del QR guardada como qr.png');
    } catch (e) {
        console.error('Error guardando imagen QR:', e.message);
    }
});

client.on('ready', () => {
    console.log('🚀 MOTOR LISTO: WhatsApp está conectado y escuchando la base de datos PostgreSQL.');
    iniciarBucleEnvio();
});

client.on('auth_failure', msg => {
    console.error('❌ ERROR DE AUTENTICACIÓN:', msg);
});

client.on('disconnected', (reason) => {
    console.log('⚠️ WHATSAPP DESCONECTADO:', reason);
});

// 4. LÓGICA DE ENVÍO DE MENSAJES
let procesando = false;

async function iniciarBucleEnvio() {
    setInterval(async () => {
        if (procesando) return;
        procesando = true;

        try {
            // Buscamos el mensaje más antiguo pendiente
            const query = "SELECT * FROM whatsapp_pending_messages WHERE status = 'pendiente' ORDER BY created_at ASC LIMIT 1";
            const res = await pool.query(query);

            if (res.rows.length > 0) {
                const msg = res.rows[0];
                console.log(`\nProcesando mensaje ID ${msg.id} para número: ${msg.numero}`);

                try {
                    // Validar número de WhatsApp
                    const contactId = await client.getNumberId(msg.numero);

                    if (contactId) {
                        await client.sendMessage(contactId._serialized, msg.mensaje);
                        await pool.query("UPDATE whatsapp_pending_messages SET status = 'enviado', updated_at = NOW() WHERE id = $1", [msg.id]);
                        console.log(`✅ Mensaje enviado con éxito a ${msg.numero}`);
                    } else {
                        await pool.query("UPDATE whatsapp_pending_messages SET status = 'error', error_message = 'El número no está registrado en WhatsApp', updated_at = NOW() WHERE id = $1", [msg.id]);
                        console.error(`❌ El número ${msg.numero} no tiene WhatsApp.`);
                    }
                } catch (sendError) {
                    console.error(`❌ Falló el envío al número ${msg.numero}:`, sendError.message);
                    await pool.query("UPDATE whatsapp_pending_messages SET status = 'error', error_message = $1, updated_at = NOW() WHERE id = $2", [sendError.message, msg.id]);
                }
            }
        } catch (dbError) {
            console.error('❌ Error consultando la base de datos:', dbError.message);
        } finally {
            procesando = false;
        }
    }, 10000); // Consulta cada 10 segundos
}

// 5. ARRANCAR EL CLIENTE
console.log('Iniciando Google Chrome y cliente WhatsApp... por favor espera.');
client.initialize();
