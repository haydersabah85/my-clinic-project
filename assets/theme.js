// Load the shared language toggle on every page that includes theme.js.
(function () {
    if (document.querySelector('script[data-clinic-lang]')) return;

    var s = document.createElement('script');
    s.dataset.clinicLang = 'true';
    s.defer = true;
    var base = (document.currentScript && document.currentScript.src)
        ? document.currentScript.src.replace(/theme\.js(\?.*)?$/, '')
        : 'assets/';
    s.src = base + 'lang.js?v=20260615-11';
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
