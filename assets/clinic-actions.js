(function () {
  function closestForm(element) {
    return element && element.closest ? element.closest("form") : null;
  }

  document.addEventListener("click", function (event) {
    const target = event.target.closest("[data-confirm]");
    if (!target) return;

    const message = target.getAttribute("data-confirm") || "Are you sure?";
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });

  document.addEventListener("submit", function (event) {
    const form = event.target;
    if (!form.matches("[data-prevent-double-submit]")) return;
    if (form.dataset.submitted === "true") {
      event.preventDefault();
      return;
    }

    form.dataset.submitted = "true";
    const submitter = event.submitter || form.querySelector("[type='submit']");
    if (!submitter) return;

    submitter.dataset.originalText = submitter.textContent;
    submitter.textContent = submitter.getAttribute("data-loading-text") || "Saving...";
    submitter.disabled = true;
  });

  document.addEventListener("click", function (event) {
    const trigger = event.target.closest("[data-submit-form]");
    if (!trigger) return;

    const form = closestForm(trigger);
    if (!form) return;

    event.preventDefault();
    form.requestSubmit();
  });
})();

