// Central UI utilities: toaster, user menu toggle, and dark-mode toggle
(function(){
    // Toaster
    function ensureToaster(){
        var t = document.getElementById('toaster');
        if (!t) {
            t = document.createElement('div');
            t.id = 'toaster';
            t.style.position = 'fixed';
            t.style.right = '1rem';
            t.style.top = '1rem';
            t.style.zIndex = 9999;
            t.style.display = 'flex';
            t.style.flexDirection = 'column';
            t.style.gap = '0.5rem';
            document.body.appendChild(t);
        }
        return t;
    }

    function showToast(message, type){
        var t = ensureToaster();
        var el = document.createElement('div');
        el.className = 'rounded-md px-4 py-2 shadow-md animate-slideIn';
        el.style.maxWidth = '320px';
        el.style.color = '#fff';
        el.style.fontSize = '0.95rem';
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'space-between';

        var bg = '#111827';
        if (type === 'success') bg = '#16a34a';
        else if (type === 'error') bg = '#dc2626';
        else if (type === 'warning') bg = '#f59e0b';
        else bg = '#111827';

        el.style.background = bg;

        var span = document.createElement('span');
        span.style.flex = '1';
        span.style.paddingRight = '0.5rem';
        span.textContent = message;

        var close = document.createElement('button');
        close.textContent = '✕';
        close.style.background = 'transparent';
        close.style.border = 'none';
        close.style.color = 'rgba(255,255,255,0.9)';
        close.style.cursor = 'pointer';
        close.style.fontSize = '0.9rem';
        close.setAttribute('aria-label','dismiss toast');

        close.addEventListener('click', function(){
            el.remove();
        });

        el.appendChild(span);
        el.appendChild(close);
        t.appendChild(el);

        // Auto remove after 4s
        setTimeout(function(){
            if (el.parentNode) el.remove();
        }, 4000);
    }

    // expose globally
    window.showToast = showToast;

    // User menu toggle (delegated init)
    function initUserMenu(){
        var btn = document.getElementById('user-menu-button');
        var menu = document.getElementById('user-menu');
        if (!btn || !menu) return;

        function openMenu(){ menu.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
        function closeMenu(){ menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
        function toggleMenu(e){ e.stopPropagation(); if (menu.classList.contains('hidden')) openMenu(); else closeMenu(); }

        btn.addEventListener('click', toggleMenu);
        document.addEventListener('click', function(e){ if (!menu.contains(e.target) && e.target !== btn) closeMenu(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeMenu(); });
    }

    // Dark mode support
    var THEME_KEY = 'tf_theme'; // 'dark' or 'light'
    function applyTheme(theme){
        if (theme === 'dark') document.documentElement.classList.add('dark');
        else document.documentElement.classList.remove('dark');
        // update toggle button if present
        var tbtn = document.getElementById('theme-toggle');
        if (tbtn) tbtn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }

    function initTheme(){
        var stored = null;
        try { stored = localStorage.getItem(THEME_KEY); } catch(e){}
        var theme = stored || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        applyTheme(theme);

        var tbtn = document.getElementById('theme-toggle');
        if (tbtn){
            tbtn.addEventListener('click', function(e){
                var cur = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                var next = cur === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(THEME_KEY, next); } catch(e){}
                applyTheme(next);
                // show a short toast
                showToast('Switched to ' + (next === 'dark' ? 'Dark' : 'Light') + ' mode', 'success');
            });
        }
    }

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function(){
        initUserMenu();
        initTheme();
        // hook up auto-toast for flash messages rendered server-side into window.__FLASH__
        if (window.__FLASH__ && window.__FLASH__.length){
            window.__FLASH__.forEach(function(f){ showToast(f.message, f.type || 'success'); });
        }
    });

})();
