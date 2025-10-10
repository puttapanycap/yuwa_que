#!/usr/bin/env node
/**
 * Queue Ticket Thermal Printer Service
 * ------------------------------------
 *
 * Lightweight HTTP service that accepts queue ticket print requests and
 * forwards them to an ESC/POS compatible thermal printer using
 * the `esc-pos-encoder` package and raw socket connections.
 *
 * The service exposes two endpoints:
 *   - POST /commands/print
 *       Accepts raw ESC/POS payloads (base64/hex/ascii) that mirror the
 *       request format used by the BIXOLON Web Print service.
 *   - POST /print-ticket
 *       Accepts structured JSON describing a queue ticket and renders it
 *       with helper utilities powered by esc-pos-encoder.
 *
 * Environment variables:
 *   QUEUE_PRINTER_HOST            Host to bind the HTTP server (default: 0.0.0.0)
 *   QUEUE_PRINTER_PORT            Port to bind the HTTP server (default: 18080)
 *   QUEUE_PRINTER_INTERFACE       Printer interface URI (e.g. tcp://192.168.0.50:9100)
 *   QUEUE_PRINTER_TYPE            Printer type: epson, star, tanca, daruma, brother, custom (default: epson)
 *   QUEUE_PRINTER_CHARSET         ESC/POS code page (default: thai11)
 *   QUEUE_PRINTER_TIMEOUT         Connection timeout in ms (default: 5000)
 *   QUEUE_PRINTER_LINE_CHAR       Character used when drawing horizontal lines (default: '=')
 *   QUEUE_PRINTER_MAX_BODY        Maximum accepted JSON payload in bytes (default: 1_048_576)
 *   QUEUE_PRINTER_MAX_COPIES      Maximum copies per request (default: 5)
 *   QUEUE_PRINTER_DEFAULT_COPIES  Default copy count when not provided (default: 1)
 *   QUEUE_PRINTER_CUT_TYPE        Default cut type: full or partial (default: partial)
 *   QUEUE_PRINTER_TRAILING_FEED   Default trailing feed lines (default: 4)
 *   QUEUE_PRINTER_QR_SIZE         Default QR module size (1-8, default: 4)
 *   QUEUE_PRINTER_QR_MODEL        Default QR model (1,2,3; default: 2)
 *   QUEUE_PRINTER_QR_CORRECTION   Default QR correction level (L,M,Q,H; default: M)
 *   QUEUE_PRINTER_ALLOWED_ORIGIN  CORS Access-Control-Allow-Origin header (default: *)
 *   QUEUE_PRINTER_DRIVER          Optional printer driver module name for non-network printers
 */

const http = require('http');
const { URL } = require('url');
const net = require('net');
const EscPosEncoder = require('esc-pos-encoder');

const HOST = process.env.QUEUE_PRINTER_HOST || '0.0.0.0';
const PORT = Number.parseInt(process.env.QUEUE_PRINTER_PORT || '18080', 10);
const DEFAULT_INTERFACE = (process.env.QUEUE_PRINTER_INTERFACE || '').trim() || null;
const DEFAULT_TYPE = (process.env.QUEUE_PRINTER_TYPE || 'epson').toLowerCase();
const DEFAULT_CODEPAGE = (process.env.QUEUE_PRINTER_CHARSET || 'thai11').trim() || 'thai11';
const DEFAULT_TIMEOUT = Number.parseInt(process.env.QUEUE_PRINTER_TIMEOUT || '5000', 10);
const DEFAULT_LINE_CHARACTER = (process.env.QUEUE_PRINTER_LINE_CHAR || '=').substring(0, 1) || '=';
const MAX_BODY_SIZE = Number.parseInt(process.env.QUEUE_PRINTER_MAX_BODY || `${1024 * 1024}`, 10);
const MAX_COPIES = Math.max(1, Number.parseInt(process.env.QUEUE_PRINTER_MAX_COPIES || '5', 10));
const DEFAULT_COPIES = clampInt(process.env.QUEUE_PRINTER_DEFAULT_COPIES, 1, MAX_COPIES, 1);
const DEFAULT_CUT_TYPE = normalizeCutType(process.env.QUEUE_PRINTER_CUT_TYPE);
const DEFAULT_TRAILING_FEED = clampInt(process.env.QUEUE_PRINTER_TRAILING_FEED, 0, 12, 4);
const DEFAULT_QR_SIZE = clampInt(process.env.QUEUE_PRINTER_QR_SIZE, 1, 8, 4);
const DEFAULT_QR_MODEL = clampInt(process.env.QUEUE_PRINTER_QR_MODEL, 1, 3, 2);
const DEFAULT_QR_CORRECTION = normalizeQrCorrection(process.env.QUEUE_PRINTER_QR_CORRECTION);
const ALLOWED_ORIGIN = process.env.QUEUE_PRINTER_ALLOWED_ORIGIN || '*';
const DRIVER_MODULE = (process.env.QUEUE_PRINTER_DRIVER || '').trim() || null;
const TICKET_COLUMNS = clampInt(process.env.QUEUE_PRINTER_COLUMNS, 24, 64, DEFAULT_TYPE === 'star' ? 42 : 48);

let optionalDriver = null;
if (DRIVER_MODULE) {
  try {
    optionalDriver = require(DRIVER_MODULE);
  } catch (error) {
    console.warn(`⚠️  Unable to load printer driver module "${DRIVER_MODULE}": ${error.message}`);
  }
}

const server = http.createServer(async (req, res) => {
  setCorsHeaders(res);

  // Quick handling for CORS pre-flight requests
  if (req.method === 'OPTIONS') {
    res.writeHead(204, 'No Content');
    res.end();
    return;
  }

  const requestUrl = safeParseUrl(req.url, req.headers.host);
  if (!requestUrl) {
    return sendJson(res, 400, { success: false, message: 'Invalid request URL' });
  }

  try {
    if (req.method === 'GET' && requestUrl.pathname === '/health') {
      return sendJson(res, 200, {
        success: true,
        service: 'queue-printer',
        message: 'Printer service is running',
        timestamp: new Date().toISOString(),
      });
    }

    if (req.method === 'POST' && requestUrl.pathname === '/commands/print') {
      const payload = await readJsonBody(req);
      const result = await handleRawPrint(payload);
      return sendJson(res, 200, { success: true, mode: 'raw', ...result });
    }

    if (req.method === 'POST' && requestUrl.pathname === '/print-ticket') {
      const payload = await readJsonBody(req);
      const result = await handleStructuredPrint(payload);
      return sendJson(res, 200, { success: true, mode: 'ticket', ...result });
    }

    return sendJson(res, 404, { success: false, message: 'Endpoint not found' });
  } catch (error) {
    const statusCode = error.statusCode && Number.isInteger(error.statusCode) ? error.statusCode : 500;
    console.error('Printer service error:', error.message);
    if (error.stack) {
      console.debug(error.stack);
    }
    return sendJson(res, statusCode, { success: false, message: error.message || 'Unexpected printer error' });
  }
});

server.listen(PORT, HOST, () => {
  console.log(`✅ Queue printer service listening on http://${HOST}:${PORT}`);
  if (DEFAULT_INTERFACE) {
    console.log(`→ Default printer interface: ${DEFAULT_INTERFACE}`);
  } else {
    console.log('→ No default printer interface configured. Requests must provide target details.');
  }
});

// ---------------------------------------------------------------------------
// Request handlers
// ---------------------------------------------------------------------------

async function handleRawPrint(payload = {}) {
  if (typeof payload !== 'object' || payload === null) {
    throw createHttpError(400, 'Invalid JSON payload');
  }

  if (!payload.data) {
    throw createHttpError(400, 'Missing "data" field in payload');
  }

  const interfaceUri = resolveInterface(payload);
  if (!interfaceUri) {
    throw createHttpError(400, 'Printer interface is not configured. Set QUEUE_PRINTER_INTERFACE or include target information.');
  }

  const copies = clampInt(payload.copies ?? payload.copy ?? payload.printCopies, 1, MAX_COPIES, DEFAULT_COPIES);
  const buffer = decodePayloadData(payload.data, payload.dataFormat);

  const target = parsePrinterInterface(interfaceUri);
  await ensurePrinterReachable(target);

  let totalBytes = 0;
  for (let i = 0; i < copies; i += 1) {
    totalBytes += await sendBufferToPrinter(target, buffer);
  }

  return {
    copiesPrinted: copies,
    interface: interfaceUri,
    bytesSent: totalBytes,
  };
}

async function handleStructuredPrint(payload = {}) {
  const ticket = extractTicket(payload);
  if (!ticket) {
    throw createHttpError(400, 'Missing ticket data. Provide a "ticket" object with queue details.');
  }

  if (!ticket.queueNumber) {
    throw createHttpError(400, 'ticket.queueNumber is required');
  }

  const interfaceUri = resolveInterface({
    interface: payload.interface ?? payload.printerInterface,
    target: payload.target ?? payload.printerTarget,
    port: payload.port ?? payload.printerPort,
  });
  if (!interfaceUri) {
    throw createHttpError(400, 'Printer interface is not configured.');
  }

  const copies = clampInt(payload.copies ?? ticket.copies, 1, MAX_COPIES, DEFAULT_COPIES);
  const qrOptions = {
    size: clampInt(payload.qrSize ?? payload.qrModuleSize ?? ticket.qrSize, 1, 8, DEFAULT_QR_SIZE),
    model: clampInt(payload.qrModel ?? ticket.qrModel, 1, 3, DEFAULT_QR_MODEL),
    correction: normalizeQrCorrection(payload.qrCorrection ?? payload.qrErrorLevel ?? ticket.qrCorrection),
  };
  const trailingFeed = clampInt(payload.trailingFeed ?? ticket.trailingFeed, 0, 12, DEFAULT_TRAILING_FEED);
  const cutType = normalizeCutType(payload.cutType ?? ticket.cutType) || DEFAULT_CUT_TYPE;

  const target = parsePrinterInterface(interfaceUri);
  await ensurePrinterReachable(target);

  const job = buildTicketBuffer(ticket, {
    qr: qrOptions,
    trailingFeed,
    cutType,
  });

  let totalBytes = 0;
  for (let i = 0; i < copies; i += 1) {
    totalBytes += await sendBufferToPrinter(target, job);
  }

  return {
    copiesPrinted: copies,
    interface: interfaceUri,
    bytesSent: totalBytes,
    ticket: { queueNumber: ticket.queueNumber, serviceType: ticket.serviceType, hospitalName: ticket.hospitalName },
  };
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function buildTicketBuffer(ticket, { qr, trailingFeed, cutType }) {
  const hospitalName = sanitizeLine(ticket.hospitalName);
  const queueLabel = sanitizeLine(ticket.label || ticket.title || 'บัตรคิว');
  const queueNumber = sanitizeLine(ticket.queueNumber);
  const serviceType = sanitizeLine(ticket.serviceType);
  const servicePoint = sanitizeLine(ticket.servicePoint || ticket.counterName);
  const issuedAt = sanitizeLine(ticket.issuedAt || ticket.datetime || ticket.createdAt);
  const waitingCount = typeof ticket.waitingCount === 'number' ? ticket.waitingCount : ticket.waiting;
  const additionalNote = sanitizeLine(ticket.additionalNote || ticket.note);
  const footer = sanitizeLine(ticket.footer || ticket.footerNote);
  const qrData = typeof ticket.qrData === 'string' && ticket.qrData.trim() ? ticket.qrData.trim() : null;

  const encoder = new EscPosEncoder({ columns: TICKET_COLUMNS });

  encoder.initialize();

  if (DEFAULT_CODEPAGE) {
    encoder.codepage(DEFAULT_CODEPAGE);
  }

  encoder.align('center');

  if (hospitalName) {
    encoder.bold(true).line(hospitalName).bold(false);
  }

  if (queueLabel) {
    encoder.line(queueLabel);
  }

  encoder.line(DEFAULT_LINE_CHARACTER.repeat(TICKET_COLUMNS));

  if (serviceType) {
    encoder.line(serviceType);
  }

  if (queueNumber) {
    encoder.newline();
    encoder.bold(true).size(2, 2).line(queueNumber).size(1, 1).bold(false);
    encoder.newline();
  }

  if (servicePoint) {
    encoder.line(servicePoint);
  }

  encoder.align('left');

  if (issuedAt) {
    encoder.line(`ออกบัตร: ${issuedAt}`);
  }

  if (Number.isFinite(waitingCount)) {
    encoder.line(`จำนวนคิวก่อนหน้า: ${waitingCount}`);
  }

  encoder.align('center');

  if (additionalNote) {
    encoder.newline();
    encoder.line(additionalNote);
  }

  if (qrData) {
    encoder.newline();
    encoder.qrcode(qrData, {
      model: qr.model,
      size: qr.size,
      errorlevel: (qr.correction || 'M').toLowerCase(),
    });
    encoder.newline();
  }

  if (footer) {
    encoder.line(footer);
  }

  const feedLines = clampInt(trailingFeed, 0, 12, DEFAULT_TRAILING_FEED);
  for (let i = 0; i < feedLines; i += 1) {
    encoder.newline();
  }

  encoder.cut(cutType === 'full' ? 'full' : 'partial');

  return Buffer.from(encoder.encode());
}

function extractTicket(payload) {
  if (payload.ticket && typeof payload.ticket === 'object') {
    return payload.ticket;
  }

  const keys = ['queueNumber', 'hospitalName', 'serviceType', 'servicePoint', 'issuedAt', 'waitingCount', 'additionalNote', 'footer', 'qrData'];
  const hasAny = keys.some((key) => key in payload);
  if (hasAny) {
    return payload;
  }
  return null;
}

function parsePrinterInterface(interfaceUri) {
  if (!interfaceUri || typeof interfaceUri !== 'string') {
    throw createHttpError(400, 'Printer interface is required');
  }

  const trimmed = interfaceUri.trim();

  if (trimmed.toLowerCase().startsWith('tcp://')) {
    try {
      const url = new URL(trimmed);
      const port = clampInt(url.port, 1, 65535, 9100);
      return {
        type: 'tcp',
        uri: trimmed,
        host: url.hostname,
        port,
      };
    } catch (error) {
      throw createHttpError(400, `Invalid TCP printer interface: ${interfaceUri}`);
    }
  }

  if (trimmed.startsWith('printer:') || trimmed.startsWith('\\\\.\\')) {
    return {
      type: 'driver',
      uri: trimmed,
      name: trimmed.startsWith('printer:') ? trimmed.slice('printer:'.length) : trimmed,
    };
  }

  throw createHttpError(400, `Unsupported printer interface "${interfaceUri}"`);
}

async function ensurePrinterReachable(target) {
  if (target.type === 'tcp') {
    await sendTcpBuffer(target.host, target.port, Buffer.alloc(0), { probe: true });
    return;
  }

  if (target.type === 'driver') {
    if (!optionalDriver) {
      throw createHttpError(500, `Printer driver is required for interface "${target.uri}"`);
    }

    if (typeof optionalDriver.isReady === 'function') {
      const ready = await optionalDriver.isReady(target.name);
      if (ready === false) {
        throw createHttpError(503, `Printer "${target.uri}" is not reachable`);
      }
    }

    return;
  }
}

async function sendBufferToPrinter(target, buffer) {
  if (!Buffer.isBuffer(buffer)) {
    throw createHttpError(500, 'Print payload must be a buffer');
  }

  if (target.type === 'tcp') {
    return sendTcpBuffer(target.host, target.port, buffer);
  }

  if (target.type === 'driver') {
    if (!optionalDriver) {
      throw createHttpError(500, `Printer driver is required for interface "${target.uri}"`);
    }

    const handler = optionalDriver.print || optionalDriver.write || optionalDriver.send;
    if (typeof handler !== 'function') {
      throw createHttpError(500, 'Printer driver module must expose a print(), write(), or send() function');
    }

    const maybePromise = handler({
      interface: target.uri,
      name: target.name,
      data: buffer,
    });

    if (maybePromise && typeof maybePromise.then === 'function') {
      await maybePromise;
    }

    return buffer.length;
  }

  throw createHttpError(500, 'Unsupported printer transport');
}

function sendTcpBuffer(host, port, buffer, { probe = false } = {}) {
  return new Promise((resolve, reject) => {
    const socket = new net.Socket();
    let finished = false;

    const cleanup = () => {
      socket.removeAllListeners();
    };

    const finish = (error, bytes = 0) => {
      if (finished) {
        return;
      }
      finished = true;
      cleanup();
      socket.destroy();
      if (error) {
        reject(error);
      } else {
        resolve(bytes);
      }
    };

    const timeout = Number.isFinite(DEFAULT_TIMEOUT) ? DEFAULT_TIMEOUT : 5000;
    socket.setTimeout(timeout, () => {
      finish(createHttpError(504, `Printer ${host}:${port} timed out`));
    });

    socket.once('error', (error) => {
      finish(createHttpError(503, `Printer ${host}:${port} error: ${error.message}`));
    });

    socket.once('close', () => {
      if (!finished) {
        finish(null, probe ? 0 : buffer.length);
      }
    });

    socket.connect(port, host, () => {
      if (probe || buffer.length === 0) {
        finish(null, 0);
        return;
      }

      socket.write(buffer, (error) => {
        if (error) {
          finish(createHttpError(503, `Printer ${host}:${port} write failed: ${error.message}`));
          return;
        }
        socket.end();
      });
    });
  });
}

function decodePayloadData(data, format = 'base64') {
  if (typeof data !== 'string' || !data.trim()) {
    throw createHttpError(400, 'Payload data must be a non-empty string');
  }

  const normalized = format ? format.toString().toLowerCase() : 'base64';

  switch (normalized) {
    case 'base64':
      return Buffer.from(data, 'base64');
    case 'hex':
      return Buffer.from(data, 'hex');
    case 'ascii':
    case 'text':
      return Buffer.from(data, 'ascii');
    default:
      throw createHttpError(400, `Unsupported data format "${format}"`);
  }
}

function resolveInterface(payload = {}) {
  if (payload.interfaceUri && typeof payload.interfaceUri === 'string') {
    const trimmed = payload.interfaceUri.trim();
    if (trimmed) {
      return trimmed;
    }
  }

  if (DEFAULT_INTERFACE) {
    return DEFAULT_INTERFACE;
  }

  const interfaceValue = typeof payload.interface === 'string' ? payload.interface.trim() : '';
  const interfaceType = interfaceValue.toLowerCase();

  if (
    interfaceValue &&
    (interfaceType.includes('://') || interfaceType.startsWith('printer:') || interfaceType.startsWith('\\\\.\\'))
  ) {
    return interfaceValue;
  }

  const target = typeof payload.target === 'string' ? payload.target.trim() : '';
  const port = clampInt(payload.port, 1, 65535, null);

  if (target.startsWith('tcp://') || target.startsWith('printer:') || target.startsWith('\\\\.\\')) {
    return target;
  }

  if (!target) {
    return null;
  }

  if (interfaceType === 'printer' || interfaceType === 'system' || interfaceType === 'windows') {
    return `printer:${target}`;
  }

  const resolvedPort = port ?? 9100;
  return `tcp://${target}${resolvedPort ? `:${resolvedPort}` : ''}`;
}

function normalizeCutType(value) {
  if (typeof value !== 'string') {
    return 'partial';
  }
  const cut = value.trim().toLowerCase();
  return cut === 'full' ? 'full' : 'partial';
}

function normalizeQrCorrection(value) {
  const defaultLevel = 'M';
  if (typeof value !== 'string') {
    return defaultLevel;
  }
  const normalized = value.trim().toUpperCase();
  return ['L', 'M', 'Q', 'H'].includes(normalized) ? normalized : defaultLevel;
}

function clampInt(value, min, max, fallback) {
  const num = Number.parseInt(value, 10);
  if (Number.isFinite(num)) {
    const clamped = Math.min(max, Math.max(min, num));
    return clamped;
  }
  return fallback;
}

function sanitizeLine(value) {
  if (typeof value !== 'string') {
    return '';
  }
  return value.replace(/\s+/g, ' ').trim();
}

function createHttpError(statusCode, message) {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
}

function setCorsHeaders(res) {
  res.setHeader('Access-Control-Allow-Origin', ALLOWED_ORIGIN);
  res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization');
}

function sendJson(res, statusCode, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
  });
  res.end(body);
}

function safeParseUrl(path, hostHeader) {
  try {
    return new URL(path, `http://${hostHeader || 'localhost'}`);
  } catch (error) {
    return null;
  }
}

function readJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = [];
    let totalLength = 0;

    req.on('data', (chunk) => {
      totalLength += chunk.length;
      if (totalLength > MAX_BODY_SIZE) {
        reject(createHttpError(413, 'Request payload is too large'));
        req.destroy();
        return;
      }
      body.push(chunk);
    });

    req.on('end', () => {
      try {
        const raw = Buffer.concat(body).toString('utf8');
        if (!raw) {
          resolve({});
          return;
        }
        const json = JSON.parse(raw);
        resolve(json);
      } catch (error) {
        reject(createHttpError(400, 'Invalid JSON payload'));
      }
    });

    req.on('error', (error) => {
      reject(createHttpError(500, error.message || 'Stream error'));
    });
  });
}

