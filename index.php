<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเรียกคิว - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>

    <meta name="theme-color" content="#690dfdff" />
    <link rel="manifest" href="./manifest.json" />
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .kiosk-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        
        .hospital-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #007bff;
        }
        
        .hospital-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        
        .service-type-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .service-type-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.2);
        }
        
        .service-type-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .btn-kiosk {
            font-size: 1.2rem;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .id-input {
            font-size: 1.5rem;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .queue-display {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }
        
        .queue-number {
            font-size: 4rem;
            font-weight: bold;
            color: #007bff;
            margin: 1rem 0;
        }
        
        .qr-code {
            margin: 1rem 0;
        }

        .ticket-preview {
            background: #ffffff;
            border: 1px dashed #ced4da;
            border-radius: 15px;
            padding: 1.75rem 1.5rem;
            text-align: center;
            color: #212529;
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.05);
            max-width: 280px;
            margin: 0 auto;
        }

        .ticket-preview-hospital {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .ticket-preview-label {
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #0d6efd;
            margin-top: 0.35rem;
        }

        .ticket-preview-service {
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 0.75rem;
        }

        .ticket-preview-queue {
            font-size: 3rem;
            font-weight: 800;
            color: #0d6efd;
            margin: 0.75rem 0 0.5rem;
        }

        .ticket-preview-service-point,
        .ticket-preview-issued-at,
        .ticket-preview-waiting {
            display: block;
            font-size: 1rem;
            color: #495057;
        }

        .ticket-preview-service-point {
            margin-top: 0.35rem;
        }

        .ticket-preview-issued-at,
        .ticket-preview-waiting {
            margin-top: 0.25rem;
        }

        .ticket-preview-note,
        .ticket-preview-footer {
            margin-top: 1rem;
            font-size: 0.95rem;
            color: #495057;
            white-space: pre-line;
        }

        .ticket-preview-note {
            padding-top: 0.75rem;
            border-top: 1px dashed #dee2e6;
        }

        .ticket-preview-footer {
            color: #adb5bd;
        }

        .ticket-preview-qr {
            margin: 1.25rem auto 0.5rem;
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ticket-preview-qr-placeholder {
            width: 100%;
            height: 100%;
        }

        .ticket-preview-qr canvas {
            width: 100%;
            height: 100%;
            image-rendering: pixelated;
        }

        .ticket-preview-qr-fallback {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            word-break: break-word;
        }

        .ticket-preview-print-count {
            margin-top: 1.25rem;
            font-size: 0.95rem;
            color: #6c757d;
        }

        .swal2-popup.swal-ticket-preview-popup {
            border-radius: 18px;
        }

        .swal-ticket-preview-container {
            margin: 0;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: none;
                font-family: 'Sarabun', sans-serif;
            }
            .kiosk-container, .hospital-header, .step-content, .btn, .form-text, .qr-code p {
                display: none !important;
            }
            .queue-display {
                display: block !important;
                box-shadow: none;
                padding: 0;
                margin: 0;
                text-align: center;
            }
            #step3 {
                display: block !important;
            }
            .print-area {
                width: 80mm;
                height: 140mm;
                padding: 5mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .print-area .hospital-name {
                font-size: 14pt;
                font-weight: bold;
            }
            .print-area h3 {
                font-size: 16pt;
                margin: 10px 0;
            }
            .print-area .queue-number {
                font-size: 36pt;
                font-weight: bold;
                margin: 10px 0;
            }
            .print-area .queue-type, .print-area .datetime {
                font-size: 12pt;
            }
            .print-area .qr-container {
                margin: 15px 0;
            }
            .print-area .footer {
                font-size: 10pt;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="kiosk-container">
            <!-- Header -->
            <div class="hospital-header">
                <div class="hospital-logo mx-auto mb-3">
                    <i class="fas fa-hospital-alt fa-4x text-primary"></i>
                </div>
                <h1 class="h2 text-primary mb-2">โรงพยาบาลยุวประสาทไวทโยปถัมภ์</h1>
                <h2 class="h4 text-muted">ระบบรับบัตรคิวอัตโนมัติ</h2>
            </div>

            <!-- Main Content -->
            <div id="step1" class="step-content">
                <h3 class="text-center mb-4">เลือกประเภทการรับบริการ</h3>
                <div class="row" id="serviceTypes">
                    <!-- Service types will be loaded here -->
                </div>
                <div class="text-center mt-4">
                    <button class="btn btn-primary btn-kiosk" onclick="nextStep()" disabled id="nextBtn">
                        <i class="fas fa-arrow-right me-2"></i>ถัดไป
                    </button>
                </div>
            </div>

            <div id="step2" class="step-content d-none">
                <h3 class="text-center mb-4">ยืนยันตัวตน</h3>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="mb-4">
                            <label class="form-label h5">กรุณากรอกเลขบัตรประจำตัวประชาชน 13 หลัก</label>
                            <input type="text" class="form-control id-input" id="idCardNumber" inputmode="numeric"
                                   placeholder="0-0000-00000-00-0" maxlength="17" pattern="[0-9-]{17}">
                            <!-- <input 
                                type="text" 
                                class="form-control id-input"
                                id="idCardNumber" 
                                inputmode="none" 
                                style="ime-mode:disabled;" 
                                maxlength="17"
                                pattern="[0-9-]{17}"
                                placeholder="0-0000-00000-00-0"
                                readonly 
                                onfocus="this.blur();"> -->
                            <div class="form-text">หรือเสียบบัตรประจำตัวประชาชนที่เครื่องอ่านบัตร</div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-secondary btn-kiosk me-3" onclick="prevStep()">
                                <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                            </button>
                            <button class="btn btn-success btn-kiosk" onclick="generateQueue()" id="generateBtn" disabled>
                                <i class="fas fa-ticket-alt me-2"></i>รับบัตรคิว
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="step3" class="step-content d-none">
                <div class="queue-display">
                    <h3 class="text-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>รับบัตรคิวสำเร็จ
                    </h3>
                    <div class="queue-number" id="queueNumber">A001</div>
                    <div class="h5 mb-3" id="serviceTypeName">คิวทั่วไป</div>
                    <div class="text-muted mb-3" id="queueDateTime"></div>
                    
                    <div class="qr-code">
                        <canvas id="qrcode"></canvas>
                        <p class="mt-2">สแกน QR Code เพื่อตรวจสอบสถานะคิว</p>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-outline-primary btn-kiosk me-3" onclick="printQueue()">
                            <i class="fas fa-print me-2"></i>พิมพ์บัตรคิว
                        </button>
                        <button class="btn btn-primary btn-kiosk" onclick="resetKiosk()">
                            <i class="fas fa-home me-2"></i>หน้าแรก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- QR Code library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@latest/build/qrcode.min.js"></script>
    <!-- SweetAlert2 for printing status -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- ESC/POS encoder & BIXOLON Web Print helper -->
    <script src="assets/vendor/esc-pos-encoder.umd.js"></script>
    <script>
        let selectedServiceType = null;
        let currentQueue = null;
        let appSettings = {
            queue_print_count: 1
        };

        function escapeHtml(text) {
            if (text === null || text === undefined) {
                return '';
            }
            return text
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatMultiline(text) {
            return escapeHtml(text).replace(/\r?\n/g, '<br>');
        }

        function buildTicketPreview(ticket, printCount) {
            const previewId = `ticket-preview-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            const qrElementId = `${previewId}-qr`;

            const hospitalName = (ticket.hospitalName || '').toString().trim();
            const label = (ticket.label || 'บัตรคิว').toString().trim();
            const serviceType = (ticket.serviceType || '').toString().trim();
            const queueNumber = (ticket.queueNumber || '').toString().trim();
            const servicePoint = (ticket.servicePoint || '').toString().trim();
            const issuedAt = (ticket.issuedAt || '').toString().trim();
            const waitingRaw = ticket.waitingCount;
            const waitingNumber = typeof waitingRaw === 'number' ? waitingRaw : Number.parseInt(waitingRaw, 10);
            const waitingText = Number.isFinite(waitingNumber) ? `รอคิวก่อนหน้า ${waitingNumber}` : '';
            const additionalNote = (ticket.additionalNote || '').toString();
            const footer = (ticket.footer || '').toString();
            const qrData = (ticket.qrData || '').toString().trim();
            const copiesText = String(Math.max(1, parseInt(printCount, 10) || 1));

            const html = [
                `<div class="ticket-preview" id="${previewId}">`,
                hospitalName ? `<div class="ticket-preview-hospital">${escapeHtml(hospitalName.toLocaleUpperCase('th-TH'))}</div>` : '',
                label ? `<div class="ticket-preview-label">${escapeHtml(label)}</div>` : '',
                serviceType ? `<div class="ticket-preview-service">${escapeHtml(serviceType)}</div>` : '',
                queueNumber ? `<div class="ticket-preview-queue">${escapeHtml(queueNumber)}</div>` : '',
                servicePoint ? `<div class="ticket-preview-service-point">${escapeHtml(servicePoint)}</div>` : '',
                issuedAt ? `<div class="ticket-preview-issued-at">${escapeHtml(issuedAt)}</div>` : '',
                waitingText ? `<div class="ticket-preview-waiting">${escapeHtml(waitingText)}</div>` : '',
                additionalNote ? `<div class="ticket-preview-note">${formatMultiline(additionalNote)}</div>` : '',
                `<div class="ticket-preview-qr" id="${qrElementId}">${qrData ? '<div class="ticket-preview-qr-placeholder">&nbsp;</div>' : ''}</div>`,
                footer ? `<div class="ticket-preview-footer">${formatMultiline(footer)}</div>` : '',
                `<div class="ticket-preview-print-count">จำนวนสำเนา: <strong>${escapeHtml(copiesText)}</strong></div>`,
                '</div>'
            ].join('');

            return {
                html,
                onOpen(modalElement) {
                    const qrContainer = modalElement && modalElement.querySelector(`#${qrElementId}`);
                    if (!qrContainer) {
                        return;
                    }

                    qrContainer.innerHTML = '';

                    if (!qrData) {
                        const noData = document.createElement('div');
                        noData.className = 'ticket-preview-qr-fallback';
                        noData.textContent = 'ไม่มีข้อมูล QR Code';
                        qrContainer.appendChild(noData);
                        return;
                    }

                    const renderFallback = () => {
                        const fallback = document.createElement('div');
                        fallback.className = 'ticket-preview-qr-fallback';
                        fallback.textContent = qrData;
                        qrContainer.appendChild(fallback);
                    };

                    if (typeof QRCode !== 'undefined' && typeof QRCode.toCanvas === 'function') {
                        const canvas = document.createElement('canvas');
                        QRCode.toCanvas(canvas, qrData, { width: 140, margin: 1 }, function(error) {
                            if (error) {
                                console.error('Ticket preview QR Code error:', error);
                                renderFallback();
                                return;
                            }
                            qrContainer.appendChild(canvas);
                        });
                    } else {
                        renderFallback();
                    }
                }
            };
        }

        $(document).ready(function() {
            loadAppSettings();
            loadServiceTypes();
            setupIdCardInput();
        });

        function loadAppSettings() {
            $.get('api/get_settings.php', function(data) {
                appSettings = data;
            }).fail(function() {
                console.error('Could not load app settings.');
            });
        }

        function getTicketDateTime() {
            if (currentQueue && currentQueue.creation_time) {
                try {
                    return new Date(currentQueue.creation_time.replace(' ', 'T')).toLocaleString('th-TH');
                } catch (error) {
                    console.warn('Cannot parse queue creation time, using current time.', error);
                }
            }
            return new Date().toLocaleString('th-TH');
        }

        function buildTicketForPrinting() {
            const hospitalName = (appSettings.hospital_name || 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์').trim();
            const ticket = {
                label: 'บัตรคิว',
                hospitalName,
                serviceType: selectedServiceType?.name || '',
                queueNumber: currentQueue?.queue_number || '',
                servicePoint: currentQueue?.service_point_name || '',
                issuedAt: getTicketDateTime(),
                additionalNote: (appSettings.bixolon_additional_note || '').trim() || '',
                footer: (appSettings.bixolon_ticket_footer || '').trim() || '',
                qrData: `${window.location.origin}/check_status.php?queue_id=${currentQueue?.queue_id || ''}`
            };

            if (typeof currentQueue?.waiting_position === 'number') {
                ticket.waitingCount = currentQueue.waiting_position;
            }

            return ticket;
        }

        function resolveQueuePrinterEndpoint() {
            const origin = window.location.origin;
            const defaultEndpoint = `${origin}/api/queue_printer.php`;
            const configured = ((appSettings.queue_printer_endpoint || '').trim() || (appSettings.bixolon_service_url || '').trim());
            const path = (appSettings.bixolon_service_path || '').trim();

            if (!configured) {
                return defaultEndpoint;
            }

            const legacyLocalPattern = /^(https?:\/\/)?(localhost|127\.0\.0\.1)(:\d+)?$/i;
            if (legacyLocalPattern.test(configured) && (!path || path === '/commands/print')) {
                return defaultEndpoint;
            }

            const appendPath = (baseUrl) => {
                if (!path) {
                    return baseUrl;
                }
                if (/\.php(\?|$)/i.test(baseUrl)) {
                    return baseUrl;
                }
                return `${baseUrl.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
            };

            if (/^https?:\/\//i.test(configured)) {
                return appendPath(configured);
            }

            if (configured.startsWith('/')) {
                return appendPath(`${origin}${configured}`);
            }

            return appendPath(`${origin}/${configured.replace(/^\//, '')}`);
        }

        async function sendTicketToPrinterService(printCount) {
            const endpoint = resolveQueuePrinterEndpoint();
            const timeoutMs = Math.max(1000, parseInt(appSettings.bixolon_timeout, 10) || 5000);

            const options = {
                qrModel: parseInt(appSettings.bixolon_qr_model, 10),
                qrSize: parseInt(appSettings.bixolon_qr_module_size, 10),
                qrErrorLevel: (appSettings.bixolon_qr_error_level || 'm').toString().toUpperCase(),
                cutType: (appSettings.bixolon_cut_type || 'partial').toString().toLowerCase(),
                trailingFeed: parseInt(appSettings.bixolon_trailing_feed, 10),
                profile: (appSettings.queue_printer_profile || '').trim() || undefined,
                codeTable: parseInt((appSettings.queue_printer_code_table || appSettings.bixolon_code_table), 10)
            };

            const sanitizedOptions = Object.fromEntries(Object.entries(options).filter(([_, value]) => Number.isFinite(value) || typeof value === 'string'));

            const payload = {
                copies: Math.max(1, parseInt(printCount, 10) || 1),
                interface: (appSettings.bixolon_printer_interface || '').trim() || undefined,
                target: (appSettings.bixolon_printer_target || '').trim() || undefined,
                port: appSettings.bixolon_printer_port ? parseInt(appSettings.bixolon_printer_port, 10) : undefined,
                timeout: timeoutMs,
                ticket: buildTicketForPrinting(),
                options: sanitizedOptions
            };

            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });
                clearTimeout(timer);

                const responseText = await response.text();
                let json = null;
                if (responseText) {
                    try {
                        json = JSON.parse(responseText);
                    } catch (error) {
                        console.warn('Printer service returned non-JSON response', responseText);
                    }
                }

                if (!response.ok || (json && json.success === false)) {
                    const message = json?.message || responseText || `บริการพิมพ์ตอบกลับสถานะ ${response.status}`;
                    throw new Error(message);
                }

                return json || {};
            } catch (error) {
                clearTimeout(timer);
                if (error.name === 'AbortError') {
                    throw new Error('การเชื่อมต่อกับบริการพิมพ์หมดเวลา กรุณาตรวจสอบเครื่องพิมพ์');
                }
                console.error('Printer service request failed', error);
                throw error;
            }
        }

        function loadServiceTypes() {
            $.get('api/get_service_types.php', function(data) {
                const container = $('#serviceTypes');
                container.empty();
                
                data.forEach(function(type) {
                    const card = `
                        <div class="col-md-12 mb-3">
                            <div class="service-type-card" onclick="selectServiceType(${type.queue_type_id}, '${type.type_name}')">
                                <div class="text-center">
                                    <i class="${type.icon_class} fa-3x text-primary mb-3"></i>
                                    <h5>${type.type_name}</h5>
                                    <p class="text-muted">${type.description}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(card);
                });
            }).fail(function() {
                alert('ไม่สามารถโหลดข้อมูลประเภทคิวได้');
            });
        }

        function selectServiceType(typeId, typeName) {
            $('.service-type-card').removeClass('selected');
            event.currentTarget.classList.add('selected');
            
            selectedServiceType = {
                id: typeId,
                name: typeName
            };
            
            $('#nextBtn').prop('disabled', false);
        }

        function setupIdCardInput() {
            $('#idCardNumber').on('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 13) value = value.substr(0, 13);
                
                // Format: 0-0000-00000-00-0
                if (value.length >= 1) {
                    value = value.replace(/(\d{1})(\d{0,4})(\d{0,5})(\d{0,2})(\d{0,1})/, function(match, p1, p2, p3, p4, p5) {
                        let result = p1;
                        if (p2) result += '-' + p2;
                        if (p3) result += '-' + p3;
                        if (p4) result += '-' + p4;
                        if (p5) result += '-' + p5;
                        return result;
                    });
                }
                
                this.value = value;
                
                const cleanValue = value.replace(/\D/g, '');
                $('#generateBtn').prop('disabled', cleanValue.length !== 13);
            });
            
            // รองรับการวางข้อมูล (paste)
            $('#idCardNumber').on('paste', function(e) {
                setTimeout(() => {
                    $(this).trigger('input');
                }, 10);
            });
        }

        function nextStep() {
            $('#step1').addClass('d-none');
            $('#step2').removeClass('d-none');
            $('#idCardNumber').focus();
        }

        function prevStep() {
            $('#step2').addClass('d-none');
            $('#step1').removeClass('d-none');
        }

        function generateQueue() {
            const idCard = $('#idCardNumber').val().replace(/\D/g, '');
            
            if (idCard.length !== 13) {
                alert('กรุณากรอกเลขบัตรประจำตัวประชาชนให้ครบ 13 หลัก');
                return;
            }

            $('#generateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>กำลังสร้างคิว...');

            $.post('api/generate_queue.php', {
                queue_type_id: selectedServiceType.id,
                id_card_number: idCard
            }, function(response) {
                if (response.success) {
                    currentQueue = response.queue;
                    showQueueResult();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + response.message);
                    $('#generateBtn').prop('disabled', false).html('<i class="fas fa-ticket-alt me-2"></i>รับบัตรคิว');
                }
            }).fail(function() {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                $('#generateBtn').prop('disabled', false).html('<i class="fas fa-ticket-alt me-2"></i>รับบัตรคิว');
            });
        }

        function showQueueResult() {
            $('#step2').addClass('d-none');
            $('#step3').removeClass('d-none');
            
            $('#queueNumber').text(currentQueue.queue_number);
            $('#serviceTypeName').text(selectedServiceType.name);
            $('#queueDateTime').text(new Date().toLocaleString('th-TH'));
            
            const qrData = `${window.location.origin}/check_status.php?queue_id=${currentQueue.queue_id}`;
            
            if (typeof QRCode !== 'undefined' && typeof QRCode.toCanvas === 'function') {
                // ใช้ QRCode library (เช่น https://cdn.jsdelivr.net/npm/qrcode@latest/build/qrcode.min.js)
                QRCode.toCanvas(document.getElementById('qrcode'), qrData, {
                    width: 200,
                    height: 200
                }, function(error) {
                    if (error) {
                        console.error('QR Code error:', error);
                        fallbackQRCode(qrData);
                    }
                });
            } else {
                fallbackQRCode(qrData);
            }
        }

        // Fallback QR Code using pure JavaScript
        function fallbackQRCode(qrData) {
            const canvas = document.getElementById('qrcode');
            const ctx = canvas.element.getContext('2d');
            
            // Set canvas size
            canvas.width = 200;
            canvas.height = 200;
            
            // Try to load QRious library as fallback
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
            script.onload = function() {
                try {
                    new QRious({
                        element: canvas,
                        value: qrData,
                        size: 200,
                        backgroundAlpha: 1,
                        foreground: '#000000',
                        background: '#FFFFFF',
                        level: 'H'
                    });
                } catch (error) {
                    console.error('QRious error:', error);
                    drawFallbackText();
                }
            };
            
            script.onerror = function() {
                drawFallbackText();
            };
            
            document.head.appendChild(script);
            
            function drawFallbackText() {
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, 200, 200);
                ctx.fillStyle = '#6c757d';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('QR Code', 100, 90);
                ctx.fillText('ไม่สามารถสร้างได้', 100, 110);
            }
        }

        async function printQueue() {
            if (!currentQueue || !selectedServiceType) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบข้อมูลคิว',
                    text: 'กรุณากดรับบัตรคิวใหม่อีกครั้ง',
                    confirmButtonText: 'ตกลง'
                });
                return;
            }

            const ticket = buildTicketForPrinting();
            const printCount = Math.max(1, parseInt(appSettings.queue_print_count, 10) || 1);
            const preview = buildTicketPreview(ticket, printCount);

            const result = await Swal.fire({
                title: 'ตรวจสอบบัตรคิวก่อนพิมพ์',
                html: preview.html,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'พิมพ์บัตรคิว',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    popup: 'swal-ticket-preview-popup',
                    htmlContainer: 'swal-ticket-preview-container'
                },
                returnFocus: false,
                width: '32rem',
                didOpen: (modalElement) => {
                    if (preview.onOpen) {
                        preview.onOpen(modalElement);
                    }
                }
            });

            if (result.isConfirmed) {
                await performTicketPrint(printCount);
            }
        }

        async function performTicketPrint(printCount) {
            Swal.fire({
                title: 'กำลังพิมพ์บัตรคิว',
                text: 'กรุณารอสักครู่...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                await sendTicketToPrinterService(printCount);
                Swal.fire({
                    icon: 'success',
                    title: 'พิมพ์บัตรคิวสำเร็จ',
                    timer: 1500,
                    showConfirmButton: false
                });
                return true;
            } catch (error) {
                const message = error?.message || 'ไม่สามารถสั่งพิมพ์บัตรคิวได้ กรุณาตรวจสอบเครื่องพิมพ์';
                Swal.fire({
                    icon: 'error',
                    title: 'พิมพ์บัตรคิวไม่สำเร็จ',
                    text: message,
                    confirmButtonText: 'ตกลง'
                });
                return false;
            }
        }

        function resetKiosk() {
            selectedServiceType = null;
            currentQueue = null;
            
            $('#step3').addClass('d-none');
            $('#step1').removeClass('d-none');
            
            $('.service-type-card').removeClass('selected');
            $('#nextBtn').prop('disabled', true);
            $('#idCardNumber').val('');
            $('#generateBtn').prop('disabled', true).html('<i class="fas fa-ticket-alt me-2"></i>รับบัตรคิว');
        }

        // Auto-refresh service types every 30 seconds
        setInterval(loadServiceTypes, 30000);
        
    </script>

    <script>
        window.addEventListener('barcode-scan', function(e){
            const code = e.detail?.code || '';
            console.log('Barcode from native:', code);
            // ตัวอย่าง: ใส่ค่าแล้ว submit
            const input = document.querySelector('#idCardNumber');
            if (input) {
            input.value = code;
            input.dispatchEvent(new Event('input'));
            // submit หรือ fetch ไปค้นสินค้า
            }
        });
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js').catch(console.error);
            });
        }
    </script>
</body>
</html>
