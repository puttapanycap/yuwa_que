self.addEventListener('install', event => {
  // สามารถใส่ pre-cache ได้ แต่ไม่ใส่ก็ยัง “ติดตั้งเป็นแอป” ได้
  self.skipWaiting();
});
self.addEventListener('activate', event => {
  clients.claim();
});
// (ไม่ใส่ fetch ก็ได้; ถ้าต้องการ offline ค่อยทำ cache ตรงนี้)
