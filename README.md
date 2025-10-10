# 🏥 ระบบจัดการคิวโรงพยาบาลยุวประสาท

ระบบจัดการคิวที่ครอบคลุมและทันสมัย ออกแบบมาสำหรับโรงพยาบาลและสถานพยาบาล สร้างด้วย PHP, MySQL และเทคโนโลยีเว็บสมัยใหม่ เพื่อให้การจัดการผู้ป่วยมีประสิทธิภาพ

## ✨ คุณสมบัติ

### 🎯 คุณสมบัติหลัก
- **การจัดการคิวหลายบริการ** - รองรับประเภทบริการและแผนกต่างๆ
- **การติดตามสถานะแบบเรียลไทม์** - อัปเดตสถานะคิวสดและการตรวจสอบ
- **แดชบอร์ดเจ้าหน้าที่** - อินเทอร์เฟซเจ้าหน้าที่ที่ครอบคลุมสำหรับการจัดการคิว
- **แผงควบคุมผู้ดูแลระบบ** - การควบคุมการดูแลระบบและการกำหนดค่าระบบอย่างเต็มรูปแบบ
- **การออกแบบที่ตอบสนองต่อมือถือ** - ทำงานได้อย่างสมบูรณ์แบบบนอุปกรณ์ทั้งหมด
- **ระบบเรียกคิวด้วยเสียง** - การเรียกคิวด้วยเสียงพูดพร้อมตัวเลือกเสียงที่หลากหลาย
- **รองรับหลายภาษา** - อินเทอร์เฟซภาษาไทยและภาษาอังกฤษ

### 📊 คุณสมบัติขั้นสูง
- **แดชบอร์ดการวิเคราะห์** - การวิเคราะห์แบบเรียลไทม์และเมตริกประสิทธิภาพ
- **การรายงานขั้นสูง** - รายงานที่ปรับแต่งได้พร้อมรูปแบบการส่งออกที่หลากหลาย
- **ระบบรีเซ็ตอัตโนมัติ** - กำหนดการรีเซ็ตหมายเลขคิวอัตโนมัติ
- **ศูนย์การแจ้งเตือน** - การแจ้งเตือนทางอีเมลและ Telegram
- **การจัดการขั้นตอนการบริการ** - กำหนดค่ากระบวนการบริการหลายขั้นตอนที่ซับซ้อน
- **การควบคุมการเข้าถึงตามบทบาท** - สิทธิ์และการจัดการผู้ใช้แบบละเอียด
- **การบันทึกการตรวจสอบ** - การติดตามกิจกรรมที่สมบูรณ์และบันทึกความปลอดภัย
- **สำรองและกู้คืน** - การสำรองและกู้คืนข้อมูลอัตโนมัติ

### 🔧 คุณสมบัติทางเทคนิค
- **RESTful API** - API ที่สมบูรณ์สำหรับการรวมแอปมือถือ
- **การวินิจฉัยฐานข้อมูล** - การตรวจสอบสถานะฐานข้อมูลในตัว
- **การกำหนดค่าสภาพแวดล้อม** - การจัดการการกำหนดค่าที่ปลอดภัย
- **Composer Package Management** - การจัดการ Dependency ของ PHP สมัยใหม่
- **Responsive UI** - การออกแบบที่ตอบสนองตาม Bootstrap

## 🚀 เริ่มต้นอย่างรวดเร็ว

### ข้อกำหนดเบื้องต้น
- **PHP 8.0+** พร้อมส่วนขยาย: `mysqli`, `json`, `curl`, `mbstring`
- **MySQL 5.7+** หรือ **MariaDB 10.3+**
- **Web Server** (Apache/Nginx)
- **Composer** (สำหรับการจัดการ Dependency)

### การติดตั้ง

#### ตัวเลือกที่ 1: การติดตั้งอัตโนมัติ (แนะนำ)

**Linux/macOS:**
```bash
# Clone repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git
cd yuwaprasart-queue-system

# ทำให้สคริปต์การติดตั้งสามารถเรียกใช้งานได้
chmod +x install.sh

# เรียกใช้งานการติดตั้ง
./install.sh
```

**Windows:**
```batch
# Clone repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git
cd yuwaprasart-queue-system

# เรียกใช้งานการติดตั้ง
install.bat
```

#### ตัวเลือกที่ 2: การติดตั้งด้วยตนเอง

1. **ตรวจสอบข้อกำหนดของระบบ**
   ```bash
   php scripts/check-system-requirements.php
   ```

2. **ติดตั้ง Dependencies**
   ```bash
   composer install --ignore-platform-req=ext-zip
   ```

3. **ติดตั้ง Packages เสริม**
   ```bash
   php scripts/install-optional-packages.php
   ```

4. **กำหนดค่า Environment**
   ```bash
   cp .env.example .env
   # แก้ไข .env ด้วยข้อมูลรับรองฐานข้อมูลของคุณ
   ```

5. **นำเข้าฐานข้อมูล**
   ```bash
   mysql -u root -p yuwaprasart_queue < database/schema.sql
   ```

## ⚙️ การกำหนดค่า

### การกำหนดค่าฐานข้อมูล

แก้ไขไฟล์ `.env` ด้วยข้อมูลรับรองฐานข้อมูลของคุณ:

```env
# การกำหนดค่าฐานข้อมูล
DB_HOST=localhost
DB_NAME=yuwaprasart_queue
DB_USER=your_username
DB_PASS=your_password

# การตั้งค่าแอปพลิเคชัน
APP_NAME="Yuwaprasart Queue System"
APP_URL=http://localhost/yuwaprasart-queue-system
APP_TIMEZONE=Asia/Bangkok

# ความปลอดภัย
JWT_SECRET=your-secret-key-here
SESSION_LIFETIME=3600

# Telegram Bot (Optional)
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# การกำหนดค่าอีเมล (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

### การกำหนดค่า Web Server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [L]
```

#### Nginx
```nginx
location /api/ {
    try_files $uri $uri.php $uri/ =404;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

## 🖨️ การพิมพ์บัตรคิวด้วยเครื่องพิมพ์ความร้อน

ระบบเวอร์ชันล่าสุดย้ายไปใช้ [`mike42/escpos-php`](https://github.com/mike42/escpos-php) เพื่อสร้างคำสั่ง ESC/POS ที่รองรับภาษาไทยและส่งผ่านตัวเชื่อมต่อของไลบรารีโดยตรงจากฝั่ง PHP ไม่จำเป็นต้องเปิดบริการ Node.js แยกอีกต่อไป โดยสคริปต์จะเรนเดอร์บัตรคิวเป็นภาพ (ฝังฟอนต์ Sarabun เป็น Base64 ภายในโค้ด และสร้าง QR Code ด้วย `chillerlan/php-qrcode`) ก่อนส่งคำสั่งพิมพ์ ดังนั้นหน้าตาที่ได้จากเครื่องพิมพ์จะตรงกับตัวอย่างในโมดอลอย่างแท้จริง หน้า Kiosk จะเรียก `api/queue_printer.php` ให้โดยอัตโนมัติเมื่อผู้ใช้ยืนยันการพิมพ์ในโมดอลตัวอย่าง

### 1) ติดตั้งไลบรารี

```bash
composer install        # หรือ composer update หากมีการเพิ่ม dependency ใหม่
```

> `mike42/escpos-php` และ `chillerlan/php-qrcode` จะถูกติดตั้ง/ดึงมาพร้อมกับ composer dependencies ส่วนฟอนต์ Sarabun ถูกฝังเป็นข้อมูล Base64 ภายในสคริปต์แล้ว ดังนั้นเพียงแค่เรียก `composer install` ในโฟลเดอร์โปรเจกต์ก็พร้อมใช้งานโดยไม่ต้องจัดการไฟล์ไบนารีเพิ่มเติม

### 2) ตั้งค่าเครื่องพิมพ์

ค่าที่เกี่ยวข้องกับการพิมพ์ยังคงอ่านจาก `settings` เดิม สามารถกำหนดผ่านหน้า Admin หรือแก้ไขข้อมูลในฐานข้อมูลได้ โดยค่าที่สำคัญมีดังนี้

| คีย์ในการตั้งค่า | ค่าเริ่มต้น | คำอธิบาย |
| --- | --- | --- |
| `bixolon_printer_interface` | `network` | ประเภทการเชื่อมต่อ (`network`, `printer`, `windows`, `file` หรือกำหนดเป็น URI เต็ม เช่น `tcp://192.168.0.50:9100`) |
| `bixolon_printer_target` | *(เว้นว่างไม่ได้สำหรับ network/windows)* | ที่อยู่ปลายทาง เช่น IP หรือชื่อแชร์ของเครื่องพิมพ์ |
| `bixolon_printer_port` | `9100` | พอร์ต TCP ของเครื่องพิมพ์ (ใช้เมื่อ interface เป็น network) |
| `bixolon_service_url` | `/api/queue_printer.php` | ปลายทางบริการพิมพ์ (ค่าเริ่มต้นคือสคริปต์ PHP ในโปรเจกต์) |
| `bixolon_service_path` | *(เว้นว่าง)* | ใช้เฉพาะกรณีเชื่อมต่อบริการภายนอก หากเว้นว่างจะเรียกปลายทางตาม `bixolon_service_url` โดยตรง |
| `bixolon_qr_module_size` | `6` | ขนาดโมดูลของ QR Code (1–16) |
| `bixolon_qr_model` | `2` | โมเดล QR (1, 2 หรือ 3 สำหรับ Micro QR) |
| `bixolon_qr_error_level` | `m` | ระดับการแก้ไขข้อผิดพลาดของ QR (`l`, `m`, `q`, `h`) |
| `bixolon_cut_type` | `partial` | รูปแบบการตัดกระดาษ (`partial` หรือ `full`) |
| `bixolon_trailing_feed` | `6` | จำนวนบรรทัดที่ป้อนกระดาษเพิ่มก่อนสั่งตัด |
| `queue_printer_profile` | `default` | ชื่อ Capability Profile ของ `mike42/escpos-php` (ปรับเมื่อเครื่องพิมพ์ต้องการโปรไฟล์เฉพาะ) |
| `queue_printer_code_table` | `21` | เลข Code Table (`ESC t n`) ที่จะเลือกเมื่อพิมพ์ (21 คือ CP874/TIS-620 สำหรับภาษาไทย) |

### 3) ทดลองสั่งพิมพ์ผ่าน API

สามารถเรียก `POST /api/queue_printer.php` เพื่อทดสอบได้โดยตรง ตัวอย่าง payload สำหรับพิมพ์บัตรคิวพร้อม QR code:

```bash
curl -X POST http://localhost/api/queue_printer.php \
  -H "Content-Type: application/json" \
  -d '{
        "copies": 1,
        "interface": "network",
        "target": "192.168.0.50",
        "port": 9100,
        "options": {
          "qrModel": 2,
          "qrSize": 6,
          "qrErrorLevel": "M",
          "cutType": "partial",
          "codeTable": 21
        },
        "ticket": {
          "hospitalName": "โรงพยาบาลยุวประสาทไวทโยปถัมภ์",
          "serviceType": "คิวทั่วไป",
          "queueNumber": "A001",
          "servicePoint": "จุดคัดกรอง",
          "issuedAt": "2024-05-01 08:30",
          "waitingCount": 3,
          "qrData": "https://example.com/check_status.php?queue_id=1",
          "additionalNote": "กรุณารอเรียกคิวจากเจ้าหน้าที่",
          "footer": "สแกน QR Code เพื่อตรวจสอบสถานะคิว"
        }
      }'
```

หากต้องการใช้บริการภายนอกแทนสคริปต์ที่มากับระบบ เพียงเปลี่ยนค่า `bixolon_service_url` ให้ชี้ไปยังปลายทางใหม่ได้ตามต้องการ ส่วนหน้า Kiosk จะยึดค่าดังกล่าวในการเรียกใช้งานเสมอ

## 📱 การใช้งาน

### สำหรับผู้ป่วย
1. **รับหมายเลขคิว** - เยี่ยมชมหน้าหลักและเลือกประเภทบริการ
2. **ตรวจสอบสถานะ** - ใช้รหัส QR หรือลิงก์ที่ให้ไว้เพื่อตรวจสอบสถานะคิว
3. **อัปเดตแบบเรียลไทม์** - ตรวจสอบตำแหน่งของคุณและเวลาที่รอโดยประมาณ

### สำหรับเจ้าหน้าที่
1. **เข้าสู่ระบบ** - เข้าถึงแดชบอร์ดเจ้าหน้าที่ที่ `/staff/`
2. **จัดการคิว** - เรียก, ข้าม หรือทำหมายเลขคิวให้เสร็จสมบูรณ์
3. **ตรวจสอบจุดบริการ** - ดูสถานะจุดบริการแบบเรียลไทม์

### สำหรับผู้ดูแลระบบ
1. **แผงควบคุมผู้ดูแลระบบ** - เข้าถึงอินเทอร์เฟซผู้ดูแลระบบเต็มรูปแบบที่ `/admin/`
2. **การจัดการผู้ใช้** - สร้างและจัดการบัญชีเจ้าหน้าที่
3. **การกำหนดค่าระบบ** - กำหนดค่าประเภทบริการ จุด และขั้นตอน
4. **รายงานและการวิเคราะห์** - สร้างรายงานที่ครอบคลุม

## 🔧 เอกสาร API

### การตรวจสอบสิทธิ์
```http
POST /api/mobile/auth.php
Content-Type: application/json

{
  "username": "staff_user",
  "password": "password"
}
```

### การจัดการคิว
```http
# สร้างคิวใหม่
POST /api/generate_queue.php
Content-Type: application/json

{
  "service_type_id": 1,
  "patient_name": "John Doe"
}

# รับสถานะคิว
GET /api/get_queues.php?service_type_id=1

# อัปเดตสถานะคิว
POST /api/queue_action.php
Content-Type: application/json

{
  "queue_id": 123,
  "action": "call",
  "service_point_id": 1
}
```

## 🎨 การปรับแต่ง

### ธีมและสไตล์
- แก้ไข `admin/globals.css` สำหรับสไตล์สากล
- แก้ไขตัวแปร Bootstrap ใน `tailwind.config.ts`
- ปรับแต่งสีและการสร้างแบรนด์ในการตั้งค่าผู้ดูแลระบบ

### ระบบเสียง
- อัปโหลดไฟล์เสียงที่กำหนดเองในแผงควบคุมผู้ดูแลระบบ
- กำหนดค่าการตั้งค่า TTS สำหรับภาษาต่างๆ
- ตั้งค่าลำดับการโทรด้วยเสียง

### การแจ้งเตือน
- กำหนดค่า Telegram bot สำหรับการแจ้งเตือนทันที
- ตั้งค่าเทมเพลตอีเมลสำหรับข้อความอัตโนมัติ
- ปรับแต่งทริกเกอร์และผู้รับการแจ้งเตือน

## 🔒 คุณสมบัติความปลอดภัย

### การตรวจสอบสิทธิ์และการอนุญาต
- **การตรวจสอบสิทธิ์ด้วย JWT Token** สำหรับการเข้าถึง API
- **การควบคุมการเข้าถึงตามบทบาท** พร้อมสิทธิ์แบบละเอียด
- **การจัดการ Session** พร้อมการหมดเวลาที่กำหนดค่าได้
- **การแฮชรหัสผ่าน** โดยใช้ password_hash() ของ PHP

### การปกป้องข้อมูล
- **การป้องกัน SQL Injection** โดยใช้ Prepared Statements
- **การป้องกัน XSS** ด้วยการ Sanitization ข้อมูลนำเข้า
- **การป้องกัน CSRF** สำหรับการส่งแบบฟอร์ม
- **การบันทึกการตรวจสอบ** สำหรับการดำเนินการด้านการดูแลระบบทั้งหมด

### ความปลอดภัยของระบบ
- **การป้องกันตัวแปร Environment** สำหรับข้อมูลที่ละเอียดอ่อน
- **ข้อจำกัดการอัปโหลดไฟล์** พร้อมการตรวจสอบประเภท
- **การจำกัดอัตรา** สำหรับ API endpoints
- **การใช้งาน Secure Headers**

## 📊 การตรวจสอบและการบำรุงรักษา

### การตรวจสอบสถานะ
```bash
# ตรวจสอบสถานะระบบ
php admin/database_diagnostic.php

# ตรวจสอบโครงสร้างฐานข้อมูล
php api/validate_database_structure.php

# ทดสอบฟังก์ชันการทำงานของสถานะคิว
php test_queue_status.php
```

### สำรองและกู้คืน
```bash
# สร้างข้อมูลสำรอง
php api/backup_before_reset.php

# กู้คืนจากข้อมูลสำรอง
# ใช้ส่วนต่อประสานการจัดการข้อมูลสำรองของแผงควบคุมผู้ดูแลระบบ
```

### การจัดการ Log
- **Application Logs**: `logs/app.log`
- **Error Logs**: `logs/error.log`
- **Audit Logs**: มีอยู่ในแผงควบคุมผู้ดูแลระบบ
- **Auto-Reset Logs**: `logs/auto_reset.log`

## 🚀 การเพิ่มประสิทธิภาพ

### การเพิ่มประสิทธิภาพฐานข้อมูล
- **Indexed Columns** สำหรับการสืบค้นที่รวดเร็ว
- **Query Optimization** พร้อมการ Join ที่เหมาะสม
- **Connection Pooling** สำหรับปริมาณการใช้งานสูง
- **Regular Maintenance** รวมสคริปต์

### Caching
- **Browser Caching** สำหรับ Static Assets
- **Database Query Caching** สำหรับการสืบค้นซ้ำ
- **Session Caching** สำหรับข้อมูลผู้ใช้
- **Redis Support** สำหรับ Caching ขั้นสูง (Optional)

### การเพิ่มประสิทธิภาพ Frontend
- **Minified CSS/JS** เพื่อการโหลดที่เร็วขึ้น
- **Responsive Images** เพื่อการเพิ่มประสิทธิภาพมือถือ
- **Lazy Loading** เพื่อประสิทธิภาพที่ดีขึ้น
- **Progressive Web App** คุณสมบัติ

## 🔧 การแก้ไขปัญหา

### ปัญหาทั่วไป

#### การติดตั้ง Composer ล้มเหลว
```bash
# Missing ZIP extension
composer install --ignore-platform-req=ext-zip

# ตรวจสอบข้อกำหนด
php scripts/check-system-requirements.php
```

#### ปัญหาการเชื่อมต่อฐานข้อมูล
```bash
# ทดสอบการเชื่อมต่อฐานข้อมูล
php admin/database_diagnostic.php

# ตรวจสอบข้อมูลรับรองในไฟล์ .env
# ตรวจสอบว่าบริการ MySQL กำลังทำงานอยู่
```

#### ข้อผิดพลาดเกี่ยวกับสิทธิ์
```bash
# ตั้งค่าสิทธิ์ที่เหมาะสม
chmod -R 755 .
chmod -R 777 logs/
chmod -R 777 uploads/
```

#### ระบบเสียงไม่ทำงาน
- ตรวจสอบสิทธิ์เสียงของเบราว์เซอร์
- ตรวจสอบการกำหนดค่าบริการ TTS
- ทดสอบไฟล์เสียงในแผงควบคุมผู้ดูแลระบบ

### การขอความช่วยเหลือ
- **เอกสารประกอบ**: ตรวจสอบเอกสารประกอบโค้ด Inline
- **Logs**: ตรวจสอบ Application และ Error Logs
- **Diagnostics**: ใช้เครื่องมือวินิจฉัยในตัว
- **Support**: ติดต่อผู้ดูแลระบบ

## 🤝 การมีส่วนร่วม

### การตั้งค่าการพัฒนา
```bash
# Clone repository
git clone https://github.com/your-repo/yuwaprasart-queue-system.git

# ติดตั้ง Dependencies
composer install

# ตั้งค่าสภาพแวดล้อมการพัฒนา
cp .env.example .env.dev

# เรียกใช้งานการทดสอบ
composer test
```

### มาตรฐานโค้ด
- มาตรฐานการเขียนโค้ด **PSR-12**
- เอกสารประกอบ **PHPDoc** สำหรับฟังก์ชันทั้งหมด
- **Unit Tests** สำหรับฟังก์ชันการทำงานที่สำคัญ
- **Security Review** สำหรับการเปลี่ยนแปลงทั้งหมด

### การส่งการเปลี่ยนแปลง
1. Fork repository
2. สร้าง Feature Branch
3. ทำการเปลี่ยนแปลงของคุณ
4. เพิ่มการทดสอบหากมี
5. ส่ง Pull Request

## 📄 ใบอนุญาต

โปรเจ็กต์นี้ได้รับอนุญาตภายใต้ MIT License - ดูรายละเอียดในไฟล์ [LICENSE](LICENSE)

## 🙏 ขอขอบคุณ

- **Bootstrap** สำหรับ Responsive UI Framework
- **Chart.js** สำหรับการแสดงภาพการวิเคราะห์
- **PHPMailer** สำหรับฟังก์ชันการทำงานของอีเมล
- **mPDF** สำหรับการสร้าง PDF
- **Telegram Bot API** สำหรับการแจ้งเตือน

## 📞 การสนับสนุน

สำหรับการสนับสนุนด้านเทคนิคหรือคำถาม:
- **อีเมล**: support@yuwaprasart.com
- **เอกสารประกอบ**: [Wiki](https://github.com/puttapanycap/yuwa_que/wiki)
- **Issues**: [GitHub Issues](https://github.com/puttapanycap/yuwa_que/issues)

---

**สร้างด้วย ❤️ สำหรับโรงพยาบาลยุวประสาท**
