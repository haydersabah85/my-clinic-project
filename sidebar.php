  <?php
    include "config.php";
    ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Document</title>
      <link rel="stylesheet" href="assets/dark-mode.css">
      <script src="assets/theme.js" defer></script>
  </head>

  <style>
      /* ===== Sidebar ===== */
      .sidebar {
          width: 180px;
          background: var(--card);
          box-shadow: var(--shadow);
          padding: 20px;
          transition: .3s;
      }

      .sidebar.hidden {
          width: 0;
          padding: 0;
          overflow: hidden;
      }

      .sidebar h3 {
          color: var(--primary);
          margin-bottom: 20px;
          font-weight: bold;
          font-size: 24px;
      }

      .menu-group {
          margin-bottom: 12px;
          border: 1px solid rgba(148, 163, 184, .3);
          border-radius: 12px;
          overflow: hidden;
          background: rgba(37, 99, 235, .03);
      }

      .menu-title,
      .menu-group summary {
          display: flex;
          align-items: center;
          justify-content: space-between;
          width: 100%;
          gap: 10px;
          padding: 12px 14px;
          font-weight: 800;
          font-size: 15px;
          color: var(--text);
          background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(15, 118, 110, .08));
          border: 0;
          list-style: none;
          cursor: pointer;
      }

      .menu-group summary::-webkit-details-marker {
          display: none;
      }

      .menu-group summary::after {
          content: "▸";
          font-size: 13px;
          color: #64748b;
          transition: transform .2s ease;
      }

      .menu-group[open] summary::after {
          transform: rotate(90deg);
      }

      .menu-links {
          padding: 8px;
      }

      .menu-group a {
          display: block;
          padding: 10px 14px;
          border-radius: 10px;
          margin-bottom: 6px;
          text-decoration: none;
          color: var(--text);
          transition: .3s;
      }

      .menu-group a:hover {
          background: linear-gradient(135deg, var(--primary), var(--secondary));
          color: #fff;
          transform: translateX(-5px);
      }

      .menu-group a.danger:hover {
          background: linear-gradient(135deg, var(--danger), #ef4444);
      }

      body.dark .menu-group {
          border-color: rgba(96, 165, 250, .22);
          background: rgba(2, 6, 23, .55);
      }

      body.dark .menu-title,
      body.dark .menu-group summary {
          background: linear-gradient(135deg, rgba(96, 165, 250, .18), rgba(45, 212, 191, .12));
      }

      .toggle-sidebar {
          border: none;
          cursor: pointer;
          padding: 8px 16px;
          border-radius: 12px;
          font-size: 15px;
          font-weight: 700;
          background: linear-gradient(135deg, var(--primary), var(--secondary));
          color: #fff;
          box-shadow: var(--shadow);
          transition: .3s;
      }

      .toggle-sidebar:hover {
          transform: translateY(-2px);
      }
  </style>



  <body>




      <!-- ===== Sidebar ===== -->
      <aside class="sidebar hidden" id="sidebar">
          <h3>القائمة</h3>
          <div class="menu-group">
              <div class="menu-title">📊 الرئيسية</div>
              <div class="menu-links">
                  <a href="dashboard.php">لوحة التحكم</a>
              </div>
          </div>
          <details class="menu-group" data-menu-key="appointments" open>
              <summary>📅 المواعيد</summary>
              <div class="menu-links">
                  <a href="work-queue.php">قائمة عمل اليوم</a>
                  <a href="exam-requests.php">طلبات الفحوصات</a>
                  <a href="import_expected.php">استيراد المواعيد</a>
                  <a href="expected_appointments.php">المواعيد المتوقعة</a>
                  <a href="visits.php">زيارات اليوم</a>
                  <a href="operation-by-date.php">مواعيد العمليات</a>
              </div>
          </details>

          <details class="menu-group" data-menu-key="patients">
              <summary>👤 المرضى</summary>
              <div class="menu-links">
                  <a href="add-patient.php">إضافة مريض</a>
                  <a href="main.php">كل المرضى</a>
                  <a href="add-referred-case.php">إضافة حالة محولة</a>
                  <a href="referred-cases.php">الحالات المحولة</a>
                  <a href="archived-patients.php">أرشيف المرضى</a>
                  <a href="data-quality.php">جودة البيانات</a>
                  <a href="confirmed-list.php">قوائم العمليات</a>
              </div>
          </details>

          <details class="menu-group" data-menu-key="system">
              <summary>⚙️ النظام</summary>
              <div class="menu-links">
                  <a href="treatment-templates.php">قوالب العلاج</a>
                  <a href="audit-log.php">سجل العمليات</a>
                  <a href="settings.php">الإعدادات</a>
                  <a href="logout.php" class="danger">تسجيل الخروج</a>
              </div>
          </details>
      </aside>

      <script>
          function setupSidebarAccordion() {
              const groups = Array.from(document.querySelectorAll("#sidebar details.menu-group[data-menu-key]"));
              if (!groups.length) return;

              const storageKey = "shared_sidebar_open_group";
              const saved = localStorage.getItem(storageKey);
              const defaultGroup = groups.find(group => group.hasAttribute("open"));

              groups.forEach(group => {
                  group.open = false;
              });

              const initialGroup = groups.find(group => group.dataset.menuKey === saved) || defaultGroup || groups[0];
              if (initialGroup) initialGroup.open = true;

              groups.forEach(group => {
                  group.addEventListener("toggle", () => {
                      if (!group.open) return;
                      groups.forEach(other => {
                          if (other !== group) other.open = false;
                      });
                      if (group.dataset.menuKey) {
                          localStorage.setItem(storageKey, group.dataset.menuKey);
                      }
                  });
              });
          }

          function toggleSidebar() {
              const sidebar = document.getElementById("sidebar");
              const btn = document.querySelector(".toggle-sidebar");

              sidebar.classList.toggle("hidden");

              const hidden = sidebar.classList.contains("hidden");
              localStorage.setItem("sidebar", hidden ? "hidden" : "show");

              btn.textContent = hidden ? "➡️ إظهار القائمة" : "⬅️ إخفاء القائمة";
          }

          setupSidebarAccordion();
      </script>

  </body>

  </html>