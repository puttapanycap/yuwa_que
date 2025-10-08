<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d6efd" />
    <link rel="manifest" href="./manifest.json" />
    <meta name="mobile-web-app-capable" content="yes">

    <title>ระบบเรียกคิว - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
                padding: 5mm;
                box-sizing: border-box;
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
    
    <script>
        let selectedServiceType = null;
        let currentQueue = null;
        let appSettings = {
            queue_print_count: 1
        };

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

        function printQueue() {
            const printCount = parseInt(appSettings.queue_print_count, 10) || 1;
            let ticketsHtml = '';

            for (let i = 0; i < printCount; i++) {
                ticketsHtml += `
                    <div class="print-area" style="page-break-after: ${i < printCount - 1 ? 'always' : 'auto'};">
                        <div class="hospital-name">${appSettings.hospital_name || 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์'}</div>
                        <h3>บัตรคิว</h3>
                        <div class="queue-number">${currentQueue.queue_number}</div>
                        <div class="queue-type">${selectedServiceType.name}</div>
                        <div class="datetime">${new Date().toLocaleString('th-TH')}</div>
                        <div class="qr-container">
                            <canvas id="printQR_${i}" width="150" height="150"></canvas>
                        </div>
                        <div class="footer">สแกน QR Code เพื่อตรวจสอบสถานะคิว</div>
                    </div>
                `;
            }

            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>บัตรคิว</title>
                    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
                    <style>
                        @page {
                            size: 80mm 140mm;
                            margin: 0;
                        }
                        body {
                            font-family: 'Sarabun', sans-serif;
                            text-align: center;
                            margin: 0;
                            padding: 0;
                            width: 80mm;
                            box-sizing: border-box;
                            color: #000;
                        }
                        .print-area {
                            width: 80mm;
                            height: 140mm;
                            padding: 5mm;
                            box-sizing: border-box;
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                        }
                        .hospital-name {
                            font-size: 12pt;
                            font-weight: bold;
                            margin-bottom: 5px;
                        }
                        h3 {
                            font-size: 14pt;
                            margin: 5px 0;
                        }
                        .queue-number {
                            font-size: 32pt;
                            font-weight: bold;
                            margin: 10px 0;
                        }
                        .queue-type, .datetime {
                            font-size: 10pt;
                            margin: 5px 0;
                        }
                        .qr-container {
                            margin: 10px 0;
                        }
                        .footer {
                            font-size: 8pt;
                            margin-top: 10px;
                        }
                    </style>
                </head>
                <body>
                    ${ticketsHtml}
                </body>
                </html>
            `;

            const printFrame = document.createElement('iframe');
            printFrame.style.position = 'absolute';
            printFrame.style.width = '0';
            printFrame.style.height = '0';
            printFrame.style.border = '0';
            document.body.appendChild(printFrame);

            const frameDoc = printFrame.contentWindow.document;
            frameDoc.open();
            frameDoc.write(content);
            frameDoc.close();

            const qrData = `${window.location.origin}/check_status.php?queue_id=${currentQueue.queue_id}`;
            const script = frameDoc.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
            
            script.onload = function() {
                try {
                    for (let i = 0; i < printCount; i++) {
                        new printFrame.contentWindow.QRious({
                            element: frameDoc.getElementById(`printQR_${i}`),
                            value: qrData,
                            size: 150,
                            level: 'H'
                        });
                    }
                } catch (e) {
                    console.error("QRious error:", e);
                }
                
                setTimeout(() => {
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();
                    document.body.removeChild(printFrame);
                }, 500);
            };

            script.onerror = function() {
                console.error("Could not load QRious library.");
                setTimeout(() => {
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();
                    document.body.removeChild(printFrame);
                }, 250);
            };

            frameDoc.head.appendChild(script);
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
