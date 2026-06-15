(function () {
    'use strict';

    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('appSidebarToggle');
    const backdrop = document.getElementById('appSidebarBackdrop');
    if (!sidebar || !toggle || !backdrop) return;

    function setSidebar(open, saveState) {
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.textContent = open ? '⬅️ إخفاء القائمة' : '➡️ القائمة';
        if (saveState) {
            localStorage.setItem('clinicSidebarState', open ? 'show' : 'hidden');
        }
    }

    setSidebar(localStorage.getItem('clinicSidebarState') === 'show', false);

    toggle.addEventListener('click', function () {
        setSidebar(!sidebar.classList.contains('is-open'), true);
    });

    backdrop.addEventListener('click', function () {
        setSidebar(false, true);
    });
})();
