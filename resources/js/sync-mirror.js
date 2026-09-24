/**
 * Register the PWA service worker and mirror pending sync count in IndexedDB.
 */
const DB_NAME = 'holy-health-sync';
const STORE = 'meta';

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE);
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function putMeta(key, value) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, 'readwrite');
        tx.objectStore(STORE).put(value, key);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function mirrorPendingFromDom() {
    const el = document.querySelector('[data-sync-pending]');
    if (! el) {
        return;
    }
    const pending = Number(el.textContent.trim() || '0');
    try {
        await putMeta('pending_count', pending);
        await putMeta('mirrored_at', new Date().toISOString());
    } catch {
        // IndexedDB may be unavailable in private mode — ignore.
    }
}

function registerServiceWorker() {
    if (! ('serviceWorker' in navigator)) {
        return;
    }

    const swUrl = document.body?.dataset?.swUrl || '/sw.js';
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(swUrl).catch(() => {
            // Offline or unsupported path — ignore.
        });
    });
}

registerServiceWorker();
mirrorPendingFromDom();
