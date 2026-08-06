// Alpine.js is automatically bundled and started by Livewire 4.


// Theme Manager
// The database (via window.SERVER_PREFS) is the single source of truth.
// On every full page load we trust the server preferences and re-sync
// localStorage from them, so the two can never diverge (e.g. after changing
// preferences on another device). We still persist to localStorage so that
// preference changes made in this session apply instantly without a reload.
(function() {
    const server = window.SERVER_PREFS || {};

    const defaults = {
        accent: 'copper',
        fontSize: 'medium',
        density: 'balanced',
        reducedMotion: 'false',
    };

    const prefs = {
        accent: server.accent ?? defaults.accent,
        fontSize: server.fontSize ?? defaults.fontSize,
        density: server.density ?? defaults.density,
        reducedMotion: server.reducedMotion ?? defaults.reducedMotion,
    };

    // Re-sync localStorage to match the server (source of truth).
    localStorage.setItem('pref_accent', prefs.accent);
    localStorage.setItem('pref_font_size', prefs.fontSize);
    localStorage.setItem('pref_density', prefs.density);
    localStorage.setItem('pref_reduced_motion', prefs.reducedMotion);

    document.documentElement.setAttribute('data-accent', prefs.accent);
    document.documentElement.setAttribute('data-font-size', prefs.fontSize);
    document.documentElement.setAttribute('data-ui-density', prefs.density);
    document.documentElement.setAttribute('data-reduced-motion', prefs.reducedMotion);
})();

// Apply preferences helper exposed to Livewire & Alpine
window.applyPreferences = function(prefs) {
    document.documentElement.setAttribute('data-accent', prefs.accent);
    document.documentElement.setAttribute('data-font-size', prefs.fontSize);
    document.documentElement.setAttribute('data-ui-density', prefs.density);
    document.documentElement.setAttribute('data-reduced-motion', prefs.reducedMotion ? 'true' : 'false');

    localStorage.setItem('pref_accent', prefs.accent);
    localStorage.setItem('pref_font_size', prefs.fontSize);
    localStorage.setItem('pref_density', prefs.density);
    localStorage.setItem('pref_reduced_motion', prefs.reducedMotion ? 'true' : 'false');
};

window.addEventListener('preferences-applied', (event) => {
    // Livewire wraps dispatch args in an array, so detail[0] is the payload
    const payload = Array.isArray(event.detail) ? event.detail[0] : event.detail;
    if (payload && payload.accent) {
        window.applyPreferences(payload);
    }
});

// Progress Bar for wire:navigate
const sidebarScrollStorageKey = 'sidebar-scroll-top';

function getSidebarScrollElement() {
    return document.querySelector('[data-sidebar-scroll]');
}

function saveSidebarScrollPosition() {
    const sidebar = getSidebarScrollElement();

    if (!sidebar) {
        return;
    }

    sessionStorage.setItem(sidebarScrollStorageKey, String(sidebar.scrollTop));
}

function restoreSidebarScrollPosition() {
    const sidebar = getSidebarScrollElement();

    if (!sidebar) {
        return;
    }

    const savedScrollTop = sessionStorage.getItem(sidebarScrollStorageKey);

    if (savedScrollTop === null) {
        return;
    }

    const scrollTop = Number(savedScrollTop);

    if (Number.isNaN(scrollTop)) {
        return;
    }

    sidebar.scrollTop = scrollTop;
}

function bindSidebarScrollPersistence() {
    const sidebar = getSidebarScrollElement();

    if (!sidebar) {
        return;
    }

    if (sidebar.dataset.scrollPersistenceBound !== 'true') {
        sidebar.dataset.scrollPersistenceBound = 'true';
        sidebar.addEventListener('scroll', saveSidebarScrollPosition, { passive: true });
    }

    restoreSidebarScrollPosition();
}

bindSidebarScrollPersistence();

document.addEventListener('livewire:navigate', () => {
    saveSidebarScrollPosition();

    const bar = document.getElementById('progress-bar');
    if (bar) {
        bar.style.width = '0%';
        bar.style.opacity = '1';
        let progress = 0;
        const reduced = document.documentElement.getAttribute('data-reduced-motion') === 'true';
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            bar.style.width = progress + '%';
        }, reduced ? 50 : 100);
        window._progressInterval = interval;
    }
});

document.addEventListener('livewire:navigate:finish', () => {
    const bar = document.getElementById('progress-bar');
    if (bar) {
        if (window._progressInterval) clearInterval(window._progressInterval);
        bar.style.width = '100%';
        const reduced = document.documentElement.getAttribute('data-reduced-motion') === 'true';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => { bar.style.width = '0%'; }, reduced ? 50 : 300);
        }, reduced ? 50 : 200);
    }

    // Re-apply theme from localStorage after every SPA navigation.
    // wire:navigate swaps parts of the DOM, which can wipe data-* attributes
    // that were set by JavaScript. Re-applying from localStorage is instant
    // and prevents any flash of wrong colours.
    const accent       = localStorage.getItem('pref_accent')       || 'copper';
    const fontSize     = localStorage.getItem('pref_font_size')    || 'medium';
    const density      = localStorage.getItem('pref_density')      || 'balanced';
    const reducedMotion = localStorage.getItem('pref_reduced_motion') || 'false';
    document.documentElement.setAttribute('data-accent',        accent);
    document.documentElement.setAttribute('data-font-size',     fontSize);
    document.documentElement.setAttribute('data-ui-density',    density);
    document.documentElement.setAttribute('data-reduced-motion', reducedMotion);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            bindSidebarScrollPersistence();
        });
    });
});

document.addEventListener('livewire:navigate:error', () => {
    const bar = document.getElementById('progress-bar');
    if (bar) {
        if (window._progressInterval) clearInterval(window._progressInterval);
        bar.style.opacity = '0';
        bar.style.width = '0%';
    }
});

// Connectivity Manager
(function() {
    const overlay = document.getElementById('offline-overlay');
    if (!overlay) return;

    let lastPingOk = true;

    function showOffline() {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }

    function hideOffline() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
    }

    async function pingServer() {
        try {
            const res = await fetch('/health/ping', { method: 'HEAD', cache: 'no-store' });
            return res.ok;
        } catch {
            return false;
        }
    }

    window.addEventListener('online', async () => {
        const ok = await pingServer();
        if (ok) {
            hideOffline();
            showToast('تمت استعادة الاتصال', 'success');
        }
    });

    window.addEventListener('offline', () => {
        showOffline();
    });

    setInterval(async () => {
        if (!navigator.onLine) return;
        const ok = await pingServer();
        if (!ok && lastPingOk) {
            showOffline();
        } else if (ok && !lastPingOk) {
            hideOffline();
            showToast('تمت استعادة الاتصال', 'success');
        }
        lastPingOk = ok;
    }, 30000);
})();

// Session Manager
(function() {
    window.addEventListener('livewire:request.error', (event) => {
        if (event.detail?.status === 419) {
            const modal = document.getElementById('session-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }
    });
})();

// Toast helper
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: 'bg-green-500',
        danger: 'bg-red-500',
        warning: 'bg-amber-500',
        info: 'bg-blue-500',
    };

    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${colors[type] || colors.info} transform transition-all duration-300 translate-y-[-10px] opacity-0`;
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-[-10px]', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    });

    setTimeout(() => {
        toast.classList.add('translate-y-[-10px]', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

window.showToast = showToast;
