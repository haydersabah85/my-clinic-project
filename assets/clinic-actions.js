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

    // Disabled controls are omitted from the submitted form data. Preserve the
    // clicked button's name/value before disabling it because several handlers
    // use that value to identify the intended action.
    if (submitter.name) {
      const submitterValue = document.createElement("input");
      submitterValue.type = "hidden";
      submitterValue.name = submitter.name;
      submitterValue.value = submitter.value;
      form.appendChild(submitterValue);
    }

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
