/**
 * Cross-tab data-sync helper for Numa Log.
 *
 * ใช้แจ้งเตือนแท็บอื่นเมื่อ idol / type / event เปลี่ยน เพื่อให้หน้า items.php
 * รีเฟรชดรอปดาวน์/ตัวกรองเองโดยไม่ต้อง reload
 *
 *   notifyDataChanged('idols')   // เรียกหลังบันทึก/ลบ entity สำเร็จ
 *   onDataChanged(kind => {...}) // ลงทะเบียน callback ที่ทำงานในแท็บอื่น
 *
 * BroadcastChannel ให้การอัพเดททันที; localStorage 'storage' event เป็น fallback
 * สำหรับเบราว์เซอร์ที่ไม่มี BroadcastChannel ทั้งสองช่องทางยิงเฉพาะ "แท็บอื่น"
 * ไม่ยิงกลับแท็บที่ส่ง จึงไม่เกิด loop
 */
(function () {
    const CHANNEL = 'numa-sync';
    const LS_KEY = 'numa-sync-ping';
    let bc = null;
    try { bc = ('BroadcastChannel' in window) ? new BroadcastChannel(CHANNEL) : null; } catch (_) { /* ignore */ }

    // kind = 'idols' | 'types' | 'events'
    window.notifyDataChanged = function (kind) {
        const msg = { kind, ts: Date.now() };
        if (bc) { try { bc.postMessage(msg); } catch (_) { /* ignore */ } }
        try { localStorage.setItem(LS_KEY, JSON.stringify(msg)); } catch (_) { /* ignore */ }
    };

    window.onDataChanged = function (cb) {
        if (bc) bc.onmessage = (e) => { if (e.data && e.data.kind) cb(e.data.kind); };
        window.addEventListener('storage', (e) => {
            if (e.key === LS_KEY && e.newValue) {
                try { const m = JSON.parse(e.newValue); if (m && m.kind) cb(m.kind); } catch (_) { /* ignore */ }
            }
        });
    };
})();
