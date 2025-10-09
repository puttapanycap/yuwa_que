(function(window) {
    'use strict';

    function chunkToBinaryString(uint8Array) {
        let result = '';
        const chunkSize = 0x8000;
        for (let i = 0; i < uint8Array.length; i += chunkSize) {
            const chunk = uint8Array.subarray(i, i + chunkSize);
            result += String.fromCharCode.apply(null, chunk);
        }
        return result;
    }

    class EscPosBuilder {
        constructor(options = {}) {
            this.options = Object.assign({
                qrModuleSize: 6,
                qrErrorCorrection: 'M',
                cutType: 'partial',
                encoding: 'utf-8'
            }, options);
            this.commands = [];
            this.textEncoder = new TextEncoder();
        }

        push(...bytes) {
            for (let i = 0; i < bytes.length; i += 1) {
                const value = bytes[i];
                if (Array.isArray(value)) {
                    this.push.apply(this, value);
                } else if (typeof value === 'number') {
                    this.commands.push(value & 0xFF);
                }
            }
        }

        initialize() {
            this.push(0x1B, 0x40);
        }

        align(mode = 'left') {
            const map = { left: 0, center: 1, right: 2 };
            this.push(0x1B, 0x61, map[mode] ?? 0);
        }

        bold(enable = false) {
            this.push(0x1B, 0x45, enable ? 1 : 0);
        }

        doubleSize(enable = false) {
            this.push(0x1D, 0x21, enable ? 0x11 : 0x00);
        }

        text(value = '') {
            if (!value) {
                return;
            }
            const encoded = this.textEncoder.encode(value);
            this.push(Array.from(encoded));
        }

        textLine(value = '', options = {}) {
            if (options.align) this.align(options.align);
            if (options.bold !== undefined) this.bold(options.bold);
            if (options.doubleSize !== undefined) this.doubleSize(options.doubleSize);
            if (options.uppercase) {
                this.text((value || '').toUpperCase());
            } else {
                this.text(value);
            }
            this.newLine();
            if (options.bold !== undefined) this.bold(false);
            if (options.doubleSize !== undefined) this.doubleSize(false);
            if (options.align) this.align('left');
        }

        newLine(count = 1) {
            for (let i = 0; i < count; i += 1) {
                this.push(0x0A);
            }
        }

        feed(lines = 1) {
            this.push(0x1B, 0x64, Math.max(1, Math.min(255, lines)));
        }

        drawSeparator(width = 32) {
            const line = '-'.repeat(Math.max(8, Math.min(64, width)));
            this.textLine(line, { align: 'center' });
        }

        qrCode(data, moduleSize = this.options.qrModuleSize, errorCorrection = this.options.qrErrorCorrection) {
            if (!data) return;
            const encoder = this.textEncoder;
            const dataBytes = encoder.encode(data);
            const size = Math.max(1, Math.min(16, moduleSize));
            const eccMap = { L: 48, M: 49, Q: 50, H: 51 };
            const ecc = eccMap[(errorCorrection || 'M').toUpperCase()] ?? eccMap.M;

            this.push(0x1D, 0x28, 0x6B, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00);
            this.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x43, size);
            this.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x45, ecc);

            const length = dataBytes.length + 3;
            const pL = length & 0xFF;
            const pH = (length >> 8) & 0xFF;
            const storeCommand = [0x1D, 0x28, 0x6B, pL, pH, 0x31, 0x50, 0x30];
            this.push(storeCommand.concat(Array.from(dataBytes)));
            this.push(0x1D, 0x28, 0x6B, 0x03, 0x00, 0x31, 0x51, 0x30);
        }

        cut(mode = this.options.cutType) {
            if (mode === 'partial') {
                this.push(0x1D, 0x56, 0x42, 0x00);
            } else {
                this.push(0x1D, 0x56, 0x00);
            }
        }

        appendTicket(ticket) {
            this.initialize();
            this.align('center');
            this.bold(true);
            if (ticket.hospitalName) {
                this.textLine(ticket.hospitalName, { uppercase: true });
            }
            this.bold(false);
            if (ticket.serviceType) {
                this.textLine(ticket.serviceType, { align: 'center' });
            }
            this.doubleSize(true);
            this.textLine(ticket.queueNumber || '', { align: 'center' });
            this.doubleSize(false);
            if (ticket.servicePoint) {
                this.textLine(ticket.servicePoint, { align: 'center' });
            }
            if (ticket.datetime) {
                this.textLine(ticket.datetime, { align: 'center' });
            }
            if (ticket.additionalNote) {
                this.textLine(ticket.additionalNote, { align: 'center' });
            }
            if (ticket.qrData) {
                this.newLine();
                this.align('center');
                this.qrCode(ticket.qrData);
                this.newLine();
            }
            if (ticket.footer) {
                this.textLine(ticket.footer, { align: 'center' });
            }
            this.feed(6);
            this.cut(this.options.cutType);
        }

        toUint8Array() {
            return Uint8Array.from(this.commands);
        }

        toBase64() {
            const bytes = this.toUint8Array();
            return btoa(chunkToBinaryString(bytes));
        }
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
            this.qrModuleSize = config.qrModuleSize ? parseInt(config.qrModuleSize, 10) : 6;
            this.cutType = config.cutType || 'partial';
            this.timeout = config.timeout ? parseInt(config.timeout, 10) : 4000;
        }

        isReady() {
            return this.enabled && !!this.printerTarget;
        }

        buildTicketPayload(ticket, copies = 1) {
            const builder = new EscPosBuilder({
                qrModuleSize: this.qrModuleSize,
                cutType: this.cutType
            });
            for (let i = 0; i < copies; i += 1) {
                builder.appendTicket(ticket);
            }
            return builder.toBase64();
        }

        async printTicket(ticket, copies = 1) {
            if (!this.isReady()) {
                throw new Error('BIXOLON printer is not configured.');
            }

            const payload = {
                requestId: `queue-${Date.now()}`,
                interface: this.interfaceType,
                target: this.printerTarget,
                port: this.printerPort,
                model: this.printerModel,
                copies: Math.max(1, parseInt(copies, 10) || 1),
                dataFormat: 'base64',
                data: this.buildTicketPayload(ticket, copies)
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
        EscPosBuilder,
        BixolonWebPrintClient
    };
})(window);
