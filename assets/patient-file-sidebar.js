(function () {
    'use strict';

    const sidebar = document.getElementById('appSidebar');
    const toggle = document.getElementById('appSidebarToggle');
    const backdrop = document.getElementById('appSidebarBackdrop');
    if (!sidebar || !toggle || !backdrop) return;

    const groups = Array.from(sidebar.querySelectorAll('details.menu-group[data-menu-key]'));

    function setupSidebarAccordion() {
        if (!groups.length) return;

        const storageKey = 'patient_file_sidebar_open_group';
        const saved = localStorage.getItem(storageKey);
        const defaultGroup = groups.find(function (group) {
            return group.hasAttribute('open');
        });

        groups.forEach(function (group) {
            group.open = false;
        });

        const initialGroup = groups.find(function (group) {
            return group.dataset.menuKey === saved;
        }) || defaultGroup || groups[0];

        if (initialGroup) {
            initialGroup.open = true;
        }

        groups.forEach(function (group) {
            group.addEventListener('toggle', function () {
                if (!group.open) return;

                groups.forEach(function (other) {
                    if (other !== group) {
                        other.open = false;
                    }
                });

                if (group.dataset.menuKey) {
                    localStorage.setItem(storageKey, group.dataset.menuKey);
                }
            });
        });
    }

    function markCurrentSidebarLink() {
        const currentPath = window.location.pathname.split('/').pop().toLowerCase();
        const links = Array.from(sidebar.querySelectorAll('a[href]'));

        links.forEach(function (link) {
            const href = (link.getAttribute('href') || '').split('?')[0].toLowerCase();
            if (!href) return;
            if (href === currentPath) {
                link.classList.add('is-current');
            }
        });
    }

    function setSidebar(open, saveState) {
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        document.body.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', 'فتح القائمة');
        toggle.textContent = '☰ القائمة';
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

    setupSidebarAccordion();
    markCurrentSidebarLink();
})();
