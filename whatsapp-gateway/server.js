const express = require('express');
const { Client, LocalAuth } = require('whatsapp-web.js');
const crypto = require('crypto');
const qrcode = require('qrcode');
const axios = require('axios');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const PORT = process.env.GATEWAY_PORT || 3000;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL || 'http://127.0.0.1:8000/webhook/whatsapp';

// Webhook signature helper
function signPayload(payload) {
    const secret = process.env.WEBHOOK_SECRET || '';
    if (!secret) return '';
    return crypto.createHmac('sha256', secret).update(payload).digest('hex');
}

// File logging helper
const LOG_FILE = path.join(__dirname, 'gateway.log');
function logToFile(message) {
    const timestamp = new Date().toISOString();
    const logLine = `[${timestamp}] ${message}\n`;
    fs.appendFileSync(LOG_FILE, logLine);
    console.log(message);
}

// Map to store client states: token -> clientState
// clientState: { client, status, lastQr, qrResolver, readyResolver }
const clients = new Map();

// Helper to extract Authorization token
function getDeviceToken(req) {
    const auth = req.headers.authorization;
    if (!auth) return null;
    return auth.trim();
}

// Helper to extract clean JID without assuming @c.us
function cleanJid(jid = '') {
    return String(jid).split('@')[0];
}

// Helper to resolve sender identity safely (LID-compatible)
async function resolveSender(msg) {
    try {
        const contact = await msg.getContact();
        return {
            raw: msg.from,
            jid: contact?.id?._serialized || msg.from,
            number: contact?.number || null,
            pushname: contact?.pushname || null,
            isLid: msg.from.includes('@lid'),
            isGroup: msg.from.includes('@g.us'),
            isNewsletter: msg.from.includes('@newsletter'),
            isBroadcast: msg.from.includes('@broadcast'),
            isPrivate: msg.from.includes('@c.us') || msg.from.includes('@lid'),
        };
    } catch (e) {
        return {
            raw: msg.from,
            jid: msg.from,
            number: null,
            pushname: null,
            isLid: msg.from.includes('@lid'),
            isGroup: msg.from.includes('@g.us'),
            isNewsletter: msg.from.includes('@newsletter'),
            isBroadcast: msg.from.includes('@broadcast'),
            isPrivate: msg.from.includes('@c.us') || msg.from.includes('@lid'),
        };
    }
}

// Helper to format target for whatsapp-web.js (handles all JID types)
function formatTarget(target) {
    if (!target) return null;
    const str = String(target).trim();
    
    // Already has a valid suffix
    if (/@(c\.us|lid|g\.us|newsletter|broadcast)$/.test(str)) {
        return str;
    }
    
    // Strip any existing @ suffix and re-add @c.us as default for plain numbers
    const clean = str.split('@')[0].replace(/[^\d]/g, '');
    return `${clean}@c.us`;
}

// Helper to create MessageMedia from URL with retry
async function createMessageMediaFromUrl(fileUrl, retries = 3) {
    const axios = require('axios');
    const { MessageMedia } = require('whatsapp-web.js');
    
    let lastError;
    for (let attempt = 1; attempt <= retries; attempt++) {
        try {
            logToFile(`[Gateway] Download attempt ${attempt}/${retries} for ${fileUrl}`);
            const response = await axios.get(fileUrl, { 
                responseType: 'arraybuffer',
                timeout: 60000,
                maxContentLength: 50 * 1024 * 1024 // 50MB max
            });
            const mimeType = response.headers['content-type'] || 'application/octet-stream';
            const base64 = Buffer.from(response.data).toString('base64');
            return new MessageMedia(mimeType, base64);
        } catch (err) {
            lastError = err;
            logToFile(`[Gateway] Download attempt ${attempt} failed: ${err.message}`);
            if (attempt < retries) {
                await sleep(2000 * attempt);
            }
        }
    }
    throw lastError;
}

// Helper to safely send a message (LID-compatible)
async function safeSendMessage(client, target, text) {
    const formatted = formatTarget(target);
    if (!formatted) throw new Error('Invalid target');
    
    // For @lid targets, prefer chat.sendMessage() to avoid "No LID for user" errors
    if (formatted.endsWith('@lid')) {
        try {
            const chat = await client.getChatById(formatted);
            return await chat.sendMessage(text);
        } catch (chatErr) {
            logToFile(`[Gateway] chat.sendMessage failed for ${formatted}, falling back to client.sendMessage: ${chatErr.message}`);
        }
    }
    
    // Fallback for @c.us or after chat.sendMessage failure
    return await client.sendMessage(formatted, text);
}

// Helper to send a message with optional file attachment
async function sendMessageWithFile(client, target, text, fileUrl) {
    const formatted = formatTarget(target);
    if (!formatted) throw new Error('Invalid target');

    if (fileUrl) {
        try {
            logToFile(`[Gateway] Downloading file from ${fileUrl} for ${formatted}`);
            const media = await createMessageMediaFromUrl(fileUrl);
            const chat = await client.getChatById(formatted);
            
            // Retry send for WhatsApp Web flakiness
            let lastSendError;
            for (let attempt = 1; attempt <= 3; attempt++) {
                try {
                    if (text) {
                        await chat.sendMessage(media, { caption: text });
                    } else {
                        await chat.sendMessage(media);
                    }
                    logToFile(`[Gateway] File sent successfully to ${formatted}`);
                    return true;
                } catch (sendErr) {
                    lastSendError = sendErr;
                    logToFile(`[Gateway] WhatsApp send attempt ${attempt} failed: ${sendErr.message}`);
                    if (attempt < 3) {
                        await sleep(3000 * attempt);
                    }
                }
            }
            throw lastSendError;
        } catch (err) {
            logToFile(`[Gateway] File send failed for ${formatted}: ${err.message}. Falling back to text.`);
            // Fallback to text only
            await safeSendMessage(client, target, text || 'File gagal dikirim, silakan coba lagi.');
            return false;
        }
    } else {
        await safeSendMessage(client, target, text);
        return true;
    }
}

// Function to delay execution
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Calculate delay in ms from string (e.g. "2-5" or "5-10")
function getDelayMs(delayStr) {
    if (!delayStr) return 2000;
    const parts = delayStr.split('-').map(Number);
    if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
        const min = parts[0] * 1000;
        const max = parts[1] * 1000;
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
    return (Number(delayStr) * 1000) || 2000;
}

// Get or initialize a client instance for a given token
async function getOrCreateClient(token) {
    if (clients.has(token)) {
        return clients.get(token);
    }

    logToFile(`[Gateway] Initializing new client instance for token: ${token.substring(0, 8)}...`);

    const client = new Client({
        authStrategy: new LocalAuth({ 
            clientId: token,
            dataPath: path.join(__dirname, '.wwebjs_auth')
        }),
        puppeteer: {
            headless: true,
            ...(process.env.PUPPETEER_EXECUTABLE_PATH ? { executablePath: process.env.PUPPETEER_EXECUTABLE_PATH } : {}),
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu'
            ]
        }
    });

    const state = {
        client,
        status: 'disconnected', // disconnected, initializing, qr, connected
        lastQr: null,
        qrResolver: null,
        readyResolver: null
    };

    clients.set(token, state);

    client.on('qr', async (qr) => {
        state.status = 'qr';
        logToFile(`[Gateway] QR code generated for token: ${token.substring(0, 8)}...`);
        try {
            const qrBase64 = await qrcode.toDataURL(qr);
            state.lastQr = qrBase64;
            if (state.qrResolver) {
                state.qrResolver(qrBase64);
                state.qrResolver = null;
            }
        } catch (err) {
            logToFile(`[Gateway] QR code generation error: ${err.message}`);
        }
    });

    client.on('ready', () => {
        state.status = 'connected';
        state.lastQr = null;
        logToFile(`[Gateway] Client is ready for token: ${token.substring(0, 8)}...`);

        if (state.readyResolver) {
            state.readyResolver();
            state.readyResolver = null;
        }

        // Notify Laravel about the successful connection
        (() => {
            const payload = { status: 'connected', token };
            const body = JSON.stringify(payload);
            axios.post(`${LARAVEL_WEBHOOK_URL}/device`, payload, {
                headers: { 'X-Webhook-Signature': signPayload(body) }
            }).catch(err => logToFile(`[Gateway] Failed to post connected webhook to Laravel: ${err.message}`));
        })();
    });

    client.on('disconnected', (reason) => {
        state.status = 'disconnected';
        state.lastQr = null;
        logToFile(`[Gateway] Client disconnected for token: ${token.substring(0, 8)}... Reason: ${reason}`);

        // Notify Laravel about the disconnection
        (() => {
            const payload = { status: 'disconnected', token };
            const body = JSON.stringify(payload);
            axios.post(`${LARAVEL_WEBHOOK_URL}/device`, payload, {
                headers: { 'X-Webhook-Signature': signPayload(body) }
            }).catch(err => logToFile(`[Gateway] Failed to post disconnected webhook to Laravel: ${err.message}`));
        })();

        try {
            client.destroy();
        } catch (e) {}
        clients.delete(token);
    });

    client.on('message', async (msg) => {
        try {
            // Skip empty messages
            if (!msg.body || msg.body.trim() === '') {
                return;
            }

            const sender = await resolveSender(msg);
            
            logToFile(`[Gateway] Message event - from: ${msg.from}, author: ${msg.author || 'N/A'}, body: "${msg.body}", isGroup: ${msg.isGroup}, fromMe: ${msg.fromMe}`);
            logToFile(`[Gateway] Sender resolved - jid: ${sender.jid}, number: ${sender.number || 'N/A'}, pushname: ${sender.pushname || 'N/A'}, isLid: ${sender.isLid}`);
            
            // Forward incoming user messages to Laravel webhook for Auto Reply processing
            if (sender.isPrivate && !msg.isGroup && !msg.fromMe) {
                const cleanNumber = sender.number || cleanJid(sender.jid);
                
                logToFile(`[Gateway] Forwarding message from ${cleanNumber} (${sender.isLid ? 'LID' : 'c.us'}): "${msg.body}" to ${LARAVEL_WEBHOOK_URL}/message`);
                
                try {
                    const payload = {
                        sender: cleanNumber,
                        message: msg.body,
                        token: token,
                        is_lid: sender.isLid,
                        raw_sender: msg.from,
                        normalized_sender: sender.jid,
                        pushname: sender.pushname,
                    };
                    const body = JSON.stringify(payload);
                    const response = await axios.post(`${LARAVEL_WEBHOOK_URL}/message`, payload, {
                        headers: { 'X-Webhook-Signature': signPayload(body) }
                    });
                    logToFile(`[Gateway] Laravel webhook response: ${response.status} - ${JSON.stringify(response.data)}`);
                } catch (err) {
                    logToFile(`[Gateway] Failed to forward message to Laravel: ${err.message}`);
                    if (err.response) {
                        logToFile(`[Gateway] Laravel responded with: ${err.response.status} - ${JSON.stringify(err.response.data)}`);
                    }
                }
            }
        } catch (err) {
            logToFile(`[Gateway] Safe message handler error: ${err.message}`);
        }
    });

    // Tangkap semua event pesan (termasuk yang mungkin terlewat oleh 'message')
    client.on('message_create', async (msg) => {
        // Hanya log, tidak forward (untuk debug)
        if (!msg.fromMe) {
            logToFile(`[Gateway] message_create event - from: ${msg.from}, body: "${msg.body}"`);
        }
    });

    state.status = 'initializing';
    client.initialize().catch(err => {
        logToFile(`[Gateway] Client initialization failed: ${err.message}`);
        state.status = 'disconnected';
        clients.delete(token);
    });

    return state;
}

// 1. Device Status Endpoint
app.post('/device', async (req, res) => {
    const token = getDeviceToken(req);
    if (!token) return res.status(401).json({ status: false, message: 'Missing Authorization Token' });

    const state = await getOrCreateClient(token);

    if (state.status === 'connected') {
        res.json({
            status: true,
            device_status: 'connected',
            sender: state.client.info?.wid?.user || '-',
            quota: 999999,
            limit: 999999
        });
    } else {
        res.json({
            status: false,
            device_status: state.status
        });
    }
});

// 2. Fetch QR Code Endpoint
app.post('/qr', async (req, res) => {
    const token = getDeviceToken(req);
    if (!token) return res.status(401).json({ status: false, message: 'Missing Authorization Token' });

    const state = await getOrCreateClient(token);

    if (state.status === 'connected') {
        return res.json({ status: false, reason: 'device already connect' });
    }

    if (state.lastQr) {
        return res.json({ status: true, url: state.lastQr });
    }

    // Wait for the QR code to generate asynchronously (up to 25 seconds)
    const qrPromise = new Promise((resolve) => {
        state.qrResolver = resolve;
        setTimeout(() => {
            if (state.qrResolver === resolve) {
                state.qrResolver = null;
            }
            resolve(null);
        }, 25000);
    });

    const qr = await qrPromise;
    if (qr) {
        res.json({ status: true, url: qr });
    } else {
        res.json({ status: false, message: 'QR Code not generated yet. Please try again.' });
    }
});

// 3. Disconnect Endpoint
app.post('/disconnect', async (req, res) => {
    const token = getDeviceToken(req);
    if (!token) return res.status(401).json({ status: false, message: 'Missing Authorization Token' });

    if (clients.has(token)) {
        const state = clients.get(token);
        try {
            logToFile(`[Gateway] Logging out client for token: ${token.substring(0, 8)}...`);
            await state.client.logout();
            await state.client.destroy();
        } catch (err) {
            console.error('[Gateway] Error logging out/destroying client:', err.message);
            logToFile(`[Gateway] Error logging out/destroying client: ${err.message}`);
        }
        clients.delete(token);
    }

    res.json({ status: true, detail: 'device disconnected' });
});

// 4. Send Message / Bulk Messages Endpoint
app.post('/send', async (req, res) => {
    const token = getDeviceToken(req);
    if (!token) return res.status(401).json({ status: false, message: 'Missing Authorization Token' });

    const state = clients.get(token);
    if (!state || state.status !== 'connected') {
        return res.json({ status: false, message: 'Device is not connected. Connect the device first.' });
    }

    const { target, message, data } = req.body;

    try {
        if (data) {
            // Bulk pengiriman
            const messages = typeof data === 'string' ? JSON.parse(data) : data;
            logToFile(`[Gateway] Processing bulk dispatch of ${messages.length} messages...`);
            
            // Run bulk sending in background so HTTP response is returned immediately (async gateway)
            (async () => {
                for (const msg of messages) {
                    try {
                        const delay = getDelayMs(msg.delay);
                        await sleep(delay);
                        
                        await sendMessageWithFile(state.client, msg.target, msg.message, msg.url);
                    } catch (sendErr) {
                        console.error(`[Gateway] Error sending bulk item to ${msg.target}:`, sendErr.message);
                        logToFile(`[Gateway] Error sending bulk item to ${msg.target}: ${sendErr.message}`);
                    }
                }
            })();

            return res.json({ status: true, detail: 'messages scheduled/sent' });
        } else if (target && message) {
            // Single pengiriman (with optional file)
            logToFile(`[Gateway] Single message - target: ${target}, message: ${message.substring(0, 50)}..., url: ${req.body.url || 'none'}`);
            
            // Run in background to avoid Laravel timeout (async gateway)
            (async () => {
                try {
                    await sendMessageWithFile(state.client, target, message, req.body.url);
                } catch (sendErr) {
                    logToFile(`[Gateway] Error sending single message to ${target}: ${sendErr.message}`);
                }
            })();

            return res.json({ status: true, detail: 'message sent' });
        }

        res.json({ status: false, message: 'Invalid payload. target/message or data parameter required.' });
    } catch (err) {
        console.error('[Gateway] Send message API error:', err);
        logToFile(`[Gateway] Send message API error: ${err.message}`);
        res.json({ status: false, message: err.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    logToFile(`[Gateway] Self-hosted WhatsApp Gateway running on http://0.0.0.0:${PORT}`);
    logToFile(`[Gateway] Laravel Webhook URL configured to: ${LARAVEL_WEBHOOK_URL}`);
});
