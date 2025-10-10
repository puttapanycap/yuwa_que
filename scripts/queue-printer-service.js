#!/usr/bin/env node
/**
 * Queue Ticket Thermal Printer Service
 * ------------------------------------
 *
 * Lightweight HTTP service that accepts queue ticket print requests and
 * forwards them to an ESC/POS compatible thermal printer using
 * the `escpos` package with its network adapter for transport.
 *
 * The service exposes two endpoints:
 *   - POST /commands/print
 *       Accepts raw ESC/POS payloads (base64/hex/ascii) that mirror the
 *       request format used by the BIXOLON Web Print service.
 *   - POST /print-ticket
 *       Accepts structured JSON describing a queue ticket and renders it
 *       with helper utilities powered by escpos commands.
 *
 * Environment variables:
 *   QUEUE_PRINTER_HOST            Host to bind the HTTP server (default: 0.0.0.0)
 *   QUEUE_PRINTER_PORT            Port to bind the HTTP server (default: 18080)
 *   QUEUE_PRINTER_INTERFACE       Printer interface URI (e.g. tcp://192.168.0.50:9100)
 *   QUEUE_PRINTER_TYPE            Printer type: epson, star, tanca, daruma, brother, custom (default: epson)
 *   QUEUE_PRINTER_CHARSET         ESC/POS code page (default: thai11)
 *   QUEUE_PRINTER_CODE_TABLE      Override ESC/POS code table number (ESC t n) when needed
 *   QUEUE_PRINTER_TIMEOUT         Connection timeout in ms (default: 5000)
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
const escpos = require('escpos');

let NetworkAdapter = escpos.Network;
try {
  if (!NetworkAdapter) {
    NetworkAdapter = require('escpos-network');
    escpos.Network = NetworkAdapter;
  }
} catch (error) {
  console.warn(`⚠️  Unable to load escpos network adapter: ${error.message}`);
  NetworkAdapter = null;
}

const HOST = process.env.QUEUE_PRINTER_HOST || '0.0.0.0';
const PORT = Number.parseInt(process.env.QUEUE_PRINTER_PORT || '18080', 10);
const DEFAULT_INTERFACE = (process.env.QUEUE_PRINTER_INTERFACE || '').trim() || null;
const DEFAULT_TYPE = (process.env.QUEUE_PRINTER_TYPE || 'epson').toLowerCase();
const DEFAULT_CODEPAGE = (process.env.QUEUE_PRINTER_CHARSET || 'thai11').trim() || 'thai11';
const DEFAULT_ENCODING = resolveEncoding(DEFAULT_CODEPAGE);
const CODE_TABLE_MAPPINGS = {
  default: { tis620: 21, thaiAlt: 26, cp874: 21 },
  epson: { tis620: 21, thaiAlt: 26, cp874: 21 },
  custom: { tis620: 21, thaiAlt: 26, cp874: 21 },
  tanca: { tis620: 21, thaiAlt: 26, cp874: 21 },
  daruma: { tis620: 21, thaiAlt: 26, cp874: 21 },
  brother: { tis620: 21, thaiAlt: 26, cp874: 21 },
  star: { tis620: 21, thaiAlt: 21, cp874: 21 },
};
const EXPLICIT_CODE_TABLE = parseCodeTable(process.env.QUEUE_PRINTER_CODE_TABLE);
const DEFAULT_CODE_TABLE = resolveCodeTable(DEFAULT_ENCODING, DEFAULT_TYPE, EXPLICIT_CODE_TABLE);
const DEFAULT_TIMEOUT = Number.parseInt(process.env.QUEUE_PRINTER_TIMEOUT || '5000', 10);
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
    totalBytes += await printRawBuffer(target, buffer);
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

  const job = await buildTicketBuffer(ticket, {
    qr: qrOptions,
    trailingFeed,
    cutType,
  });

  let totalBytes = 0;
  for (let i = 0; i < copies; i += 1) {
    totalBytes += await printRawBuffer(target, job);
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

async function buildTicketBuffer(ticket, { qr, trailingFeed, cutType }) {
  const adapter = new MemoryAdapter();
  const printer = createEscposPrinter(adapter);

  adapter.open(() => {});

  applyTicketLayout(printer, ticket, { qr, trailingFeed, cutType });

  return new Promise((resolve, reject) => {
    try {
      printer.close((error) => {
        if (error) {
          reject(createHttpError(500, error.message || 'Failed to finalize ticket job'));
          return;
        }
        resolve(adapter.getBuffer());
      });
    } catch (error) {
      reject(createHttpError(500, error.message || 'Failed to build ticket data'));
    }
  });
}

function applyTicketLayout(printer, ticket, { qr, trailingFeed, cutType }) {
  const hospitalName = sanitizeLine(ticket.hospitalName);
  const queueLabel = sanitizeLine(ticket.label || ticket.title);
  const queueNumber = sanitizeLine(ticket.queueNumber);
  const serviceType = sanitizeLine(ticket.serviceType || ticket.queueType);
  const servicePoint = sanitizeLine(ticket.servicePoint || ticket.counterName);
  const issuedAt = sanitizeLine(ticket.issuedAt || ticket.datetime || ticket.createdAt);
  const waitingCount = typeof ticket.waitingCount === 'number' ? ticket.waitingCount : ticket.waiting;
  const additionalNote = sanitizeLine(ticket.additionalNote || ticket.note);
  const footer = sanitizeLine(ticket.footer || ticket.footerNote);
  const qrData = typeof ticket.qrData === 'string' && ticket.qrData.trim() ? ticket.qrData.trim() : null;

  printer
    .encode(DEFAULT_ENCODING)
    .font('A')
    .style('NORMAL')
    .size(1, 1)
    .align('ct');

  if (Number.isFinite(DEFAULT_CODE_TABLE)) {
    printer.setCharacterCodeTable(DEFAULT_CODE_TABLE);
  }

  if (hospitalName) {
    printer.style('B').text(hospitalName.toUpperCase()).style('NORMAL');
  }

  if (queueLabel) {
    printer.text(queueLabel);
  }

  if (serviceType) {
    printer.text(serviceType);
  }

  if (queueNumber) {
    printer.style('B').size(2, 2).text(queueNumber).size(1, 1).style('NORMAL');
  }

  if (servicePoint) {
    printer.text(servicePoint);
  }

  if (issuedAt) {
    printer.text(issuedAt);
  }

  if (Number.isFinite(waitingCount)) {
    printer.text(`รอคิวก่อนหน้า ${waitingCount}`);
  }

  if (additionalNote) {
    printer.text(additionalNote);
  }

  if (qrData) {
    const qrSize = clampInt(qr.size, 1, 8, DEFAULT_QR_SIZE);
    const qrVersion = clampInt(qr.model, 1, 40, DEFAULT_QR_MODEL);
    const qrLevel = normalizeQrCorrection(qr.correction);

    printer.newLine();
    printer.qrcode(qrData, qrVersion, qrLevel, qrSize);
    printer.newLine();
  }

  if (footer) {
    printer.text(footer);
  }

  const feedLines = clampInt(trailingFeed, 0, 12, DEFAULT_TRAILING_FEED);
  if (feedLines > 0) {
    printer.feed(feedLines);
  }
  printer.cut(cutType === 'full' ? false : true);

  return printer;
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
    if (!NetworkAdapter) {
      throw createHttpError(
        500,
        'escpos network adapter is not available. Install the "escpos-network" package to enable TCP printing.',
      );
    }
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

async function printRawBuffer(target, buffer) {
  if (!Buffer.isBuffer(buffer)) {
    throw createHttpError(500, 'Print payload must be a buffer');
  }

  if (target.type === 'tcp') {
    return withEscposPrinter(target, (printer) => {
      printer.raw(buffer);
    });
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

function withEscposPrinter(target, job) {
  if (!NetworkAdapter) {
    throw createHttpError(
      500,
      'escpos network adapter is not available. Install the "escpos-network" package to enable TCP printing.',
    );
  }

  return new Promise((resolve, reject) => {
    const device = new NetworkAdapter(target.host, target.port);
    const printer = createEscposPrinter(device);
    const socket = device.device;
    let totalBytes = 0;

    if (device && typeof device.write === 'function') {
      const originalWrite = device.write.bind(device);
      device.write = (data, callback) => {
        if (Buffer.isBuffer(data)) {
          totalBytes += data.length;
        }
        return originalWrite(data, callback);
      };
    }

    let timeoutListener = null;
    if (socket && typeof socket.setTimeout === 'function' && Number.isFinite(DEFAULT_TIMEOUT) && DEFAULT_TIMEOUT > 0) {
      timeoutListener = () => {
        const timeoutError = new Error(`Printer ${target.host}:${target.port} timed out after ${DEFAULT_TIMEOUT}ms`);
        timeoutError.code = 'ETIMEDOUT';
        socket.destroy(timeoutError);
      };
      socket.setTimeout(DEFAULT_TIMEOUT);
      socket.on('timeout', timeoutListener);
    }

    device.open((error) => {
      if (timeoutListener && socket) {
        socket.setTimeout(0);
        socket.removeListener('timeout', timeoutListener);
      }

      if (error) {
        const status = error.code === 'ETIMEDOUT' ? 504 : 503;
        reject(createHttpError(status, `Unable to connect to printer ${target.host}:${target.port}: ${error.message}`));
        return;
      }

      const run = async () => {
        let jobError = null;
        try {
          await job(printer);
        } catch (err) {
          jobError = err;
        }

        try {
          await closePrinter(printer);
        } catch (closeError) {
          if (!jobError) {
            throw closeError;
          }
          console.warn(`⚠️  Failed to close printer after job error: ${closeError.message}`);
        }

        if (jobError) {
          throw jobError;
        }
      };

      run()
        .then(() => {
          resolve(totalBytes);
        })
        .catch((err) => {
          const statusCode = err && err.code === 'ETIMEDOUT' ? 504 : 500;
          if (err && err.statusCode) {
            reject(err);
            return;
          }
          reject(createHttpError(statusCode, err && err.message ? err.message : 'Printer job failed'));
        });
    });
  });
}

function closePrinter(printer) {
  return new Promise((resolve, reject) => {
    try {
      printer.close((error) => {
        if (error) {
          reject(error);
        } else {
          resolve();
        }
      });
    } catch (error) {
      reject(error);
    }
  });
}

function createEscposPrinter(adapter) {
  const printer = new escpos.Printer(adapter, { encoding: DEFAULT_ENCODING, width: TICKET_COLUMNS });
  if (DEFAULT_ENCODING && typeof printer.encode === 'function') {
    printer.encode(DEFAULT_ENCODING);
  }
  if (Number.isFinite(DEFAULT_CODE_TABLE) && typeof printer.setCharacterCodeTable === 'function') {
    printer.setCharacterCodeTable(DEFAULT_CODE_TABLE);
  }
  return printer;
}

class MemoryAdapter {
  constructor() {
    this.chunks = [];
  }

  open(callback) {
    if (typeof callback === 'function') {
      callback(null, this);
    }
    return this;
  }

  write(data, callback) {
    if (data) {
      if (Buffer.isBuffer(data)) {
        this.chunks.push(Buffer.from(data));
      } else if (typeof data === 'string') {
        this.chunks.push(Buffer.from(data, 'binary'));
      }
    }
    if (typeof callback === 'function') {
      callback(null);
    }
    return this;
  }

  close(callback) {
    if (typeof callback === 'function') {
      callback(null, this);
    }
    return this;
  }

  read() {
    return this;
  }

  getBuffer() {
    return this.chunks.length > 0 ? Buffer.concat(this.chunks) : Buffer.alloc(0);
  }
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

function resolveEncoding(codepage) {
  if (!codepage) {
    return 'tis620';
  }

  const normalized = codepage.toString().trim().toLowerCase();
  const map = {
    thai: 'tis620',
    thai11: 'tis620',
    'thai-11': 'tis620',
    tis620: 'tis620',
    'tis-620': 'tis620',
    tis620_1: 'tis620',
    cp874: 'cp874',
    'cp-874': 'cp874',
    windows874: 'cp874',
    'windows-874': 'cp874',
    utf8: 'utf8',
    'utf-8': 'utf8',
  };

  return map[normalized] || normalized || 'tis620';
}

function parseCodeTable(value) {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  const parsed = Number.parseInt(value, 10);
  if (Number.isFinite(parsed) && parsed >= 0 && parsed <= 255) {
    return parsed;
  }

  console.warn(`⚠️  Ignoring invalid QUEUE_PRINTER_CODE_TABLE value: ${value}`);
  return null;
}

function resolveCodeTable(encoding, printerType, overrideValue = null) {
  if (Number.isFinite(overrideValue)) {
    return overrideValue;
  }

  const normalizedType = typeof printerType === 'string' ? printerType.trim().toLowerCase() : '';
  const typeKey = normalizedType || 'default';

  const key = normalizeCodeTableKey(encoding);
  if (!key) {
    return null;
  }

  const lookup = CODE_TABLE_MAPPINGS[typeKey] || CODE_TABLE_MAPPINGS.default;
  return typeof lookup[key] === 'number' ? lookup[key] : null;
}

function normalizeCodeTableKey(encoding) {
  if (!encoding) {
    return null;
  }

  const normalized = encoding.toString().trim().toLowerCase();

  if (!normalized) {
    return null;
  }

  if (['thai18', 'tis18', 'tis-18', 'tis620-18', 'tis620_alt', 'thai-alt'].includes(normalized)) {
    return 'thaiAlt';
  }

  if (['cp874', 'cp-874', 'windows874', 'windows-874'].includes(normalized)) {
    return 'cp874';
  }

  if (
    [
      'thai',
      'thai11',
      'thai-11',
      'tis620',
      'tis-620',
      'tis620_1',
      'tis6201',
      'tis11',
      'tis-11',
    ].includes(normalized)
  ) {
    return 'tis620';
  }

  return normalized;
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

