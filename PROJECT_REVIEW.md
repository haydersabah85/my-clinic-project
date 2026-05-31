# Clinic Project Review

Reviewed on 2026-05-31. `patient-file2.php` was intentionally excluded.

## Best Improvements To Do First

1. Centralize page styling.
   Many pages define their own `<style>` blocks, including `dashboard.php`, `main.php`, `patient-file.php`, `visits.php`, appointment pages, reports, settings, and print pages. This makes the project harder to keep visually consistent. Start moving shared tokens, buttons, cards, tables, alerts, and form styles into `assets/clinic-ui.css`.

2. Make action links safer.
   Several important actions are still triggered by GET links or inline JavaScript confirmation, such as delete, archive, restore, confirm attendance, cancel attendance, mark critical, and delete appointment. Convert these gradually to POST forms with CSRF tokens.

3. Replace JavaScript alert redirects with flash messages.
   Many handler files output `alert(...)` and then redirect with JavaScript. A better pattern is:
   - set `$_SESSION['flash']`
   - call `header('Location: ...')`
   - render the message as a styled alert on the next page

4. Fix the shared sidebar structure.
   `sidebar.php` currently outputs a full HTML document. Shared include files should usually output only the reusable fragment, such as `<aside>...</aside>`, and leave `<html>`, `<head>`, and `<body>` to the page using it.

5. Reduce duplicate appointment code.
   Surgery, laser, and injection pages have very similar add/edit/delete/decision/discharge flows. A helper file like `clinic_appointments.php` could map appointment kinds to tables and shared operations.

6. Continue prepared statements everywhere.
   Many files already use prepared statements, which is good. A few action files still use direct SQL with ids. Prioritize `mark_critical.php`, dashboard queries that interpolate values in the future, and any delete/update page that receives an id.

7. Split very large pages.
   `patient-file.php` is very large and contains data loading, page layout, styles, and scripts together. It would benefit from gradual extraction:
   - `assets/patient-file.css`
   - `assets/patient-file.js`
   - small PHP partials for visits, VA, surgeries, laser, injections, prescriptions

8. Add a small UI convention for clinic workflows.
   Use consistent visual states:
   - green: saved/done/arrived
   - blue: primary navigation/edit
   - amber: pending/warning/follow-up
   - red: delete/cancel/danger
   - gray: neutral/back/archive

## Suggested New Files

I added these opt-in files:

- `assets/clinic-ui.css`
  A shared visual foundation for buttons, cards, tables, forms, status badges, flash messages, and responsive helpers.

- `assets/clinic-actions.js`
  A shared behavior helper for confirmation prompts and preventing double form submission.

You can include them page by page:

```html
<link rel="stylesheet" href="assets/clinic-ui.css">
<script src="assets/clinic-actions.js" defer></script>
```

Example safer delete button:

```html
<a class="clinic-btn clinic-btn-danger"
   href="delete-visit.php?id_delete=123"
   data-confirm="هل تريد حذف هذه الزيارة؟">
   حذف
</a>
```

Example double-submit protection:

```html
<form method="post" data-prevent-double-submit>
    <button class="clinic-btn clinic-btn-primary" data-loading-text="جاري الحفظ...">حفظ</button>
</form>
```

## Visual Notes

- Prefer `border-radius: 8px` for cards/buttons/tables unless the page already has a strong design reason.
- Avoid putting all page CSS inline. Keep only page-specific exceptions in the page.
- Use sticky table headers for long clinic tables.
- Wrap wide tables in a scroll container on mobile.
- Keep print pages separate from dark mode CSS so paper output stays predictable.
- Avoid using emoji as the only signal for important actions. Add text labels or `title`/`aria-label`.

## Functional Ideas For Clinic Work

- Add a daily work summary page: expected visits, arrived, not arrived, urgent patients, operations, follow-ups.
- Add patient duplicate detection by phone number/name similarity.
- Add a "missing data" queue: no phone, no age, no diagnosis, no latest visit.
- Add follow-up overdue alerts.
- Add operation readiness checklist: consent, lens/IOL, eye, payment/status, arrival, discharge.
- Add audit entries for attendance confirm/cancel, appointment delete, discharge, and prescription update.
- Add backup health indicator on dashboard: last backup time and whether online sync is pending.

