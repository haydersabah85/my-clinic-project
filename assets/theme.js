// Load the shared language toggle on every page that includes theme.js.
(function () {
    if (window.__clinicLanguageLoaded) return;
    if (document.querySelector('script[data-clinic-lang], script[src*="assets/lang.js"], script[src$="/lang.js"]')) return;

    var s = document.createElement('script');
    s.dataset.clinicLang = 'true';
    s.defer = true;
    var currentSrc = (document.currentScript && document.currentScript.src)
        ? document.currentScript.src
        : '';
    var base = currentSrc
        ? currentSrc.replace(/theme\.js(\?.*)?$/, '')
        : 'assets/';
    var versionMatch = currentSrc.match(/[?&]v=([^&#]+)/);
    var versionQuery = versionMatch ? '?v=' + encodeURIComponent(versionMatch[1]) : '';
    s.src = base + 'lang.js' + versionQuery;
    document.head.appendChild(s);
})();

document.addEventListener("DOMContentLoaded", function () {

    const themeBtn = document.getElementById("themeToggle");
    const currentTheme = localStorage.getItem("theme");

    if (currentTheme === "dark") {
        document.body.setAttribute("data-theme", "dark");
        document.body.classList.add("dark");
        if (themeBtn) themeBtn.innerHTML = "☀️";
    } else {
        document.body.classList.remove("dark");
        if (themeBtn) themeBtn.innerHTML = "🌙";
    }

    if (!themeBtn) return;

    themeBtn.addEventListener("click", function () {
        if (document.body.getAttribute("data-theme") === "dark") {
            document.body.removeAttribute("data-theme");
            document.body.classList.remove("dark");
            localStorage.setItem("theme", "light");
            themeBtn.innerHTML = "🌙";
        } else {
            document.body.setAttribute("data-theme", "dark");
            document.body.classList.add("dark");
            localStorage.setItem("theme", "dark");
            themeBtn.innerHTML = "☀️";
        }
    });
});
