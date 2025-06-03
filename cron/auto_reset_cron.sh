#!/bin/bash

# Auto Reset Cron Job Script
# เพิ่มใน crontab: */5 * * * * /path/to/auto_reset_cron.sh

# กำหนด path
SCRIPT_DIR="/var/www/html/yuwa_que/v4"
LOG_DIR="/var/log/yuwa_queue"
LOG_FILE="$LOG_DIR/auto_reset.log"

# สร้าง log directory ถ้ายังไม่มี
mkdir -p "$LOG_DIR"

# เขียน log เริ่มต้น
echo "$(date '+%Y-%m-%d %H:%M:%S') - Starting Auto Reset Cron Job" >> "$LOG_FILE"

# เรียกใช้ API
curl -s -X GET "http://localhost/yuwa_que/v4/api/auto_reset_queue.php?cron=true" \
  -H "Content-Type: application/json" \
  >> "$LOG_FILE" 2>&1

# เขียน log สิ้นสุด
echo "$(date '+%Y-%m-%d %H:%M:%S') - Auto Reset Cron Job Completed" >> "$LOG_FILE"
echo "----------------------------------------" >> "$LOG_FILE"

# ลบ log เก่าที่เกิน 30 วัน
find "$LOG_DIR" -name "auto_reset.log" -mtime +30 -delete

exit 0
