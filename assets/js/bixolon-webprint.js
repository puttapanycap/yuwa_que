(function(window) {
    'use strict';

    function chunkToBase64(uint8Array) {
        if (!(uint8Array instanceof Uint8Array)) {
            return '';
        }

        let result = '';
        const chunkSize = 0x8000;
        for (let i = 0; i < uint8Array.length; i += chunkSize) {
            const chunk = uint8Array.subarray(i, i + chunkSize);
            result += String.fromCharCode.apply(null, chunk);
        }
        return btoa(result);
    }

    function clamp(value, min, max, fallback) {
        const number = Number(value);
        if (Number.isFinite(number)) {
            return Math.min(max, Math.max(min, number));
        }
        return fallback;
    }

    function resolveEncoder() {
        if (typeof window.EscPosEncoder === 'function') {
            return window.EscPosEncoder;
        }
        if (window.escpos && typeof window.escpos.EscPosEncoder === 'function') {
            return window.escpos.EscPosEncoder;
        }
        return null;
    }

    function normalizeCutType(value) {
        return value === 'full' ? 'full' : 'partial';
    }

    function normalizeErrorLevel(value) {
        const level = (value || 'm').toString().toLowerCase();
        return ['l', 'm', 'q', 'h'].includes(level) ? level : 'm';
    }

    function mergeUint8Arrays(chunks) {
        if (!Array.isArray(chunks) || !chunks.length) {
            return new Uint8Array(0);
        }

        const totalLength = chunks.reduce((sum, chunk) => sum + (chunk?.length || 0), 0);
        const result = new Uint8Array(totalLength);
        let offset = 0;
        chunks.forEach((chunk) => {
            if (chunk instanceof Uint8Array) {
                result.set(chunk, offset);
                offset += chunk.length;
            }
        });
        return result;
    }

    function buildTicketData(ticket, copies, options) {
        const EscPosEncoder = resolveEncoder();
        if (!EscPosEncoder) {
            throw new Error('EscPosEncoder library is not loaded.');
        }

        const encoderOptions = {};
        const encodedCopies = [];
        const copyCount = Math.max(1, parseInt(copies, 10) || 1);

        for (let i = 0; i < copyCount; i += 1) {
            const encoder = new EscPosEncoder(encoderOptions);
            encoder.initialize().align('center');

            if (ticket.hospitalName) {
                encoder.bold(true).line(ticket.hospitalName.toUpperCase());
                encoder.bold(false);
            }

            if (ticket.serviceType) {
                encoder.line(ticket.serviceType);
            }

            if (ticket.queueNumber) {
                encoder.size(2, 2).line(ticket.queueNumber);
                encoder.size(1, 1);
            }

            if (ticket.servicePoint) {
                encoder.line(ticket.servicePoint);
            }

            if (ticket.datetime) {
                encoder.line(ticket.datetime);
            }

            if (ticket.additionalNote) {
                encoder.line(ticket.additionalNote);
            }

            if (ticket.qrData) {
                encoder.newline();
                try {
                    encoder.qrcode(ticket.qrData, {
                        model: options.qrModel,
                        size: options.qrSize,
                        errorlevel: options.qrErrorLevel
                    });
                    encoder.newline();
                } catch (error) {
                    console.warn('Unable to encode QR code for ticket', error);
                    encoder.line(ticket.qrData);
                    encoder.newline();
                }
            }

            if (ticket.footer) {
                encoder.line(ticket.footer);
            }

            for (let feed = 0; feed < options.trailingFeed; feed += 1) {
                encoder.newline();
            }

            encoder.cut(options.cutType);
            encodedCopies.push(encoder.encode());
        }

        return chunkToBase64(mergeUint8Arrays(encodedCopies));
    }

    class BixolonWebPrintClient {
        constructor(config = {}) {
            this.enabled = config.enabled !== false;
            this.serviceUrl = (config.serviceUrl || 'http://127.0.0.1:18080').replace(/\/$/, '');
            this.servicePath = config.servicePath || '/commands/print';
            this.interfaceType = config.interfaceType || 'network';
            this.printerTarget = config.printerTarget || '';
            this.printerPort = config.printerPort ? parseInt(config.printerPort, 10) : 9100;
            this.printerModel = config.printerModel || '';
            this.qrModuleSize = clamp(config.qrModuleSize, 1, 8, 6);
            this.qrModel = clamp(config.qrModel, 1, 2, 2);
            this.qrErrorLevel = normalizeErrorLevel(config.qrErrorLevel);
            this.cutType = normalizeCutType(config.cutType);
            this.timeout = config.timeout ? parseInt(config.timeout, 10) : 4000;
            this.trailingFeed = clamp(config.trailingFeed, 0, 12, 6);
        }

        isReady() {
            return this.enabled && !!this.printerTarget;
        }

        buildTicketPayload(ticket, copies = 1) {
            return buildTicketData(ticket, copies, {
                qrSize: this.qrModuleSize,
                qrModel: this.qrModel,
                qrErrorLevel: this.qrErrorLevel,
                cutType: this.cutType,
                trailingFeed: this.trailingFeed
            });
        }

        async printTicket(ticket, copies = 1) {
            if (!this.isReady()) {
                throw new Error('BIXOLON printer is not configured.');
            }

            const sanitizedCopies = Math.max(1, parseInt(copies, 10) || 1);
            const payload = {
                requestId: `queue-${Date.now()}`,
                interface: this.interfaceType,
                target: this.printerTarget,
                port: this.printerPort,
                model: this.printerModel,
                copies: sanitizedCopies,
                dataFormat: 'base64',
                data: this.buildTicketPayload(ticket, sanitizedCopies)
            };

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), this.timeout);

            try {
                const response = await fetch(`${this.serviceUrl}${this.servicePath}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });
                clearTimeout(timer);

                if (!response.ok) {
                    const message = await response.text().catch(() => response.statusText);
                    throw new Error(message || `Service responded with status ${response.status}`);
                }

                const result = await response.json().catch(() => ({}));
                return result;
            } catch (error) {
                clearTimeout(timer);
                throw error;
            }
        }
    }

    window.BixolonWebPrint = {
        BixolonWebPrintClient
    };
})(window);
