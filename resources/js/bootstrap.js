import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function updateCsrfTokens(newToken) {
    if (!newToken) return;

    // Update meta tag
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        metaToken.setAttribute('content', newToken);
    }

    // Update Axios default header
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;

    // Update all hidden _token form inputs on the current page
    document.querySelectorAll('input[name="_token"]').forEach(input => {
        input.value = newToken;
    });
}

const initialCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (initialCsrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialCsrfToken;
}

/*
|--------------------------------------------------------------------------
| Session Keep-Alive & CSRF Token Auto-Refresh
|--------------------------------------------------------------------------
| Prevent Error 419 (Page Expired) by pinging /keep-alive periodically
| and whenever the user returns/focuses/wakes the browser tab (iOS & Android).
*/
let lastKeepAliveTime = Date.now();
let isSessionExpiredToastShown = false;

function showSessionExpiredBanner() {
    if (isSessionExpiredToastShown || document.getElementById('session-expired-warning')) return;
    isSessionExpiredToastShown = true;

    const banner = document.createElement('div');
    banner.id = 'session-expired-warning';
    banner.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[9999] max-w-lg w-[92%] text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-rose-500 flex items-center justify-between gap-3 text-xs sm:text-sm font-semibold';
    banner.style.backgroundColor = '#be123c';
    banner.innerHTML = `
        <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 shrink-0 text-rose-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span><strong>Sesi Berakhir:</strong> Data input Anda aman di halaman ini. Silakan <a href="/login" target="_blank" style="text-decoration: underline; font-weight: bold; color: #fef08a;">buka link ini untuk login kembali di tab baru</a>, lalu klik Simpan di sini.</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="background: rgba(255,255,255,0.25); border: none; color: white; padding: 4px 8px; border-radius: 6px; font-weight: bold; cursor: pointer; flex-shrink: 0;">Tutup</button>
    `;
    document.body.appendChild(banner);
}

async function pingKeepAlive() {
    const hasCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const isAuthPage = window.location.pathname.startsWith('/login') || window.location.pathname.startsWith('/register');
    if (!hasCsrf || isAuthPage) return;

    try {
        const response = await window.axios.get('/keep-alive', {
            headers: { 'Cache-Control': 'no-cache' }
        });
        if (response.data && response.data.csrf) {
            updateCsrfTokens(response.data.csrf);
            lastKeepAliveTime = Date.now();

            // If session was renewed (e.g. logged in from another tab), dismiss warning
            const warningEl = document.getElementById('session-expired-warning');
            if (warningEl) {
                warningEl.remove();
                isSessionExpiredToastShown = false;
            }
        }
    } catch (error) {
        const status = error.response ? error.response.status : 0;
        if (status === 401 || status === 419) {
            console.warn('Session expired during keep-alive ping.');
            showSessionExpiredBanner();
        }
    }
}

// Ping every 3 minutes (180,000 ms)
setInterval(pingKeepAlive, 3 * 60 * 1000);

// Ping when user returns to tab or wakes mobile browser from sleep
['visibilitychange', 'pageshow', 'focus'].forEach(eventType => {
    window.addEventListener(eventType, (event) => {
        if (eventType === 'visibilitychange' && document.visibilityState !== 'visible') return;
        if (eventType === 'pageshow' && event.persisted) {
            pingKeepAlive();
            return;
        }
        const elapsed = Date.now() - lastKeepAliveTime;
        if (elapsed > 60 * 1000) {
            pingKeepAlive();
        }
    });
});

// Handle Axios 419 / 401 response gracefully without losing unsaved input
window.axios.interceptors.response.use(
    response => response,
    async error => {
        const status = error.response ? error.response.status : 0;
        const isAuthPage = window.location.pathname.startsWith('/login') || window.location.pathname.startsWith('/register');
        if ((status === 419 || status === 401) && !isAuthPage) {
            console.warn('Session 419/401 detected.');
            showSessionExpiredBanner();
            // Don't wipe inputs if user has unsaved draft or active forms
            if (!window._hasUnsavedDraft) {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);
