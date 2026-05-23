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
