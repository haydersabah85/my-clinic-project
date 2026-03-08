document.addEventListener("DOMContentLoaded", function () {

    const themeBtn = document.getElementById("themeToggle");
    const currentTheme = localStorage.getItem("theme");

    if (currentTheme === "dark") {
        document.body.setAttribute("data-theme", "dark");
        themeBtn.innerHTML = "☀️";
    }

    themeBtn.addEventListener("click", function () {
        if (document.body.getAttribute("data-theme") === "dark") {
            document.body.removeAttribute("data-theme");
            localStorage.setItem("theme", "light");
            themeBtn.innerHTML = "🌙";
        } else {
            document.body.setAttribute("data-theme", "dark");
            localStorage.setItem("theme", "dark");
            themeBtn.innerHTML = "☀️";
        }
    });
});
