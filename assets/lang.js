/**
 * lang.js — Language Toggle (Arabic ↔ English)
 * Works across all pages that load theme.js or include this file directly.
 * Persists choice in localStorage under the key "lang" ('ar' | 'en').
 * Switching to Arabic reloads the page (restores server-rendered Arabic).
 * Switching to English walks DOM text nodes and replaces known strings.
 */
(function () {
    'use strict';

    if (window.__clinicLanguageLoaded) return;
    window.__clinicLanguageLoaded = true;

    // =========================================================
    // TRANSLATION DICTIONARY  — Arabic → English
    // Sorted longest-first at runtime to prevent partial overlaps.
    // =========================================================
    const DICT = {
        // --- Page Titles ---
        'القوائم المؤكدة': 'Confirmed Lists',
        'لوحة التحكم': 'Dashboard',
        'ملخص التنبيهات': 'Alerts Summary',
        'متابعات الغد': 'Tomorrow Follow-ups',
        'تنبيه السكرتيرة: متابعات الغد': 'Secretary Alert: Tomorrow Follow-ups',
        'فتح متابعات الغد': 'Open Tomorrow Follow-ups',
        'تعارضات مزامنة': 'Sync Conflicts',
        'صور تنتظر المزامنة': 'Images Pending Sync',
        'عمليات خلال 5 أيام': 'Operations in 5 Days',
        'عمليات متأخرة': 'Late Operations',
        'حالات حرجة': 'Critical Cases',
        'لا توجد نسخة احتياطية محلية حديثة': 'No recent local backup was found',
        'حالات الانتظار أعلى من المعاينات المنجزة اليوم': 'Waiting visits are higher than completed visits today',
        'آخر نسخة احتياطية قبل': 'Last backup was',
        'ساعة': 'hours',
        'يوجد متابعة ليوم الغد': 'There are follow-ups for tomorrow',
        'لا توجد متابعات مسجلة ليوم الغد': 'No follow-ups are scheduled for tomorrow',
        'يوجد صورة بانتظار المزامنة': 'There are images pending sync',
        'بيانات المرضى النشطين': 'Active Patient Data',
        'مواعيد العمليات والليزر والحقن': 'Surgery, Laser & Injection Appointments',
        'مرتبة حسب القسم، ومقسمة داخل كل قسم حسب النوع.': 'Sorted by section, subdivided by type.',
        'بحث سريع، تصفية حسب حالة آخر عملية، وروابط مباشرة لفتح ملف المريض أو جدول المتابعة.':
            'Quick search, filter by last operation status, and direct links to the patient file or follow-up schedule.',
        'نبذة مختصرة عن النشاط اليومي والشهري للعيادة من زيارات المرضى والمواعيد والعمليات.':
            'A brief overview of daily and monthly clinic activity, including visits, appointments, and operations.',
        'هل تريد إلغاء تأكيد هذا المريض؟': 'Do you want to undo confirmation for this patient?',
        'هل أنت متأكد من حذف هذا المريض؟': 'Are you sure you want to delete this patient?',
        'عيادة الدكتور حيدر صباح الربيعي': 'Dr. Haider Sabah Al-Rubaie Clinic',
        'ملخص القوائم المؤكدة': 'Confirmed Lists Summary',
        'روابط الصفحة': 'Page Links',
        'قائمة العمليات': 'Operation List',
        'نظرة سريعة على حركة العيادة': 'Clinic Activity Overview',
        'آخر نشاط العيادة': 'Latest Clinic Activity',
        'ملخص اليوم': "Today's Summary",
        'جدول العمليات القادمة': 'Upcoming Operations Schedule',
        'حسب جدول العمليات المنجزة': 'Based on the Completed Operations Schedule',
        'المراجعات + الزيارات المتوقعة اليوم': "Today's Follow-ups and Expected Visits",
        'العودة إلى الإعدادات': 'Back to Settings',
        'العودة إلى بيانات المرضى': 'Back to Patient Data',
        'العودة إلى الصفحة الرئيسية': 'Back to Home',
        'العودة إلى الرئيسية': 'Back to Home',
        'غير متاح على النسخة السحابية': 'Not available on the cloud version',
        'لم يتم العثور على المريض المطلوب.': 'The requested patient was not found.',
        'لم يتم العثور على الموعد المطلوب.': 'The requested appointment was not found.',
        'المريض غير موجود أو مؤرشف': 'Patient not found or archived',
        'المريض غير موجود.': 'Patient not found.',
        'المريض غير موجود': 'Patient not found',
        'بيانات الدخول غير صحيحة': 'Incorrect login details',
        'مراجعات الأسبوع': 'Weekly Follow-ups',
        'مواعيد المراجعة': 'Follow-up Appointments',
        'كل المواعيد': 'All Appointments',
        'لا توجد مراجعات حسب التصفية الحالية': 'No follow-ups match the current filters',
        'هل أنت متأكد من حذف هذه المراجعة؟': 'Are you sure you want to delete this follow-up?',
        'السبب:': 'Reason:',
        '✔ تم': 'Done',
        '❌ حذف': 'Delete',
        'لوحة زيارات اليوم': "Today's Visits Dashboard",
        'تنقل سريع لإدارة الزيارات والإجراءات': 'Quick navigation for visits and procedures',
        'إدخال الإجراءات': 'Enter Procedures',
        'إجمالي الزيارات': 'Total Visits',
        'ملخص الإيراد لليوم:': "Today's Revenue Summary:",
        'مستحقات الخدمة:': 'Service Due:',
        'الصافي:': 'Net:',
        'فتح شاشة الإيراد': 'Open Revenue Screen',
        'المريض القادم الآن:': 'Next Patient Now:',
        'فلترة حالة الزيارة': 'Filter Visit Status',
        'اخر زيارة': 'Last Visit',
        'التسلسل:': 'Sequence:',
        'تعديل الزيارة': 'Edit Visit',
        'حذف الزيارة': 'Delete Visit',
        'تنبيه الطبيب بالمريض القادم': 'Notify Doctor About Next Patient',
        'لوحة التنقل السريع': 'Quick Navigation Panel',
        'وصول مباشر لأهم صفحات النظام اليومية': 'Direct access to key daily system pages',
        'ملخص سريع': 'Quick Summary',
        'حالة الداشبورد': 'Dashboard Status',
        'سجل التدقيق': 'Audit Log',
        'الإعدادات السريعة': 'Quick Settings',
        'الوضع الداكن': 'Dark Mode',
        'تقارير شاملة': 'Comprehensive Reports',
        'تقارير شاملة وتحليلات': 'Comprehensive Reports and Analytics',
        'تقارير العيادة الشاملة': 'Comprehensive Clinic Reports',
        'عرض موحد وتفاعلي لكل التقارير المهمة: زيارات، إجراءات، عمليات، ليزر، إبر، مواعيد، ومتابعات حسب الفترة المختارة.':
            'A unified interactive view of key reports: visits, procedures, operations, laser, injections, appointments, and follow-ups by selected period.',
        'بحث بالمريض': 'Search by Patient',
        'اسم / هاتف / رقم': 'Name / Phone / Number',
        'القسم الافتراضي': 'Default Section',
        'الزيارات اليومية التاريخية': 'Historical Daily Visits',
        'لا توجد زيارات ضمن الفلاتر.': 'No visits found under current filters.',
        'تفصيل الزيارات': 'Visits Breakdown',
        'منجزة': 'Completed',
        'مرضى مختلفون:': 'Distinct Patients:',
        'اتجاه الزيارات': 'Visit Trend',
        'لا توجد بيانات اتجاه.': 'No trend data available.',
        'تقارير الزيارات': 'Visits Reports',
        'تقارير الإجراءات': 'Procedures Reports',
        'عمليات / ليزر / إبر': 'Operations / Laser / Injections',
        'المتابعات والمواعيد': 'Follow-ups and Appointments',
        'تقارير الإجراءات (شبكية / ليزر / أخرى)': 'Procedures Reports (Retina / Laser / Other)',
        'المواعيد والإجراءات': 'Appointments and Procedures',
        'تحديث التقرير': 'Update Report',
        'إعادة الضبط': 'Reset Filters',
        'تصدير زيارات CSV': 'Export Visits CSV',
        'تصدير إجراءات CSV': 'Export Procedures CSV',
        'تصدير الحالات المحولة CSV': 'Export Referred Cases CSV',
        'تقارير الإجراءات (شبكية / ليزر / أخرى)': 'Procedures Reports (Retina / Laser / Other)',
        'الحالات المحولة': 'Referred Cases',
        'إجمالي الإجراءات': 'Total Procedures',
        'إيراد الإجراءات': 'Procedures Revenue',
        'مواعيد العمليات': 'Surgery Appointments',
        'المراجعات': 'Follow-ups',
        'إضافة حالة محولة': 'Add Referred Case',
        'إضافة حالة عملية محولة': 'Add Referred Surgery Case',
        'تعديل حالة محولة': 'Edit Referred Case',
        'تعديل الحالة المحولة': 'Edit Referred Case',
        'حفظ الحالة المحولة': 'Save Referred Case',
        'قائمة الحالات المحولة': 'Referred Cases List',
        'الحالات المحولة للعمليات': 'Referred Cases for Surgeries',
        'إجمالي الحالات المحولة': 'Total Referred Cases',
        'ملخص الحالات المحولة': 'Referred Cases Summary',
        'فتح قائمة الحالات المحولة': 'Open Referred Cases List',
        'آخر الحالات المحولة ضمن الفترة': 'Latest Referred Cases in Period',
        'لا توجد حالات محولة ضمن الفترة.': 'No referred cases in this period.',
        '🏠 رئيسية': '🏠 Home',
        '👤 المرضى': '👤 Patients',
        '📅 المواعيد': '📅 Appointments',
        '💉 العمليات': '💉 Operations',
        '⚡ إجراءات سريعة': '⚡ Quick Actions',
        '⚙️ النظام': '⚙️ System',
        '⬅️ القائمة': '⬅️ Menu',
        '🔍 ابحث عن مريض...': '🔍 Search for a patient...',
        'ملخص اليوم التشغيلي': 'Operational Summary Today',
        'مقارنة مباشرة مع أمس': 'Direct comparison with yesterday',
        'تمت': 'Done',
        'انتظار': 'Pending',
        'ضغط اليوم': 'Today Pressure',
        'نسبة قيد الانتظار من إجمالي زيارات اليوم': 'Pending rate from today\'s total visits',
        'مرتفع': 'High',
        'متوسط': 'Medium',
        'منخفض': 'Low',
        'المعاينات المنجزة': 'Completed Examinations',
        'نسبة الإنجاز:': 'Completion Rate:',
        'الهدف: إبقاءها أقل من 25% من الزيارات': 'Target: Keep it below 25% of visits',
        'المواعيد المتوقعة اليوم': 'Expected Appointments Today',
        'ملخص هذا الشهر': 'This Month Summary',
        'مقارنة مع الشهر الماضي': 'Compared with Last Month',
        'الإجمالي:': 'Total:',
        'لا توجد تنبيهات حالياً': 'There are currently no alerts',
        '🚨 المرضى الحرِجون': '🚨 Critical Patients',
        '| القسم:': '| Section:',
        '| تفاصيل:': '| Details:',
        '| أرسلها:': '| Sent by:',
        '| الوقت:': '| Time:',
        'العيادة': 'Clinic',

        // --- Navigation / Menu ---
        'الصفحة الرئيسية': 'Home',
        'قائمة عمل اليوم': "Today's Work Queue",
        'إعطاء موعد مراجعة': 'Schedule Follow-up',
        'مواعيد العمليات': 'Surgery Appointments',
        'عرض العمليات حسب التاريخ': 'View Operations by Date',
        'أرشيف المرضى': 'Archived Patients',
        'جودة البيانات': 'Data Quality',
        'زيارات اليوم': "Today's Visits",
        'إضافة مريض': 'Add Patient',
        'بيانات المرضى': 'Patient Data',
        'قوائم العمليات': 'Operation Lists',
        'استيراد العمليات': 'Import Operations',
        'الأدوية الأكثر استعمالا': 'Common Medications',
        'الأدوية الأكثر استعمالًا': 'Common Medications',
        'تسجيل الخروج': 'Logout',
        'إعدادات النظام': 'System Settings',
        'سجل المرضى': 'Patient Registry',
        'أدوات المرضى': 'Patient Tools',
        'قائمة المرضى': 'Patient List',
        'المرضى حسب رقم التسجيل': 'Patients by Registration No.',
        'ملخص المرضى': 'Patient Summary',
        'إجمالي المرضى': 'Total Patients',
        'المرضى الذين لديهم عمليات قادمة': 'Patients With Upcoming Operations',
        'المريض القادم للطبيب': 'Next Patient for the Doctor',
        'الزيارات المتوقعة اليوم': "Today's Expected Visits",
        'المواعيد المتوقعة': 'Expected Appointments',
        'العمليات القادمة': 'Upcoming Operations',
        'العمليات المنجزة هذا الشهر': 'Operations Completed This Month',
        'قوالب العلاج': 'Treatment Templates',
        'سجل العمليات': 'Operation Log',
        'إدارة التعارضات': 'Manage Conflicts',
        'استيراد المواعيد': 'Import Appointments',
        'حالات حرجة': 'Critical Cases',
        'المرضى الحرجون': 'Critical Patients',
        'الإحصائيات': 'Statistics',
        'إحصائيات': 'Statistics',
        'لا توجد مواعيد مؤكدة لهذا القسم في التاريخ المحدد':
            'No confirmed appointments for this section on the selected date',
        'لا توجد نتائج مطابقة للبحث أو التصفية الحالية.':
            'No results match the current search or filter.',
        'لا توجد تنبيهات حاليا': 'There are currently no alerts',
        'لا توجد عمليات خلال الفترة': 'No operations during this period',
        'لا توجد زيارات خلال الفترة': 'No visits during this period',
        'لا توجد حقن خلال الفترة': 'No injections during this period',
        'ابحث بالاسم، الهاتف، العنوان، الملاحظات...':
            'Search by name, phone, address, notes…',
        'ابحث عن مريض...': 'Search for a patient…',
        'روابط سريعة': 'Quick Links',
        'كل المرضى': 'All Patients',
        'الملف الكامل': 'Full File',
        'القائمة الجانبية': 'Sidebar',
        'التسلسل الزمني': 'Timeline',
        'التسلسل السريري': 'Clinical Timeline',
        'التقارير الطبية': 'Medical Reports',
        'الإجراءات الطبية': 'Medical Procedures',
        'مقارنة الصور': 'Compare Images',
        'آخر مواعيد المتابعة': 'Latest Follow-up Appointments',
        'آخر زيارة': 'Last Visit',
        'اخر زيارة': 'Last Visit',
        'الزيارات': 'Visits',
        'إجمالي الزيارات': 'Total Visits',
        'تقرير الزيارات': 'Visits Report',
        'تقرير العمليات': 'Operations Report',
        'العمليات المنجزة': 'Completed Operations',
        'بيانات المريض': 'Patient Information',
        'ملف مريض': 'Patient File',
        'ملف المريض رقم': 'Patient File No.',
        'فلترة التسلسل': 'Filter Timeline',
        'عرض الصور': 'View Images',
        'عرض الزيارات مجمعة حسب التاريخ': 'Show Visits Grouped by Date',
        'فلترة حالة الزيارة': 'Filter Visit Status',
        'لا توجد زيارات مطابقة لهذا الفلتر': 'No visits match this filter',
        'لا توجد زيارة سابقة': 'No Previous Visit',
        'المريض القادم الآن': 'Next Patient Now',

        // --- Sidebar Sections ---
        'المواعيد': 'Appointments',
        'العمليات': 'Operations',
        'العملية': 'Operation',
        'المتابعة': 'Follow-ups',
        'الإعدادات': 'Settings',
        'التقارير': 'Reports',
        'المرضى': 'Patients',
        'النظام': 'System',
        'الأرشيف': 'Archive',
        'القائمة': 'Menu',

        // --- Common Table / Card Headers ---
        'اسم المريض': 'Patient Name',
        'الاسم الكامل': 'Full Name',
        'رقم التسجيل': 'Registration No.',
        'تاريخ الميلاد': 'Date of Birth',
        'رقم التلفون': 'Phone Number',
        'الاسم': 'Name',
        'هاتف بديل': 'Alt. Phone',
        'ملاحظات بعد الإجراء': 'Post-op Notes',
        'الرقم التسلسلي': 'Serial No.',
        'تاريخ القائمة': 'List Date',
        'الملاحظات': 'Notes',
        'الإجراءات': 'Actions',
        'الإجراء': 'Action',
        'إجراء': 'Action',
        'الحالة': 'Status',
        'التاريخ': 'Date',
        'العنوان': 'Address',
        'الهاتف': 'Phone',
        'الجنس': 'Gender',
        'العمر': 'Age',
        'العين': 'Eye',
        'النوع': 'Type',
        'ملاحظة': 'Note',
        'القسم': 'Section',
        'الوقت': 'Time',
        'تفاصيل': 'Details',
        'المجموع': 'Total',
        'ملاحظات': 'Notes',
        'ملاحظات إضافية': 'Additional Notes',
        'نوع الحقن': 'Injection Type',
        'نوع العملية': 'Surgery Type',
        'نوع الليزر': 'Laser Type',
        'نوع العدسة': 'Lens Type',
        'اسم المستخدم': 'Username',
        'كلمة المرور': 'Password',
        'الاسم الرباعي': 'Full Name',
        'موبايل بديل': 'Alternative Mobile',
        'الموبايل البديل': 'Alternative Mobile',
        'التأريخ': 'Date',
        'التسلسل': 'Sequence',
        'بواسطة': 'By',
        'تاريخ الموعد': 'Appointment Date',
        'نوع الجلسة': 'Session Type',
        'نوع الحقنة': 'Injection Type',
        'الجلسة': 'Session',
        'الحقنة': 'Injection',
        'التوقيت': 'Timestamp',
        'الاتجاه': 'Direction',
        'الجدول / المفتاح': 'Table / Key',
        'المفتوح': 'Open',
        'المحلول': 'Resolved',
        'اسم الدواء': 'Medication Name',
        'رقم الموبايل': 'Mobile Number',
        'تاريخ الزيارة': 'Visit Date',
        'الرقم التسلسلي': 'Serial Number',
        'الموبايل': 'Mobile',
        'ملاحظات الزيارة': 'Visit Notes',
        'رسومات الشبكية': 'Retina Drawings',
        'نوع الزيارة': 'Visit Type',
        'حالة الزيارة': 'Visit Status',
        'آخر زيارة:': 'Last Visit:',

        // --- Operation Types ---
        'الليزر': 'Laser',
        'الحقن': 'Injections',
        'عملية': 'Surgery',
        'ليزر': 'Laser',
        'حقن': 'Injection',

        // --- Status Labels ---
        'قيد الانتظار': 'Pending',
        'بدون عملية': 'No Operation',
        'بدون هاتف': 'No Phone',
        'منجز': 'Done',
        'مغادر': 'Discharged',
        'حاضر': 'Present',
        'غائب': 'Absent',
        'وصل': 'Arrived',
        'لم يصل': 'Not Arrived',
        'متأخر': 'Late',
        'متأخرة': 'Overdue',
        'قريبة': 'Upcoming',
        'غير محدد': 'Not specified',
        'لا يوجد': 'None',
        'الكل': 'All',
        'ذكر': 'Male',
        'أنثى': 'Female',

        // --- Buttons & Actions ---
        'إلغاء التأكيد': 'Undo Confirmation',
        'تأكيد الحضور': 'Confirm Attendance',
        'تعديل الموعد': 'Edit Appointment',
        'فتح ملف المريض': 'Open Patient File',
        'إخفاء القائمة': 'Hide Menu',
        'إظهار القائمة': 'Show Menu',
        'عرض الكل': 'View All',
        'زر الحالات الحرجة': 'Critical Cases Button',
        'إعادة البحث لتحديث النتائج': 'Search again to refresh results',
        'تم استدعاؤه / مسح التنبيه': 'Called / Clear Alert',
        'إضافة مريض جديد': 'Add New Patient',
        'إضافة زيارة': 'Add Visit',
        'تعديل الزيارة': 'Edit Visit',
        'حذف الزيارة': 'Delete Visit',
        'ملف المريض': 'Patient File',
        'تسجيل الدخول': 'Login',
        'ادخل بحسابك للوصول إلى لوحة التحكم وإدارة ملفات العيادة.': 'Sign in to access the dashboard and manage clinic files.',
        'نظام العيادة الذكي': 'Smart Clinic System',
        'حسابات الموظفين، بصلاحيات واضحة ومظهر احترافي': 'Staff Accounts with Clear Permissions and a Professional Look',
        'يمكنك إنشاء حسابات للموظفين من صفحة التسجيل، ثم تحديد دور كل مستخدم وصلاحياته العملية قبل منحه الوصول إلى أجزاء النظام المناسبة له.': 'You can create staff accounts from the registration page, then define each user role and operational permissions before granting access to relevant system sections.',
        'إضافة موظفين بسرعة': 'Quick Staff Onboarding',
        'ربط مباشر مع صفحة التسجيل لإدارة الحسابات من مكان واحد.': 'Direct link to the registration page to manage accounts in one place.',
        'صلاحيات مرنة': 'Flexible Permissions',
        'تحديد ما إذا كان المستخدم يدير المرضى أو المواعيد أو التقارير أو الحسابات.': 'Choose whether the user manages patients, appointments, reports, or accounts.',
        'واجهة أنيقة ومناسبة للموبايل': 'Elegant, Mobile-Friendly UI',
        'تصميم متجاوب بلمسة حديثة ووضع ليلي متناسق.': 'Responsive design with a modern touch and consistent dark mode.',
        'فتح صفحة إنشاء حساب موظف': 'Open Staff Registration Page',
        'إضافة': 'Add',
        'تعديل': 'Edit',
        'حذف': 'Delete',
        'إلغاء': 'Cancel',
        'تأكيد': 'Confirm',
        'حفظ': 'Save',
        'بحث': 'Search',
        'طباعة': 'Print',
        'تصدير': 'Export',
        'استيراد': 'Import',
        'عودة': 'Back',
        'عرض': 'View',
        'إغلاق': 'Close',
        'فتح': 'Open',
        'اختر': 'Choose',
        'اختيار': 'Select',
        'تحديث': 'Update',
        'إنشاء': 'Create',
        'رفع': 'Upload',
        'مسح': 'Clear',
        'تخطي': 'Skip',
        'اكتب': 'Enter',
        'تبديل الوضع': 'Toggle Theme',
        'عرض التفاصيل': 'Show Details',
        'إخفاء التفاصيل': 'Hide Details',
        'إعطاء موعد': 'Schedule Appointment',
        'استرجاع': 'Restore',
        'استعادة النسخة الاحتياطية': 'Restore Backup',
        'خروج': 'Exit',
        'رجوع': 'Back',
        'تنبيه الطبيب': 'Notify Doctor',
        'إضافة متابعة': 'Add Follow-up',
        'إضافة مستخدم': 'Add User',
        'إضافة مستخدم جديد': 'Add New User',
        'إعادة': 'Reset',
        'اختر نسخة': 'Choose a Backup',
        'اختر الصلاحية': 'Choose Role',
        'إغلاق يدوي': 'Close Manually',
        'اعتماد السحابة': 'Use Cloud Version',
        'اعتماد المحلي': 'Use Local Version',
        'تبديل المظهر': 'Toggle Theme',
        'تعديل البيانات': 'Edit Information',
        'تعديل الحقن': 'Edit Injection',
        'تعديل العملية': 'Edit Surgery',
        'تعديل الليزر': 'Edit Laser',
        'تعديل الوصفة': 'Edit Prescription',
        'تحديث الزيارة': 'Update Visit',
        'حفظ المتابعة': 'Save Follow-up',
        'تعليم كمريض حرج': 'Mark as Critical',
        'رسم الشبكية': 'Retina Drawing',
        'عرض الدواء': 'View Medication',
        'عرض الوصفة': 'View Prescription',
        'إضافة ملاحظة زيارة': 'Add Visit Note',
        'إضافة زيارة جديدة': 'Add New Visit',
        'إضافة صور': 'Add Images',
        'إضافة عملية': 'Add Surgery',
        'فتح الملف': 'Open File',
        'تنبيه الطبيب بالمريض القادم': 'Notify Doctor About Next Patient',
        'تم الاستدعاء': 'Called',
        'إضافة زيارة بتاريخ': 'Add Visit on Date',

        // --- Visit / Treatment ---
        'زيارات': 'Visits',
        'زيارة': 'Visit',
        'موعد زيارة': 'Visit Appointment',
        'التشخيص': 'Diagnosis',
        'العلاج': 'Treatment',
        'الوصفة': 'Prescription',
        'وصف': 'Description',
        'تفاصيل': 'Details',
        'تم الإجراء': 'Done',
        'مراجعات اليوم': "Today's Follow-ups",
        'زيارات آخر 7 أيام عمل': 'Visits in the Last 7 Working Days',
        'عمليات آخر 6 أشهر': 'Operations in the Last 6 Months',
        'الحقن حسب النوع خلال آخر 6 أشهر': 'Injections by Type in the Last 6 Months',
        'عمليات خلال 5 أيام': 'Operations Within 5 Days',
        'عمليات قادمة': 'Upcoming Operations',
        'عمليات قريبة': 'Operations Due Soon',
        'عمليات متأخرة': 'Overdue Operations',
        'عمليات هذا الشهر': 'Operations This Month',
        'ليزر هذا الشهر': 'Laser This Month',
        'حقن هذا الشهر': 'Injections This Month',
        'مواعيد قيد الانتظار': 'Pending Appointments',
        'الموعد': 'Appointment',
        'مراجعة': 'Follow-up',
        'حجز': 'Schedule',
        'حضور': 'Attendance',
        'الحضور': 'Attendance',
        'فحص': 'Examination',
        'النظر': 'Vision',
        'الصورة': 'Image',
        'صورة': 'Image',
        'الصور': 'Images',
        'صور': 'Images',
        'الشبكية': 'Retina',
        'دواء': 'Medication',
        'الأدوية': 'Medications',
        'الأدوية والتعليمات': 'Medications and Instructions',
        'الجرعة': 'Dose',
        'المدة': 'Duration',
        'عدد المرات': 'Frequency',
        'وصفة طبية': 'Medical Prescription',
        'إنشاء وصفة طبية': 'Create Prescription',
        'لا توجد وصفات سابقة': 'No previous prescriptions',
        'سبب المراجعة': 'Follow-up Reason',
        'تاريخ المراجعة': 'Follow-up Date',
        'موعد مراجعة': 'Follow-up Appointment',
        'زيارة أول مرة': 'First Visit',
        'زيارة متكررة': 'Repeat Visit',
        'مراجعات متأخرة': 'Overdue Follow-ups',
        'موعد عملية': 'Surgery Appointment',
        'حجز موعد عملية': 'Schedule Surgery',
        'تعديل موعد الحقن': 'Edit Injection Appointment',
        'تعديل موعد الليزر': 'Edit Laser Appointment',
        'موعد الحقن': 'Injection Appointment',
        'موعد الليزر': 'Laser Appointment',
        'موعد العملية': 'Surgery Appointment',
        'تم حذف الموعد بنجاح': 'Appointment deleted successfully',
        'تمت المعاينة': 'Examined',
        'لم يحضر المريض': 'Patient Did Not Attend',
        'سبب عدم حضور المريض': 'Reason for Non-attendance',
        'حفظ السبب وتأكيد عدم الحضور': 'Save Reason and Confirm Non-attendance',
        'بدون سبب مسجل': 'No reason recorded',
        'لا توجد ملاحظات.': 'No notes.',
        'لا توجد مشاكل': 'No issues',
        'آخر عملية': 'Last Operation',
        'مواعيد المراجعة': 'Follow-up Appointments',
        'تحديد موعد المراجعة': 'Schedule Follow-up',
        'حفظ موعد المراجعة': 'Save Follow-up Appointment',
        'الأدوية المصروفة': 'Dispensed Medications',
        'الأدوية الموصوفة سابقا': 'Previously Prescribed Medications',
        'طباعة العلاج فقط': 'Print Treatment Only',
        'طباعة كاملة': 'Print Full Prescription',
        'جلسات الليزر': 'Laser Sessions',
        'إضافة أو تعديل زيارة': 'Add or Edit Visit',
        'متابعة فائتة منذ': 'Missed Follow-up Since',
        'لا توجد جلسات حقن مسجلة.': 'No injection sessions recorded.',
        'لا توجد جلسات ليزر مسجلة': 'No laser sessions recorded',
        'لا توجد عمليات مسجلة': 'No surgeries recorded',
        'لا توجد زيارات أو فحوصات نظر أو رسومات شبكية مسجلة حتى الآن':
            'No visits, vision examinations, or retina drawings recorded yet',
        'لا توجد ملاحظة زيارة في هذا التاريخ': 'No visit note for this date',
        'لا يوجد رسم شبكية مرتبط بهذا التاريخ': 'No retina drawing linked to this date',
        'لا يوجد فحص': 'No Examination',
        'مرتبط بهذا التاريخ': 'Linked to This Date',
        'تمت الزيارة': 'Visit Completed',
        'تم تسجيل': 'Recorded',
        'هل تريد حذف الزيارة؟': 'Do you want to delete this visit?',
        'هل تريد حذف هذه العملية؟': 'Do you want to delete this surgery?',
        'موعد حقن': 'Injection Appointment',
        'موعد ليزر': 'Laser Appointment',
        'وصفة العلاج': 'Treatment Prescription',
        'تحتاج فحص VA': 'Needs VA Examination',
        'إجمالي الأيام المسجلة': 'Total Recorded Days',
        'اكتب ملاحظات الزيارة هنا...': 'Enter visit notes here…',
        'زيارة مراجعة': 'Follow-up Visit',
        'غير معروف': 'Unknown',
        'هل أنت متأكد من حذف هذه الزيارة؟': 'Are you sure you want to delete this visit?',
        'ابحث باسم المريض أو نوع الزيارة أو الرقم التسلسلي...':
            'Search by patient name, visit type, or serial number…',
        'الملف والمتابعة': 'File and Follow-up',

        // --- Eye Laterality ---
        'كلتا العينين': 'Both Eyes',
        'اليمنى': 'Right',
        'اليسرى': 'Left',
        'العينين': 'Both Eyes',

        // --- Stats / Counts ---
        'مريض ظاهر': 'patient(s) shown',
        'إجمالي': 'Total',
        'عدد': 'Count',
        'الأعلى شهريا': 'Highest Monthly',
        'الأعلى شهرياً': 'Highest Monthly',
        'الأعلى': 'Highest',
        'الأكثر': 'Most',
        'خلال 7 أيام': 'Within 7 Days',
        'نتائج البحث': 'Search Results',
        'رقم': 'Number',
        'قائمة': 'List',
        'سجل': 'Record',
        'السجل': 'Record',
        'من تاريخ': 'From Date',
        'إلى تاريخ': 'To Date',
        'تاريخ الأرشفة': 'Archive Date',

        // --- Greetings ---
        'مرحبا': 'Welcome',

        // --- Misc ---
        'تم الحذف بنجاح': 'Deleted successfully',
        'خطأ': 'Error',
        'بنجاح': 'Successfully',
        'فشل': 'Failed',
        'تعذر': 'Unable',
        'صالح': 'Valid',
        'موجود': 'Existing',
        'توجد': 'Available',
        'متاح': 'Available',
        'تحديد': 'Select',
        'التحقق': 'Verification',
        'الحقول': 'Fields',
        'حقل': 'Field',
        'المستخدم': 'User',
        'نموذج': 'Form',
        'الجدول': 'Schedule',
        'البيانات': 'Data',
        'بيانات': 'Data',
        'الملف': 'File',
        'الموبايل': 'Mobile',
        'قاعدة': 'Database',
        'المزامنة': 'Sync',
        'التعارض': 'Conflict',
        'تعارضات': 'Conflicts',
        'الاحتياطية': 'Backup',
        'الطوارئ': 'Emergency',
        'النسخة': 'Version',
        'المحلي': 'Local',
        'السحابية': 'Cloud',
        'السحابة': 'Cloud',
        'إدارة': 'Manage',
        'إجراءات': 'Actions',
        'قرار': 'Decision',
        'رسم': 'Drawing',
        'الرسم': 'Drawing',
        'العناوين': 'Addresses',
        'سبب': 'Reason',
        'السبب': 'Reason',
        'حالة': 'Status',
        'نافذة': 'Window',
        'إضافية': 'Additional',
        'سابقة': 'Previous',
        'القادمة': 'Upcoming',
        'المتوقعة': 'Expected',
        'المطلوب': 'Required',
        'جديد': 'New',
        'الكتابة': 'Writing',
        'وضع': 'Mode',
        'زر': 'Button',
        'الصيغ المسموح بها': 'Allowed formats',
        'حجم الصورة كبير جدا. الحد الأقصى 10MB.': 'Image is too large. Maximum size is 10 MB.',
        'صيغة الصورة غير مدعومة. المسموح: JPG, JPEG, PNG, GIF, WEBP.':
            'Unsupported image format. Allowed: JPG, JPEG, PNG, GIF, WEBP.',
        'معرف غير صالح.': 'Invalid ID.',
        'خطأ: لم يتم تحديد المريض': 'Error: patient was not selected',
        'خطأ: المريض غير موجود': 'Error: patient not found',
        'خطأ: الوصفة غير موجودة': 'Error: prescription not found',
        'لم يتم تحديد المريض': 'Patient was not selected',
        'تأكيد حضور المريض والانتقال إلى صفحة الإجراء لإكمال البيانات النهائية.':
            'Confirm patient attendance and continue to the procedure page to complete the final details.',
        'اختر الإجراء المناسب لهذا الموعد. في حالة عدم حضور المريض ستظهر نافذة لإضافة السبب وحفظه ضمن ملاحظات المريض.':
            'Choose the appropriate action for this appointment. If the patient did not attend, a window will open to record the reason in the patient notes.',
        'فتح نافذة لإدخال سبب عدم الحضور وحفظه مباشرة داخل ملاحظات المريض.':
            'Open a window to enter the non-attendance reason and save it in the patient notes.',
        'أدخل الملاحظة التي تريد ظهورها لاحقا ضمن ملاحظات المريض.':
            'Enter the note that should appear later in the patient record.',
        'سيتم تحديث حالة الموعد وإضافة السبب إلى ملاحظات المريض.':
            'The appointment status will be updated and the reason added to the patient notes.',
        'مثال: لم يحضر بسبب السفر أو تعذر التواصل معه':
            'Example: did not attend because of travel or inability to contact the patient',
        'مثال: مراجعة ضغط العين': 'Example: intraocular pressure follow-up',
        'اختر نوع الحقن': 'Choose Injection Type',
        'اختر نوع العملية': 'Choose Surgery Type',
        'اختر نوع الليزر': 'Choose Laser Type',
        'اختر نوع العدسة': 'Choose Lens Type',
        'إضافة فحص النظر': 'Add Vision Examination',
        'اضافة فحص النظر': 'Add Vision Examination',
        'حالة مهمة': 'Important Case',
        'بدون زيارات': 'No Visits',
        'أرقام مكررة': 'Duplicate Numbers',
        'أرقام هاتف مكررة': 'Duplicate Phone Numbers',
        'المتابعات': 'Follow-ups',
        'اسم القالب': 'Template Name',
        'القوالب المحفوظة': 'Saved Templates',
        'استشارة طبية': 'Medical Consultation',
        'اختصاص طب وجراحة العيون': 'Ophthalmology and Eye Surgery',
        'الرئيسية': 'Home',
        'الدكتور': 'Doctor',
        'الأحد': 'Sunday',
        'الاثنين': 'Monday',
        'الثلاثاء': 'Tuesday',
        'الأربعاء': 'Wednesday',
        'الخميس': 'Thursday',
        'الجمعة': 'Friday',
        'السبت': 'Saturday',
        'رفع صورة للمريض': 'Upload Patient Image',
        'اللون': 'Color',
        'الحجم': 'Size',
        'الأداة': 'Tool',
        'تاريخ الرسم': 'Drawing Date',
        'رسومات شبكية': 'Retina Drawings',
        'تحتاج VA': 'Needs VA',
        'مع VA': 'With VA',
        'وقت الإرسال': 'Sent At',
        'جزئي': 'Partial',
        'متخطي': 'Skipped',
        'تم الليزر': 'Laser Completed',
        'تم إعطاء الإبرة': 'Injection Administered',
        'حفظ التعديلات': 'Save Changes',
        'بانتظار': 'Waiting',
        'حالة مهمة': 'Important Case',
        'معلم يدويا كمريض حرج': 'Manually Marked as Critical',
        'رفع قاعدة البيانات إلى السحابة': 'Upload Database to Cloud',
        'وضع الحماية مفعل': 'Protection Mode Enabled',
        'إدارة التعارضات متاحة من السيرفر المحلي فقط.':
            'Conflict management is available only on the local server.',
        'المزامنة الآمنة إلى السحابة تعمل من السيرفر المحلي في العيادة فقط.':
            'Safe cloud synchronization runs only from the local clinic server.',
        'المزامنة العكسية تعمل من السيرفر المحلي في العيادة فقط.':
            'Reverse synchronization runs only from the local clinic server.',
        'النسخة السحابية في وضع قراءة فقط مؤقتا لتجنب تضارب البيانات أثناء الطوارئ.':
            'The cloud version is temporarily read-only to prevent data conflicts during emergencies.',
        'تم إيقاف هذا المسار القديم. استخدم المزامنة الآمنة.':
            'This legacy route has been disabled. Use safe synchronization.',
        'يمكنك إعادة تفعيل الكتابة من صفحة الإعدادات بواسطة حساب المدير الرئيسي فقط.':
            'Cloud writing can be re-enabled from Settings by the primary administrator only.',
        'إجراء غير معروف.': 'Unknown action.',
        'التعارض غير موجود.': 'Conflict not found.',
        'إغلاق التعارض يدويا بدون تغيير البيانات؟':
            'Close the conflict manually without changing data?',
        'اعتماد السحابة وتحديث المحلي؟': 'Use the cloud version and update local data?',
        'اعتماد المحلي ورفعه إلى السحابة؟': 'Use the local version and upload it to the cloud?',
        'المزامنة العكسية': 'Reverse Sync',
        'المزامنة الآمنة': 'Safe Sync',
        'آمن': 'Safe',
        'أدمن': 'Administrator',
        'إجراءات اعتيادية': 'Routine Actions',
        'إجراءات حساسة': 'Sensitive Actions',
        'إدارة النسخ الاحتياطي، المزامنة، وإجراءات الطوارئ من مكان واحد.':
            'Manage backups, synchronization, and emergency actions from one place.',
        'السماح بالكتابة على النسخة السحابية': 'Allow Writing to Cloud Version',
        'تم إنشاء النسخة الاحتياطية بنجاح': 'Backup created successfully',
        'تم تحديث وضع الكتابة بنجاح': 'Writing mode updated successfully',
        'تمت الاستعادة بنجاح': 'Restore completed successfully',
        'فشلت عملية الاستعادة': 'Restore failed',
        'الملف غير موجود': 'File not found',
        'هذا الخيار متاح فقط لحساب المدير الرئيسي':
            'This option is available only to the primary administrator',
        'سيتم حذف البيانات الحالية واستبدالها بالنسخة المختارة. هل أنت متأكد؟':
            'Current data will be deleted and replaced with the selected backup. Are you sure?',
        'استرجاع هذا المريض؟': 'Restore this patient?',
        'اسم المستخدم موجود مسبقا': 'Username already exists',
        'تمت': 'Completed',
        'أرسلها:': 'Sent by:',
        'العيادة': 'Clinic',
        'يوجد': 'Available',
        'تعارض مزامنة مفتوح': 'Open sync conflict',
        'تفاصيل:': 'Details:',
        'التاريخ:': 'Date:',
        'القسم:': 'Section:',
        'الوقت:': 'Time:',
        'المجموع:': 'Total:',
        'أيقونة': 'Icon',
        'تحذير': 'Warning',
        'نجاح': 'Success',
        'معلومات': 'Information',
        'خطر': 'Danger',
        'انتظار': 'Waiting',
        'تنبيهات': 'Alerts',
        'أنواع التنبيهات': 'Alert Types',
        'نعم': 'Yes',
        'لا': 'No',
        'ملف': 'File',
        'المريض': 'Patient',
        'اليوم': 'Today',
    };

    // =========================================================
    // BUILD SORTED ENTRY LIST (longest key first)
    // =========================================================
    const ENTRIES = Object.entries(DICT).sort((a, b) => b[0].length - a[0].length);

    // =========================================================
    // TEXT REPLACEMENT
    // =========================================================
    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function translateText(text) {
        let out = text
            .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
            .replace(/\u0640/g, '');
        for (const [ar, en] of ENTRIES) {
            const normalizedArabic = ar
                .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
                .replace(/\u0640/g, '');
            if (!out.includes(normalizedArabic)) continue;

            if (/^[\u0600-\u06FF]+$/.test(normalizedArabic)) {
                const wordPattern = new RegExp(
                    '(^|[^\\u0600-\\u06FF])' + escapeRegExp(normalizedArabic) + '(?=$|[^\\u0600-\\u06FF])',
                    'g'
                );
                out = out.replace(wordPattern, (match, prefix) => prefix + en);
            } else {
                out = out.split(normalizedArabic).join(en);
            }
        }
        return out;
    }

    function isProtectedContent(element) {
        return Boolean(element && element.closest([
            '[data-no-translate]',
            '.clinic-user-content',
            '.visit-note-item[data-user-content]',
            '.procedure-note[data-user-content]',
            '.prescription-diagnosis',
            '.prescription-list',
            '.retina-user-note'
        ].join(',')));
    }

    function translateElementAttributes(root) {
        const elements = [];
        if (root.nodeType === Node.ELEMENT_NODE) elements.push(root);
        if (root.querySelectorAll) elements.push(...root.querySelectorAll('*'));

        elements.forEach(el => {
            if (isProtectedContent(el)) return;

            ['placeholder', 'title', 'aria-label', 'data-title', 'data-label'].forEach(attr => {
                if (!el.hasAttribute(attr)) return;
                const value = el.getAttribute(attr);
                const translated = translateText(value);
                if (translated !== value) el.setAttribute(attr, translated);
            });

            if (el.matches('input[type="button"], input[type="submit"], input[type="reset"]')) {
                const translated = translateText(el.value);
                if (translated !== el.value) el.value = translated;
            }
        });
    }

    function walkAndTranslate(root) {
        if (!root) return;

        if (root.nodeType === Node.TEXT_NODE) {
            const parent = root.parentElement;
            if (!parent || /^(script|style)$/i.test(parent.tagName) || isProtectedContent(parent)) return;
            const translated = translateText(root.textContent);
            if (translated !== root.textContent) root.textContent = translated;
            return;
        }

        // Text nodes
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                const p = node.parentElement;
                if (!p) return NodeFilter.FILTER_REJECT;
                const t = p.tagName.toLowerCase();
                if (t === 'script' || t === 'style' || isProtectedContent(p)) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            const translated = translateText(node.textContent);
            if (translated !== node.textContent) node.textContent = translated;
        });

        translateElementAttributes(root);
    }

    // =========================================================
    // DIRECTION + FONT SWITCH
    // =========================================================
    function applyDir(lang) {
        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'en' ? 'ltr' : 'rtl';
        if (document.body) document.body.dir = lang === 'en' ? 'ltr' : 'rtl';
    }

    // =========================================================
    // INJECT TOGGLE BUTTON
    // =========================================================
    let langBtn = null;

    function updateBtnAppearance(lang) {
        if (!langBtn) return;
        if (lang === 'en') {
            langBtn.innerHTML = '<span class="clinic-lang-icon" aria-hidden="true">🌐</span><span class="clinic-lang-code">AR</span>';
            langBtn.title = 'Switch to Arabic';
            langBtn.setAttribute('aria-label', 'Switch to Arabic');
        } else {
            langBtn.innerHTML = '<span class="clinic-lang-icon" aria-hidden="true">🌐</span><span class="clinic-lang-code">EN</span>';
            langBtn.title = 'Switch to English';
            langBtn.setAttribute('aria-label', 'Switch to English');
        }
    }

    function injectLanguageStyles() {
        if (document.getElementById('clinicLanguageStyles')) return;

        const style = document.createElement('style');
        style.id = 'clinicLanguageStyles';
        style.textContent = `
            .clinic-language-slot {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-top: auto;
                padding: 12px 4px 2px;
                border-top: 1px solid rgba(148, 163, 184, .2);
            }
            #langToggle.clinic-lang-toggle {
                width: auto;
                min-width: 58px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 0 10px;
                border: 1px solid rgba(37, 99, 235, .28);
                border-radius: 999px;
                background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(14, 165, 233, .08));
                color: #1d4ed8;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .08);
                font-family: "Segoe UI", Arial, sans-serif;
                font-size: 11px;
                font-weight: 900;
                line-height: 1;
                letter-spacing: .7px;
                cursor: pointer;
                transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            }
            #langToggle.clinic-lang-toggle:hover {
                transform: translateY(-1px);
                background: linear-gradient(135deg, rgba(37, 99, 235, .2), rgba(14, 165, 233, .14));
                box-shadow: 0 9px 22px rgba(37, 99, 235, .16);
            }
            #langToggle.clinic-lang-toggle:focus-visible {
                outline: 3px solid rgba(59, 130, 246, .25);
                outline-offset: 2px;
            }
            #langToggle .clinic-lang-icon {
                font-size: 15px;
                line-height: 1;
            }
            #langToggle.clinic-lang-floating {
                position: fixed;
                top: 14px;
                left: 14px;
                z-index: 9999;
                background: linear-gradient(135deg, #2563eb, #0284c7);
                color: #fff;
                border-color: transparent;
            }
            body[data-theme="dark"] #langToggle.clinic-lang-toggle,
            body.dark #langToggle.clinic-lang-toggle {
                color: #bfdbfe;
                border-color: rgba(96, 165, 250, .34);
                background: linear-gradient(135deg, rgba(37, 99, 235, .28), rgba(14, 165, 233, .16));
                box-shadow: 0 7px 20px rgba(0, 0, 0, .24);
            }
            @media print {
                #langToggle,
                .clinic-language-slot {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function injectButton() {
        const existingButton = document.getElementById('langToggle');
        if (existingButton) {
            langBtn = existingButton;
            langBtn.classList.add('clinic-lang-toggle');
            updateBtnAppearance(localStorage.getItem('lang') || 'ar');
            return;
        }

        injectLanguageStyles();

        langBtn = document.createElement('button');
        langBtn.id = 'langToggle';
        langBtn.type = 'button';
        langBtn.className = 'clinic-lang-toggle';

        langBtn.addEventListener('click', () => {
            const current = localStorage.getItem('lang') || 'ar';
            const next = current === 'ar' ? 'en' : 'ar';
            localStorage.setItem('lang', next);
            document.cookie = 'clinic_lang=' + next + '; path=/; max-age=31536000; SameSite=Lax';
            if (next === 'ar') {
                location.reload();
            } else {
                applyLanguage('en');
            }
        });

        const sidebar =
            document.getElementById('appSidebar') ||
            document.getElementById('sidebar') ||
            document.querySelector('aside.app-sidebar') ||
            document.querySelector('aside.sidebar');

        if (sidebar) {
            const slot = document.createElement('div');
            slot.className = 'clinic-language-slot';
            slot.setAttribute('data-no-translate', '');
            slot.appendChild(langBtn);
            sidebar.appendChild(slot);
        } else {
            // Pages without a sidebar keep the control next to the theme button.
        const themeBtn = document.getElementById('themeToggle');
        if (themeBtn) {
            themeBtn.insertAdjacentElement('afterend', langBtn);
        } else {
            const container =
                document.querySelector('.top-actions') ||
                document.querySelector('.topbar-actions') ||
                document.querySelector('header');
            if (container) {
                container.appendChild(langBtn);
            } else {
                langBtn.classList.add('clinic-lang-floating');
                document.body.appendChild(langBtn);
            }
        }
        }

        updateBtnAppearance(localStorage.getItem('lang') || 'ar');
    }

    // =========================================================
    // APPLY LANGUAGE
    // =========================================================
    let translationObserver = null;

    function startTranslationObserver() {
        if (translationObserver || !document.body) return;

        translationObserver = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'characterData') {
                    walkAndTranslate(mutation.target);
                    return;
                }
                mutation.addedNodes.forEach(node => walkAndTranslate(node));
            });
        });
        translationObserver.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    function applyLanguage(lang) {
        if (lang === 'en') {
            walkAndTranslate(document.body);
            document.title = translateText(document.title);
            applyDir('en');
            updateBtnAppearance('en');
            startTranslationObserver();
        } else {
            applyDir('ar');
            updateBtnAppearance('ar');
        }
    }

    // =========================================================
    // INIT — handles both deferred and inline loading
    // =========================================================
    function init() {
        const savedLanguage = localStorage.getItem('lang') || 'ar';
        document.cookie = 'clinic_lang=' + savedLanguage + '; path=/; max-age=31536000; SameSite=Lax';
        injectButton();
        const lang = savedLanguage;
        if (lang === 'en') applyLanguage('en');
    }

    const nativeAlert = window.alert;
    const nativeConfirm = window.confirm;
    window.alert = message => nativeAlert.call(window, translateText(String(message)));
    window.confirm = message => nativeConfirm.call(window, translateText(String(message)));

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
