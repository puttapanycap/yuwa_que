# Yuwa Queue Browser (Android)

แอปพลิเคชัน Android ที่พัฒนาโดยใช้ Python (Buildozer + python-for-android) เพื่อเปิดเว็บ [https://que.ycap.go.th](https://que.ycap.go.th) ใน WebView แบบ Native และมีปุ่ม "เลือกเครื่องพิมพ์" สำหรับเรียก Android Printer Selector ผ่าน `PrintManager` ช่วยให้สามารถเลือกเครื่องพิมพ์และพิมพ์ใบคิวได้สะดวกบนอุปกรณ์ Android 7.1.2 (API 25) ขึ้นไป

## คุณสมบัติหลัก

- เปิดหน้าเว็บคิว `que.ycap.go.th` ใน WebView พร้อมเปิดใช้งาน JavaScript และ DOM Storage
- ปุ่ม "รีเฟรช" สำหรับโหลดหน้าใหม่อย่างรวดเร็ว
- ปุ่ม "เลือกเครื่องพิมพ์" เรียก Printer Selector ของ Android ผ่าน `PrintManager`
- รองรับ Android 7.1.2 (API 25) เป็นต้นไป
- เปิดใช้งาน WebView Debugging (เฉพาะโหมด Debug) เพื่อให้ตรวจสอบผ่าน Chrome DevTools ได้ง่าย

## โครงสร้างโครงการ

```
android_app/
├── buildozer.spec          # การตั้งค่า Buildozer/p4a พร้อม min API = 25
├── main.py                 # โค้ด Python หลัก (สร้าง UI และ PrintManager integration)
└── src/
    └── com/yuwa/browser/
        └── WebViewDebug.java  # Helper สำหรับเปิด WebView debugging ใน debug builds
```

## การเตรียมสภาพแวดล้อม

1. ติดตั้งระบบปฏิบัติการ Linux (Ubuntu แนะนำ) หรือใช้ WSL2
2. ติดตั้ง Docker (แนะนำ) หรือเตรียม Android SDK/NDK ตามคู่มือ Buildozer
3. ติดตั้ง [Buildozer](https://github.com/kivy/buildozer) และ dependencies
   ```bash
   pip install --upgrade buildozer cython
   sudo apt install -y openjdk-17-jdk unzip zip
   ```
4. ดาวน์โหลด Android SDK/NDK (หากยังไม่มี) และตั้งค่า environment variables
   ```bash
   export ANDROID_SDK_ROOT="$HOME/Android/Sdk"
   export ANDROID_NDK_HOME="$ANDROID_SDK_ROOT/ndk/25.2.9519653"
   ```

> **หมายเหตุ:** ค่า `android.sdk_path` และ `android.ndk_path` ใน `buildozer.spec` จะอ่านจาก environment variables ข้างต้นโดยอัตโนมัติ

## วิธี Build APK

1. เข้าไปที่โฟลเดอร์ `android_app`
   ```bash
   cd android_app
   ```
2. เริ่มต้น Build ครั้งแรก (Buildozer จะดาวน์โหลด dependency ที่ต้องใช้)
   ```bash
   buildozer android debug
   ```
   - ผลลัพธ์ APK จะอยู่ที่ `bin/yuwa_queue_browser-0.1.0-debug.apk`
3. เมื่อต้องการสร้างไฟล์สำหรับ Release ให้เซ็นชื่อ APK ตามขั้นตอนมาตรฐานของ Android
   ```bash
   buildozer android release
   buildozer android release deploy run
   ```

## การทดสอบ Printer Selector

1. ติดตั้ง APK ในอุปกรณ์ Android 7.1.2 (API 25) หรือสูงกว่า
2. เปิดแอป ระบบจะโหลดหน้า `https://que.ycap.go.th`
3. กดปุ่ม "เลือกเครื่องพิมพ์" จะมี Printer Selector ขึ้นมา
4. เลือกเครื่องพิมพ์ที่ต้องการ (เช่น Thermal Printer ที่รองรับ Android Printing)
5. กดยืนยันเพื่อพิมพ์เอกสารจาก WebView

## การปรับแต่งเพิ่มเติม

- หากต้องการตั้งค่า URL อื่น สามารถแก้ไขค่าคงที่ `WEB_URL` ใน `main.py`
- สามารถเพิ่ม Permission เพิ่มเติมใน `buildozer.spec` ได้ตามความจำเป็น (เช่น `android.permissions = INTERNET,WRITE_EXTERNAL_STORAGE`)
- หากต้องการปิด WebView debugging ใน debug build ให้ลบหรือแก้ไขเมธอด `WebViewDebug.enable()`

## Known Limitations

- ฟังก์ชัน Printer Selector พึ่งพา Android Print Framework ดังนั้นเครื่องพิมพ์ต้องรองรับมาตรฐานนี้ (ผ่าน Wi-Fi, Bluetooth, หรือ Cloud Print ผู้ผลิต)
- Buildozer ต้องใช้เวลาในการดาวน์โหลด dependency ครั้งแรกค่อนข้างนาน โดยเฉพาะ Android SDK/NDK
- หากต้องการใช้ Bluetooth Printer ที่ไม่มี Android Print Service อาจต้องพัฒนาปลั๊กอินเฉพาะ

## License

MIT License (เหมือนกับโปรเจ็กต์หลัก)
