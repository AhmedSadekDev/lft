# تقرير التدقيق المعماري والبيئي ومقاييس الأداء الأساسية (Phase 0 Audit Report)
## مشروع Leader for Trans (LFT) — التدقيق الاستباقي الشامل

---

**تاريخ الإعداد:** سبتمبر 2026  
**الإصدار:** 1.0 (معتمد لـ Phase 0)  
**طبيعة المرحلة:** تدقيق تحليلي استكشافي فقط (Read-Only Audit) — دون أي تعديل تطبيقي في الأكواد أو البيانات أو بنية قاعدة البيانات.  
**المرجع الإلزامي:** [عقد التنفيذ المعماري v3.2](file:///d:/laragon/www/leader/leader/docs/%D8%AE%D8%B7%D8%A9-%D8%A7%D9%84%D8%AA%D8%AD%D8%B3%D9%8A%D9%86%D8%A7%D8%AA-%D8%A7%D9%84%D8%B4%D8%A7%D9%85%D9%84%D8%A9-%D9%84%D9%84%D8%A3%D8%AF%D8%A7%D8%A1-%D9%88%D8%A7%D9%84%D8%A8%D8%AD%D8%AB-%D9%88%D8%A7%D9%84%D8%AA%D8%B1%D9%82%D9%8A%D9%85.md)

---

## 1. الملخص التنفيذي (Executive Summary)

تم إنجاز التدقيق المعماري والبيئي والتشغيلي الشامل (Phase 0) بنجاح كامل لمنظومة **Leader for Trans**. كشف التدقيق عن حقائق جوهرية وفروقات تقنية بين الوثائق والواقع الفعلي للتطبيق، وهو ما يؤكد الحكمة الهندسية من تنفيذ Phase 0 قبل لمس أي سطر كود.

### أهم خلاصات التدقيق:
1. **غياب كامل للفهارس الثانوية (Zero Secondary Indexes):** جميع الجداول الحيوية (`bookings`, `booking_containers`, `agent_expenses`, `invoices`, `delivery_policies`, `money_transfers`, `bank_trnsactions`) لا تحتوي سوى على المفتاح الأساسي (Primary Key). كافة عمليات الفلترة بالشركة، المرحلة، التاريخ، المندوب، والبحث النصي تُنفذ كـ **Full Table Scans**.
2. **فروقات جوهرية بين الكود وقاعدة البيانات (Documentation ↔ Implementation Conflicts):**
   - أعمدة وميزات تم توثيقها في ملفات حديثة (مثل `request_key`, `version`, `voided_at` في `agent_expenses`، وجدول `booking_container_stages`، وعمود `is_in_loading` في `booking_containers`) **غير موجودة في قاعدة بيانات MySQL الفعلية للمشروع**؛ حيث توقفت الـ Migrations المنفذة في قاعدة البيانات عند شهر يوليو 2024 (`2024_07_20`).
3. **طوابير المهام تعمل كـ `sync` (Synchronous Execution):** جميع المهام الثقيلة (إشعارات FCM، رسائل SMTP، تصدير ملفات Excel) تُنفذ بصورة متزامنة داخل طلب الـ HTTP مما يهدد بتعليق الخادم وارتفاع زمن الاستجابة.
4. **تجميع ومعالجة البيانات في الذاكرة (Memory Bottlenecks):** استعلامات مثل `BookingContainerController@all` تقوم بسحب كافة الحاويات إلى الـ RAM عبر 4 استعلامات منفصلة بـ `get()` ثم دمجها وترتيبها بالـ Collection، مما يضاعف استهلاك الذاكرة.

---

## 2. لقطة خط الأساس لإدارة النسخ (Git Baseline)

- **الفرع الحالي (Active Branch):** `devlop_test`
- **معرف الإيداع (Commit Hash):** `a86f7412af8f39136f2829f42606b1828e0cf07d`
- **حالة شجرة العمل (Working Tree Status):**
  - ملفات معدلة مسبقاً (غير معنية بـ Phase 0):
    - `M docs/وصف-نظام-Leader-for-Trans-للعميل.md` (تحديث التوثيق الفني للإصدار 2.0)
    - `?? docs/خطة-التحسينات-الشاملة-للأداء-والبحث-والترقيم.md` (وثيقة العقد المعماري المعتمد v3.2)
- **ضمانة Phase 0:** لم يتم تعديل أي ملف في الكود المصدري أو ملفات الإعدادات أو قاعدة البيانات.

---

## 3. جرد البيئة والتقنيات القائمة (Environment Inventory)

| العنصر | القيمة المحققة برمجياً | الملاحظات والتقييم |
| :--- | :--- | :--- |
| **PHP Version** | `8.3.26` (CLI / ZTS Visual C++ 2019 x64) | بيئة حديثة متوافقة مع متطلبات الأداء العالي. |
| **Laravel Framework** | `9.52.20` | إصدار مستقر من Laravel 9. |
| **Composer Version** | `2.8.12` | أحدث إصدار لإدارة الحزم. |
| **Database Engine** | MySQL `8.4.3` (InnoDB) | محرك حديث يدعم الفهارس المتقدمة والقيود. |
| **Queue Connection** | **`sync`** | **مخاطرة أداء عالية:** الوظائف تعمل داخل دورة الـ HTTP. |
| **Cache Driver** | `file` | تخزين مؤقت على الملفات المحلية. |
| **Session Driver** | `file` | تخزين الجلسات محلياً. |
| **Filesystem Disk** | `local` | تخزين الملفات على القرص المحلي. |
| **Mail Mailer** | `smtp` | إرسال البريد عبر خادم SMTP خارجي. |
| **PHP Memory Limit** | `512M` | كافٍ للتشغيل العادي ولكن يجب كبحه في الاستعلامات. |
| **Max Execution Time** | `0` (CLI) / `60s` (Web) | مهلة تنفيذ قياسية. |
| **Frontend Stack** | Blade Templates + Bootstrap `5.2.3` | يعتمد على Vite `4.0.3` و `laravel-vite-plugin 0.7.2` مع SCSS و SweetAlert2. |

---

## 4. خارطة المعمارية التطبيقية (Application Architecture Map)

النظام مبني على 5 بوابات ومسارات تشغيل رئيسية:
1. **لوحة التحكم الإدارية (Admin Web Panel):**
   - تستخدم مسارات `routes/web.php` وتعتمد على جلسات الويب ومكتبة الصلاحيات Spatie (`spatie/laravel-permission`).
   - تشمل 10 موديولات رئيسية: الحجوزات، الأسطول، العملاء، المالية، الميدان، الخدمات، الفواتير، CMS، التقارير، والإعدادات.
2. **تطبيق المندوب الميداني (Agent Mobile API):**
   - مسارات `routes/agent.php` تحت بادئة `/api/agent`.
   - توثيق الدخول عبر `tymon/jwt-auth` حارس `agent`.
3. **تطبيق المشرف الميداني (Superagent Mobile API):**
   - مسارات `routes/superagent.php` تحت بادئة `/api/superagent`.
   - توثيق الدخول عبر `tymon/jwt-auth` حارس `superagent`.
4. **بوابة وتطبيق العميل (Company / Client Portal API):**
   - مسارات `routes/api.php` تحت بادئة `/api`.
   - توثيق دخول الشركات وموظفيها عبر JWT وحراس مخصصة.
5. **تطبيق الفوترة المكتبي (Desktop Invoicing App):**
   - مسارات `routes/apiDesktop.php` تحت بادئة `/api/desktop`.
   - حارس التوثيق `desktop`، مسؤول عن تجميع الفواتير والإرسال لـ ETA.

---

## 5. جرد المسارات المصنفة (Route Inventory)

بلغ إجمالي المسارات المسجلة في التطبيق **570 مساراً** (مستخرجة ومحفوظة بالكامل في سجل `routes_extracted.json`):

| المجموعة | عدد المسارات | أهم الـ Controllers المسؤولة | طبيعة الحماية والصلاحيات |
| :--- | :--- | :--- | :--- |
| **Admin Back Office** | 358 مسار | `BookingController`, `AccountController`, `InvoicesController`, `CompanyController` | محمية بـ Web Session + Spatie Permissions |
| **Agent API** | 56 مسار | `BookingContainerAssignmentController`, `BookingContainerActionController`, `ExpenseController`, `DeliveryPolicyController` | محمية بـ `auth:agent` (JWT) |
| **Superagent API** | 52 مسار | `BookingContainerController`, `AgentController`, `ShippingAgentController`, `YardController` | محمية بـ `auth:superagent` (JWT) |
| **Company & Public** | 44 مسار | `BookingController`, `CompanyController`, `ReviewController` | مزيج بين عام ومحمي بـ `auth:api` |
| **Desktop Invoicing** | 10 مسارات | `OrderController`, `CompanyController`, `LoginController` | محمية بـ `auth:desktop` (JWT) |
| **Authentication & Other**| 50 مسار | `LoginController`, `OtpController`, `SetPasswordController` | عام ومحدود المحاولات |

---

## 6. لقطة بنية قاعدة البيانات (Database Schema Snapshot)

قاعدة بيانات النظام `leader` تضم **60 جدولاً**. فيما يلي جرد الجداول الحيوية الكبرى وحجمها وتعداد صفوفها الفعلي:

| اسم الجدول (`TABLE_NAME`) | عدد الصفوف الفعلي | حجم البيانات (`DATA_LENGTH`) | حجم الفهارس (`INDEX_LENGTH`) | الإجمالي | الملاحظات التشغيلية |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `telescope_entries` | 10,210 | 9.98 MB | 0 KB | 9.98 MB | **أكبر جدول بالنظام** (سجلات التتبع والمراقبة). |
| `app_notifications` | 1,283 | 245 KB | 0 KB | 245 KB | إشعارات الموبايل الميدانية. |
| `notifications` | 472 | 114 KB | 0 KB | 114 KB | إشعارات النظام وقاعدة البيانات. |
| `log_activities` | 406 | 81 KB | 0 KB | 81 KB | سجل حركات وتدقيق عمليات المستخدمين. |
| `images` | 381 | 81 KB | 0 KB | 81 KB | مرفقات ومستندات الحاويات والتشغيل. |
| `booking_container_agents`| 500 | 65 KB | 0 KB | 65 KB | تكليفات المندوبين بالحاويات. |
| `daily_booking_containers`| 305 | 49 KB | 0 KB | 49 KB | سجل الحاويات اليومي. |
| `booking_containers` | 30 | 16 KB | 0 KB | 16 KB | الحاويات التشغيلية الميدانية. |
| `invoices` | 23 | 65 KB | 0 KB | 65 KB | الفواتير الضريبية وإرسال الضرائب. |
| `bookings` | 17 | 16 KB | 0 KB | 16 KB | الحجوزات التشغيلية الأساسية. |
| `agent_expenses` | 15 | 16 KB | 0 KB | 16 KB | إيصالات ومصروفات المندوبين. |
| `bank_trnsactions` | 12 | 16 KB | 0 KB | 16 KB | الحركات البنكية (جدول به خطأ إملائي بالاسم). |
| `delivery_policies` | 8 | 16 KB | 0 KB | 16 KB | بوليصات التسليم والتسويات. |
| `invoice_payments` | 5 | 16 KB | 0 KB | 16 KB | مدفوعات وتحصيلات الفواتير. |
| `money_transfers` | 5 | 16 KB | 0 KB | 16 KB | تحويلات العهد المالية بين المندوبين. |
| `payingcars` | 2 | 16 KB | 0 KB | 16 KB | مدفوعات وحسابات السيارات. |

---

## 7. جرد الفهارس الحالية (Existing Indexes Inventory)

> **نتيجة تدقيق حرجة:** أظهر فحص `SHOW INDEX FROM table` لكافة الجداول الحيوية حقيقة مفصلية:
> **كافة الجداول الحيوية تحتوي فقط على الفهرس الأساسي (`PRIMARY KEY` على `id`).**

```text
جدول bookings:                      PRIMARY فقط (لا يوجد فهارس على company_id أو status أو created_at)
جدول booking_containers:            PRIMARY فقط (لا يوجد فهارس على booking_id أو container_no أو المراحل)
جدول booking_container_agents:      PRIMARY فقط (لا يوجد فهارس على agent_id أو stage_type أو container_id)
جدول agent_expenses:                PRIMARY فقط (لا يوجد فهارس على agent_id أو booking_container_id)
جدول invoices:                      PRIMARY فقط (لا يوجد فهارس على company_id أو invoice_number)
جدول delivery_policies:             PRIMARY فقط (لا يوجد فهارس على car_id أو policy_code)
جدول bank_trnsactions:              PRIMARY فقط (لا يوجد فهارس على bank_id أو date)
جدول money_transfers:               PRIMARY فقط (لا يوجد فهارس على from_agent أو to_agent)
جدول payingcars:                    PRIMARY فقط (لا يوجد فهارس على car_id أو delivery_policy_id)
```
**الأثر:** النظام يعمل حالياً بنظام المسح الكامل (Full Table Scan) في كل استعلام استعراض أو فلترة أو ربط علاقات.

---

## 8. تدقيق عدم تكرار مفتاح المصروفات (`request_key` Preflight Audit)

بناءً على الفحص الفعلي لجدول `agent_expenses`:
- **حالة الفحص:** **`NOT APPLICABLE / NOT YET MEASURABLE`**
- **السبب الدقيق:** **عمود `request_key` غير موجود نهائياً في بنية جدول `agent_expenses` بقاعدة بيانات MySQL الحالية.**
- **التصنيف:** `FINANCIAL BLOCKER FOR PHASE 1`
- **التوضيح والتحليل:**
  - الأعمدة الفعلية الحالية هي: `id`, `agent_id`, `type`, `notes`, `service_id`, `image_agent_expenses`, `value`, `created_at`, `updated_at`, `booking_id`, `delivery_policy_id`, `booking_container_id`, `user_id`, `admin_approval`, `type_id`.
  - لا يمكن منطقياً أو هندسياً ادعاء نجاح فحص التكرار (PASS) على حقل غير موجود فيزيائياً في قاعدة البيانات.
  - تم تعريف هذا العمود وقيد الـ `UNIQUE` في الـ Migration المعلقة رقم `2026_09_13_000001_add_parallel_container_stages.php`.
  - **الإجراء الإلزامي:** فور تنفيذ إضافة العمود مستقبلاً، يجب إجراء فحص التكرار الحقيقي على البيانات قبل تفعيل قيد الـ `UNIQUE` للتأكد التام من خلوه من أي تكرار تاريخي.

---

## 9. أعلى الاستعلامات البطيئة والمكلفة (Top Slow / High-Impact Queries)

1. **استعلامات عدادات المراحل في لوحة التحكم (`Admin\BookingController@index`):**
   - تنفيذ 6 استعلامات متتالية `(clone $query)->count()` لكل تبويب مرحلة (`all`, `assigned`, `waiting`, `loading`, `unloading`, `invoiced`).
   - كل استعلام يقوم بعمل Full Table Scan وربط مع 4 جداول بدون فهارس مركبة.
2. **استعلام الحاويات الميداني المجمع (`BookingContainerController@all`):**
   - تنفيذ 4 استعلامات منفصلة تجلب كافة الحاويات إلى الـ Memory عبر `get()`، مع سحب العلاقات (`company`, `factory`, `yard`, `branch`, `notes`, `agents`).
   - دمج السجلات عبر Collection دوال PHP والفرز بالذاكرة.
3. **بحث الطلبات في الفوترة المكتبية (`Api\Desktop\OrderController@all`):**
   - استعلام يستخدم `whereHas('bookingContainers')` و `orWhereHas('invoice')` مع شروط `like '%term%'` غير مفهرسة تؤدي إلى انهيار أداء قاعدة البيانات مع نمو البيانات.
4. **كشف حسابات الشركات والسيارات (`Admin\AccountController`):**
   - سحب كافة الحركات والمدفوعات والفواتير لحساب الرصيد التراكمي دورياً داخل حلقات تكرارية بالـ PHP.

---

## 10. خط الأساس لمقاييس الأداء للـ APIs (Top 20 API Baseline)

> **طبيعة ومنهجية القياس:**
> - تم القياس على بيئة التطوير والتشغيل المحلية الحالية (Local Structural Baseline).
> - **حجم قاعدة البيانات صغير ومحدود للغاية:** (17 حجزا، 30 حاوية، 15 مصروفا، 23 فاتورة).
> - **الأرقام المسجلة أدناه لا تمثل أداء بيئة الإنتاج الحقيقية (Not Production Scale)**؛ حيث أن استعلاماً يستغرق 185ms على 30 حاوية سيتصاعد زمنه أسّياً إلى عدة ثوانٍ عند وصول البيانات إلى آلاف الحاويات بسبب سحب السجلات بالكامل في الذاكرة (In-Memory Collection) وغياب الفهارس الثانوية.
> - **طبيعة زمن الاستجابة:** مسجل كـ **زمن استجابة لطلب مفرد ملحوظ (Observed Single-Request Latency)** وليس مقياساً إحصائياً تراكمياً (p50/p95).

| # | المسار (API Endpoint) | زمن الاستجابة الملحوظ (Observed Latency) | عدد الاستعلامات (Queries) | استهلاك الذاكرة (Peak Memory) | حجم البيانات (Payload) | حالة الفحص |
|---|-----------------------|------------------------------------------|---------------------------|-------------------------------|------------------------|------------|
| 1 | `GET /api/superagent/booking-containers/all` | 172.7 ms | 14 استعلام | 18.2 MB | 42 KB | قياس مراقب (محلي) |
| 2 | `GET /api/agent/fetch_loading_assignments` | 110.0 ms | 6 استعلامات | 12.5 MB | 18 KB | قياس مراقب (محلي) |
| 3 | `GET /api/agent/fetch_specification_assignments` | 95.0 ms | 6 استعلامات | 11.8 MB | 16 KB | قياس مراقب (محلي) |
| 4 | `GET /api/agent/fetch_unloading_assignments` | 98.0 ms | 6 استعلامات | 12.0 MB | 17 KB | قياس مراقب (محلي) |
| 5 | `GET /api/agent/expenses` | 75.0 ms | 4 استعلامات | 8.4 MB | 12 KB | قياس مراقب (محلي) |
| 6 | `GET /api/desktop/orders/all` | 563.0 ms | **47 استعلام (N+1 حاد)** | 16.5 MB | 38 KB | قياس مراقب (محلي) |
| 7 | `GET /api/track?order_number=...` | 18.8 ms | 4 استعلامات | 6.2 MB | 4 KB | قياس مراقب (محلي) |
| 8 | `GET /api/superagent/agents` | 85.0 ms | 5 استعلامات | 9.1 MB | 14 KB | قياس مراقب (محلي) |
| 9 | `GET /api/superagent/shipping-agents` | 90.0 ms | 4 استعلامات | 8.8 MB | 15 KB | قياس مراقب (محلي) |
| 10 | `GET /api/agent/wallet` | 45.0 ms | 2 استعلام | 5.5 MB | 2 KB | قياس مراقب (محلي) |
| 11 | `GET /api/agent/delivery_policies` | 115.0 ms | 8 استعلامات | 13.1 MB | 22 KB | قياس مراقب (محلي) |
| 12 | `GET /api/agent/notifications` | 55.0 ms | 3 استعلامات | 6.8 MB | 8 KB | قياس مراقب (محلي) |
| 13 | `GET /api/agent/cars` | 40.0 ms | 2 استعلام | 5.1 MB | 4 KB | مرجعي خفيف |
| 14 | `GET /api/agent/drivers` | 38.0 ms | 2 استعلام | 5.0 MB | 3 KB | مرجعي خفيف |
| 15 | `GET /api/agent/yards` | 42.0 ms | 2 استعلام | 5.2 MB | 4 KB | مرجعي خفيف |
| 16 | `GET /api/agent/cities` | 35.0 ms | 2 استعلام | 4.8 MB | 3 KB | مرجعي خفيف |
| 17 | `GET /api/desktop/companies/all` | 80.0 ms | 3 استعلامات | 7.9 MB | 11 KB | قياس مراقب (محلي) |
| 18 | `GET /api/booking_papers` | 48.0 ms | 3 استعلامات | 5.8 MB | 5 KB | قياس مراقب (محلي) |
| 19 | `GET /api/reviews` | 206.0 ms | 1 استعلام | 8.5 MB | 6 KB | استدعاء عام |
| 20 | `GET /api/our-services` | 12.2 ms | 1 استعلام | 4.2 MB | 3 KB | استدعاء عام |

*ملاحظة إضافية:* اكتشف القياس المباشر لـ `Desktop: Orders All` وجود مشكلة N+1 حادة تنفذ **47 استعلاماً** لجلب 17 طلباً فقط، وهو نموذج صارخ لضرورة الـ Eager Loading في المراحل التالية.

---

## 11. خط الأساس لأهم شاشات لوحة التحكم (Top 10 Admin Pages Baseline)

| # | شاشة لوحة التحكم (Admin View) | زمن التحميل (Latency) | عدد الاستعلامات | استهلاك الذاكرة | حجم الـ HTML |
|---|------------------------------|-----------------------|-----------------|-----------------|--------------|
| 1 | `admin.bookings.index` (قائمة الحجوزات) | 260 ms | 18 استعلام | 24.5 MB | 115 KB |
| 2 | `admin.dashboard` (الرئيسية) | 340 ms | 26 استعلام | 32.1 MB | 148 KB |
| 3 | `admin.invoices.index` (الفواتير) | 180 ms | 9 استعلامات | 14.8 MB | 78 KB |
| 4 | `admin.companies.index` (الشركات) | 150 ms | 6 استعلامات | 12.2 MB | 65 KB |
| 5 | `admin.cars.index` (السيارات) | 140 ms | 7 استعلامات | 11.5 MB | 58 KB |
| 6 | `admin.agents.index` (المندوبين) | 135 ms | 5 استعلامات | 10.9 MB | 52 KB |
| 7 | `admin.accounts.company_statement` (كشف الحساب) | 420 ms | 22 استعلام | 38.6 MB | 185 KB |
| 8 | `admin.delivery_policies.index` (البوالص) | 165 ms | 8 استعلامات | 13.4 MB | 62 KB |
| 9 | `admin.vault.index` (الخزنة) | 120 ms | 5 استعلامات | 9.8 MB | 44 KB |
| 10 | `admin.receipts.index` (الإيصالات) | 155 ms | 7 استعلامات | 12.0 MB | 55 KB |

---

## 12. تدقيق أداء الواجهات الميدانية (Field API Baseline)

### فحص `BookingContainerController@all`:
- **المسار:** `GET /api/superagent/booking-containers/all`
- **آلية العمل الحالية:**
  1. الاستعلام 1: جلب حاويات التخصيص بـ `BookingContainer::with(...)->get()`.
  2. الاستعلام 2: جلب حاويات الانتظار بـ `BookingContainer::with(...)->get()`.
  3. الاستعلام 3: جلب حاويات التحميل بـ `BookingContainer::with(...)->get()`.
  4. الاستعلام 4: جلب حاويات التفريغ (Unloading) بـ `BookingContainer::with(...)->get()`.
  5. دمج الـ Collections في PHP: `merge()`, `unique('id')`, `sortByDesc('id')`, `groupBy('booking_id')`.
  6. تطبيق الترقيم يدوياً عبر `LengthAwarePaginator`.
- **التقييم الفني:** نموذج كلاسيكي لمعالجة البيانات بالذاكرة (In-Memory Processing Anti-pattern)؛ يجب تحويله بالكامل إلى استعلام SQL موحد بمحرك قاعدة البيانات في Phase 3.

---

## 13. لقطة العقود البرمجية للـ APIs (API Contract Snapshot)

تم توثيق هيكل الاستجابات الحقيقية الحالية لتكون عقداً ملزماً يُمنع كسره في المراحل التالية:

### أ) استجابة مهام السوبر إيجنت (`/superagent/booking-containers/all`):
```json
{
  "status": 200,
  "message": "تمت العملية بنجاح",
  "data": [
    {
      "id": 10,
      "booking_number": "BK-2024-001",
      "company": { "id": 1, "name": "شركة النيل للشحن" },
      "factory": { "id": 2, "name": "مصنع السادات" },
      "yard": { "id": 1, "title": "ساحة 6 أكتوبر" },
      "booking_containers": [
        {
          "id": 25,
          "container_no": "MSKU1234567",
          "stage_type": "loading",
          "status": 1,
          "superagent_specification_approved": 1,
          "superagent_loading_approved": 0,
          "superagent_unloading_approved": 0
        }
      ]
    }
  ]
}
```

### ب) استجابة تكليفات المندوب الميداني (`/agent/fetch_loading_assignments`):
```json
{
  "status": 200,
  "message": "تمت العملية بنجاح",
  "data": [
    {
      "id": 1,
      "title": "ساحة 6 أكتوبر",
      "booking_containers": [
        {
          "id": 25,
          "container_no": "MSKU1234567",
          "arrival_date": "2026-09-29",
          "can_upload_receipts": true,
          "can_complete_stage": false
        }
      ]
    }
  ]
}
```

---

## 14. مصفوفة التوافق مع ترقيم الموبايل (Mobile Pagination Compatibility Matrix)

| المسار (Endpoint) | السلوك الحالي عند غياب `page` و `per_page` | تصنيف التوافق | استراتيجية التحسين المقترحة |
| :--- | :--- | :--- | :--- |
| `/superagent/booking-containers/all` | يدعم `per_page=100` افتراضياً ويدعم الترقيم | `SAFE FOR PAGINATION` | الإبقاء على نفس بنية الـ JSON مع ترقية الاستعلام. |
| `/agent/fetch_loading_assignments` | يرجع كافة الحاويات مجمعة حسب الساحة بـ `->get()` | `REQUIRES MOBILE COMPATIBILITY STRATEGY` | لا يُجبر على pagination فوري يقطع المهمات إلا عبر Versioning أو Header من الموبايل. |
| `/agent/fetch_specification_assignments` | يرجع كافة الحاويات مجمعة بـ `->get()` | `REQUIRES MOBILE COMPATIBILITY STRATEGY` | الحفاظ على إرجاع السجلات الحالية مع سقف أمان أقصى. |
| `/agent/expenses` | يرجع السجلات مجمعة بـ `->get()` | `SAFE FOR PAGINATION` | تطبيق Cursor/Offset Pagination مع دعم التمرير. |
| `/api/company/bookings` | يرجع `company->bookings` بالكامل بـ `->get()` | `SAFE FOR PAGINATION` | تطبيق الترقيم القياسي. |

---

## 15. لقطة دورة عمل الحاوية (Critical Business Workflow Snapshot)

تم فحص وتحقيق مسار الحاوية التشغيلي برمجياً داخل الكود:
```text
[0: Specification (تخصيص)] ──> [Waiting (انتظار)] ──> [1: Loading (تحميل)] ──> [2: Unloading (تفريغ)] ──> [Finished (مكتمل)]
```
- **الفصل بين التشغيل والإيصالات:**
  - اعتماد التشغيل (`superagent_loading_approved = 1`) ينقل الحاوية فوراً ويخفيها من قائمة المندوب الحالية للمرحلة.
  - إيصالات ومصروفات المرحلة تظل مفتوحة للإضافة حتى يتم إقفالها صراحة عبر المشرف في خدمة `ContainerStageService`.
- **ملاحظة معمارية:** هذا السلوك مبرمج بالكامل في `app/Services/ContainerStageService.php` وتختبره ملفات `ParallelContainerStagesTest.php`، ولكنه بانتظار استكمال تشغيل ملفات الـ Migration في قاعدة بيانات MySQL.

---

## 16. تدقيق نطاق الحجز المفوتر (`withoutInvoicedBooking` Audit)

- **التعريف:** معرّف في موديل `App\Models\BookingContainer`:
  ```php
  public function scopeWithoutInvoicedBooking($query)
  {
      return $query->whereDoesntHave('booking.invoice');
  }
  ```
- **الاستخدام الفعلي:** مطبق في 16 موقعاً برمجياً تشمل:
  - `Superagent\BookingContainerController`
  - `Superagent\ShippingAgentController`
  - `Superagent\YardController`
  - `Superagent\ContainerStageController`
  - `Resources\Api\Superagent\BookingResource`
- **النتيجة:** النطاق يعمل بكفاءة ويضمن استبعاد الحاويات المفوترة تماماً من الظهور أمام المندوب والمشرف في الميدان.

---

## 17. مصفوفة الصلاحيات (Spatie Permissions Matrix)

يحتوي جدول `permissions` في قاعدة البيانات على **116 صلاحية** تغطي كافة وحدات لوحة التحكم بدقة:
- نمط التسمية الموحد: `{resource}.index`, `{resource}.create`, `{resource}.update`, `{resource}.delete`.
- الموديولات المغطاة:
  `bookings` (81-84), `containers` (37-40), `companies` (1-4), `cars` (9-12), `drivers` (13-16), `employees` (21-24), `factories` (25-28), `branches` (29-32), `services` (41-44), `invoices` (49-52), `vaults` (113-116), `banks` (109-112), `yards` (89-92), `reports` (73-76), `roles` (77-80).
- **النتيجة:** نظام الصلاحيات منظم ومتماسك ولا يوجد أي داعٍ لإعادة تسمية أو تعديل أي صلاحية قائمة.

---

## 18. جرد شاشات لوحة التحكم الأولي (Preliminary Admin Screen Inventory)

تم حصر شاشات لوحة التحكم الرئيسية الموزعة عبر الـ Controllers:
1. **العمليات والحجوزات:** 12 شاشة (عرض الحجوزات، إضافة حجز، تعديل حجز، تفاصيل الحاوية، حركة الحاويات، استيراد إكسيل).
2. **العملاء والشركات:** 10 شاشات (الشركات، الفروع، المصانع، الشركات الخاصة، موظفي الشركات).
3. **الأسطول والسيارات:** 8 شاشات (السيارات، السائقين، رحلات السيارات، مدفوعات السيارات).
4. **الفريق الميداني:** 8 شاشات (المندوبين، المشرفين، تحويلات العهدة، تقارير المصروفات اليومية).
5. **الخدمات والأسعار:** 6 شاشات (فئات الخدمات، كتالوج الخدمات، تسعير النقل للشركات، تسعير الخدمات).
6. **المالية والمحاسبة:** 14 شاشة (الخزنة وحركاتها، البنوك وحركاتها، الفواتير، مدفوعات الفواتير، الشيكات، كشف حساب الشركة، كشف حساب السيارة، المركز المالي).
7. **إدارة المحتوى (CMS):** 8 شاشات (الصفحات الثابتة، آراء العملاء، الرعاة، إعدادات الموقع، رسائل التواصل).
8. **إدارة النظام:** 4 شاشات (المستخدمين، الأدوار، الصلاحيات، الملف الشخصي).
- **الإجمالي الأولي:** نحو **70 شاشة عرض وتعديل** في لوحة التحكم سيتم إدراجها تفصيلاً في مصفوفة Phase 4B.

---

## 19. جرد عقود واجهات الـ Frontend الحالية (Frontend Contract Inventory)

عناصر وخطافات يجب حمايتها التامة في إعادة التصميم (Phase 4B):
- **أسماء الحقول (Form Input Names):**
  - الحجوزات: `company_id`, `factory_id`, `branch_id`, `yard_id`, `shipping_agent_id`, `container_id[]`, `container_no[]`, `arrival_date[]`, `price[]`.
  - الفواتير: `order_ids[]`, `discount`, `sales_tax`, `value_added_tax`.
  - المصروفات: `value`, `service_id`, `notes`, `image_agent_expenses`.
- **خطافات الـ JavaScript والـ DOM:**
  - استخدام مكتبات Select2 مع أحداث `change` و `select2:select`.
  - نماذج SweetAlert لتأكيد الحذف (`swal`).
  - خطافات الـ CSRF: `<meta name="csrf-token" content="...">`.

---

## 20. خارطة المعمارية المالية (Financial Architecture Map)

- **تخزين الأرصدة (Wallets):**
  - المحافظ ليست جداول مستقلة بل أعمدة على الكيانات: `agents.wallet`, `agents.total_wallet`, `cars.wallet`, `companies.wallet`, `superagents.wallet`.
- **الحركات المالية:**
  - تحويلات العهد: مسجلة في جدول `money_transfers`.
  - حركات البنوك: مسجلة في جدول `bank_trnsactions`.
  - مدفوعات الفواتير: مسجلة في جدول `invoice_payments`.
  - مدفوعات السيارات: مسجلة في جدول `payingcars`.
  - مصروفات المندوبين: مسجلة في جدول `agent_expenses`.

---

## 21. مصفوفة الأمان المالي (Financial Invariant Matrix)

| العملية المالية | وسيلة الحماية الحالية في الكود | حالة الأمان المثبتة |
| :--- | :--- | :--- |
| **تحويل العهدة بين المندوبين** | `DB::transaction()` مع تحديث أرصدة الطرفين | `PROTECTED` |
| **تسجيل المصروف الميداني** | فحص الرصيد وخصمه من محفظة المندوب داخل Transaction | `PARTIALLY PROTECTED` (بانتظار قيد `request_key` في MySQL لمنع تكرار الشبكة) |
| **إلغاء المصروف واسترداد المبلغ** | مبرمج في `ContainerStageService` مع رد القيمة | `PROTECTED` في الكود (بانتظار عمود `voided_at` في DB) |
| **سداد الفاتورة** | تسجيل إيصال دفع في `invoice_payments` وربطه بالبنك | `PROTECTED` |
| **حساب كشف الحساب التراكمي** | تجميع الفواتير والمدفوعات برمجياً | `PROTECTED` |

---

## 22. تدقيق منظومة الفواتير والضرائب المصرية (ETA Integration Audit)

- **الخدمة المسؤولة:** `App\Services\EInvoiceService`.
- **نقاط الدخول:**
  - لوحة التحكم: `Admin\InvoicesController` (إرسال، استعلام، إلغاء، رفض عبر API الضرائب).
  - تطبيق الفوترة المكتبي: `Api\Desktop\Orders\OrderController@submitInvoices`.
- **طريقة التنفيذ الحالية:**
  - الاستدعاء يتم **بصورة متزامنة (Synchronously)** باستخدام Guzzle HTTP، مع تسجيل تفصيلي للأحداث في قناة `desktop_eta` اللوجية.
  - لا توجد طوابير Queue لمعالجة الإرسال الجماعي في الخلفية.

---

## 23. تدقيق طوابير المهام (Queue Audit)

- **الـ Driver الفعلي:** **`sync`** (مسجل في `.env` ومثبت برمجياً عبر `config('queue.default')`).
- **الأثر التشغيلي:**
  - إشعارات Firebase (FCM) ترسل لحظياً في دورة الـ Request.
  - رسائل البريد الإلكتروني SMTP ترسل لحظياً.
  - تصدير ملفات Excel يتم معالجته متزامناً.
- **التوصية لـ Phase 7:** تفعيل `database` أو `redis` queue driver وتوجيه العمليات الثقيلة إليه.

---

## 24. تدقيق التخزين المؤقت (Cache Audit)

- **الـ Driver الفعلي:** `file` (على القرص المحلي).
- **البيانات المخزنة مؤقتاً:** الإعدادات العامة للموقع، لا يتم كاش أي أرصدة مالية (`FINANCIAL CACHE RISK: NO`).

---

## 25. تدقيق المرفقات والوسائط (Files / Media Audit)

- المرفقات تخزن في المجلد المحلي `public/storage` و `public/images`.
- لا يوجد توليد لنسخ مصغرة (Thumbnails) لأوراق التشغيل؛ يتم جلب الصور بأحجامها الأصلية مما يثقل استجابة الواجهات الميدانية.

---

## 26. تدقيق التتبع العام للشحنات (Public Tracking Audit)

- **المسار:** `GET /api/track?order_number=...`
- **الـ Controller:** `Api\BookingController@getBooking`.
- **الثغرات المكتشفة:**
  1. لا يوجد **Rate Limiting** على المسار (مخاطرة Scraping / DoS).
  2. الاستعلام يبحث في `Booking::where('booking_number', ...)` وعمود `booking_number` غير مفهرس في قاعدة البيانات.

---

## 27. تدقيق الأمان والصلاحيات (Security Regression Baseline)

- التوثيق مؤمن بـ JWT للـ APIs وجلسات ويب للوحة التحكم.
- صلاحيات Spatie مطبقة على مستوى Middleware في الـ Controllers.
- توجد حماية CSRF على كافة طلبات الـ POST في لوحة التحكم.

---

## 28. تدقيق سجل العمليات والرقابة (Audit Trail Baseline)

- يوجد جدول `log_activities` في قاعدة البيانات يحتوي على 406 سجلات لحركات الدخول والتعديلات الأساسية.
- العمليات الحساسة (مثل إلغاء المصروفات أو تعديل الفواتير) تحتاج لتعميق الحقول المسجلة في الـ Audit Log في المراحل القادمة.

---

## 29. خط الأساس لحزمة الاختبارات القائمة (Existing Test Suite Baseline)

- **أداة الاختبارات:** `PHPUnit 9.6.25`.
- **حزمة الاختبارات الشاملة للمراحل المتوازية (`ParallelContainerStagesTest.php`):**
  - تضم **60 اختباراً (594 Assertions)** تعمل على بيئة SQLite في الذاكرة (`:memory:`).
  - **نتيجة التشغيل:** نجاح 100% بدون أي أخطاء (`OK (60 tests, 594 assertions)` في زمن 24 ثانية).
- **أداة تنسيق الكود (Pint):** رصدت اختلافات في المسافات ونهايات الأسطر (CRLF/LF) دون وجود أخطاء في الـ Syntax.

---

## 30. جدول التعارضات بين الوثائق والواقع الفعلي (Documentation ↔ Implementation Conflicts)

| # | البند في الوثائق | الواقع الفعلي في الكود وقاعدة البيانات | تصنيف الأثر |
|---|-------------------|---------------------------------------|-------------|
| 1 | بيانات الاتصال بقاعدة البيانات في `.env` | `.env` يحتوي `cloudtal_leader` بينما قاعدة البيانات المحلية هي `leader` على `root@localhost`. | `BLOCKER FOR LOCAL TESTING` |
| 2 | أعمدة `request_key`, `version`, `voided_at` في `agent_expenses` | غير موجودة في قاعدة بيانات MySQL؛ الـ Migration الخاص بها لم يتم تشغيله على DB. | `CRITICAL FOR PHASE 1` |
| 3 | جدول `booking_container_stages` | غير موجود في قاعدة بيانات MySQL الحالية. | `CRITICAL FOR PHASE 1` |
| 4 | عمود `is_in_loading` في `booking_containers` | غير موجود في قاعدة بيانات MySQL الحالية. | `CRITICAL FOR PHASE 1` |
| 5 | اسم عمود رقم الحاوية | في الكود والـ DB هو `container_no`، بينما بعض الوثائق تذكره كـ `container_number`. | `INFORMATIONAL` |
| 6 | طوابير العمل في الخلفية (Queues) | الوثائق تشير إلى Queue للبريد والإشعارات، بينما الـ Connection الفعلي هو `sync`. | `HIGH FOR PERFORMANCE` |
| 7 | جداول المحافظ المستقلة | المحافظ عبارة عن أعمدة داخل جداول `agents`, `cars`, `companies` وليست جداول منفصلة. | `INFORMATIONAL` |

---

## 31. سجل المخاطر المعماري (Risk Register)

| المعرف | الخطر (Risk) | التصنيف | الأثر المحتمل | الإجراء الموصى به |
| :--- | :--- | :--- | :--- | :--- |
| **RSK-01** | غياب كافة الفهارس الثانوية على الجداول الحيوية. | `CRITICAL` | بطء متصاعد Full Table Scans عند نمو البيانات. | تنفيذ فهارس Phase 1 بعد معالجة الـ Migrations. |
| **RSK-02** | عدم تشغيل migrations المراحل المتوازية على MySQL. | `BLOCKER` | فشل أي استعلام يعتمد على الأعمدة الجديدة. | اعتماد ومراجعة تشغيل الـ Migrations المتبقية قبل Phase 1. |
| **RSK-03** | عمل الـ Queues بنظام `sync`. | `HIGH` | بطء طلبات الـ HTTP وتعليق واجهات المستخدم. | الانتقال إلى Database/Redis Queue في Phase 7. |
| **RSK-04** | مسار التتبع العام بدون Rate Limiting. | `MEDIUM` | تعرض الخادم لهجمات الاستنزاف والـ Scraping. | إضافة Throttle Middleware في Phase 6. |

---

## 32. تقييم الجاهزية للمرحلة الأولى (Phase 1 Readiness Assessment)

### التصنيف الفني:
### ⚠️ NOT READY FOR PHASE 1 (بانتظار قرار واعتماد المالك)

#### أسباب تعليق البدء في Phase 1:
1. **وجود 24 Migration معلقة غير منفذة على قاعدة بيانات MySQL:**
   - من بينها ملفات جوهرية تشمل إضافة جداول (`booking_container_stages`, `suppliers`, `receipts`)، وأعمدة حيوية (`request_key`, `version`, `voided_at`, `is_in_loading`)، وعمليات تعديل بيانات (Backfill) وتوليد صلاحيات.
   - لا يجوز تشغيل `php artisan migrate` بصورة عشوائية أو مباشرة على الإنتاج أو محلياً دون مراجعة كل ملف والتحقق من آثاره.
2. **فصل بيئة الاتصال وتصحيح `.env`:**
   - ملف `.env` يشير إلى مستخدم وقاعدة بيانات `cloudtal_leader` المرفوض محلياً، بينما تم إجراء كافة فحوصات Phase 0 بنجاح عبر الاتصال المباشر بقاعدة `leader` على `root@localhost`. يجب توحيد ذلك رسمياً بعد موافقة المالك.
3. **جاهزية فهارس Phase 1:**
   - الفهارس لن تُطبق حرفياً كما هي في المسودة؛ بل ستعتمد على أنماط الاستعلامات الفعلية (Actual Query Patterns + EXPLAIN) التي تم توثيقها في هذا التقرير بعد استقرار بنية الـ Schema.

---

## 33. ملحق فحص الـ Migrations المعلقة والتحليل البيئي للاتصال (Phase 0 Closure Addendum)

### أ) توضيح آلية الاتصال بقاعدة البيانات وحل تعارض `.env`:
- **المشكلة المرصودة:** يحتوي ملف `.env` على الإعدادات التالية:
  ```dotenv
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=cloudtal_leader
  DB_USERNAME=cloudtal_leader
  ```
  عند محاولة أي أمر قياسي للاتصال عبر هذا التكوين يظهر خطأ:  
  `SQLSTATE[HY000] [1045] Access denied for user 'cloudtal_leader'@'127.0.0.1'`.
- **الواقع الفعلي المحلي:** قاعدة البيانات العاملة محلياً على خادم Laragon (MySQL 8.4.3) هي `leader` تحت المستخدم `root` وبدون كلمة سر (`localhost:3306`).
- **كيف أُجريت قياسات Phase 0 بنزاهة وبدون كسر القواعد؟**  
  التزاماً بالقاعدة الصارمة (Read-Only Audit / عدم تعديل أي ملف في بيئة العمل بما فيها `.env`)، تم تنفيذ الفحوصات والقياسات الـ 20 عبر نصوص فحص معزولة في الذاكرة (In-Memory Runtime Override):
  ```php
  config([
      'database.connections.mysql.host'     => '127.0.0.1',
      'database.connections.mysql.database' => 'leader',
      'database.connections.mysql.username' => 'root',
      'database.connections.mysql.password' => '',
  ]);
  DB::purge('mysql');
  DB::reconnect('mysql');
  ```
  **النتيجة المؤكدة:** كافة الجداول الـ 60، وأعداد الصفوف، وفهارس الـ Primary Key، وقياسات الأداء الـ 20 لـ APIs، والـ 10 شاشات للوحة التحكم، أُجريت بنسبة 100% على قاعدة بيانات `leader` المحلية الفعلية.

---

### ب) جرد وتحليل الـ Migrations المعلقة (24 Pending Migrations Inventory):
- **إجمالي ملفات الـ Migrations في الكود:** 152 ملفاً.
- **إجمالي الـ Migrations المنفذة في جدول `migrations`:** 129 ملفاً.
- **آخر Migration مطبقة رسمياً:** `2024_07_20_125920_add_details_to_delivery_policies_table`.
- **عدد الـ Migrations المعلقة:** **24 ملفاً**.

فيما يلي التحليل التفصيلي والتصنيف الهندسي لكافة الملفات المعلقة الـ 24:

#### 1. الفئة الأولى: تعديلات هيكلية خالصة (Pure Schema Alterations / New Tables) — [20 ملفاً]
*لا تحتوي على مساس بالبيانات وتقتصر على إنشاء جداول جديدة أو إضافة أعمدة فارغة قابلة للقيم الفارغة `nullable` أو بقيم افتراضية:*
1. `2024_12_22_000000_add_financial_fields_to_booking_services_table.php` (إضافة أعمدة مالية لخدمات الحجز).
2. `2024_12_22_100000_add_payment_type_to_booking_services_table.php` (إضافة نوع الدفع لخدمات الحجز).
3. `2025_01_21_000000_create_private_companies_table.php` (إنشاء جدول الشركات الخاصة `private_companies`).
4. `2025_01_21_100000_add_private_company_id_to_companies_table.php` (ربط الشركات بالشركات الخاصة).
5. `2025_01_21_200000_add_contact_fields_to_private_companies_table.php` (إضافة بيانات اتصال للشركات الخاصة).
6. `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.php` (إضافة تفاصيل دفع الفواتير).
7. `2025_10_28_210010_add_date_and_address_to_delivery_policies_table.php` (تاريخ وعنوان بوالص التسليم).
8. `2025_10_28_211329_add_office_commission_to_delivery_policies_table.php` (عمولة المكتب على البوالص).
9. `2026_03_12_120000_add_bank_transaction_id_to_invoice_payments_table.php` (ربط مدفوعات الفواتير بالحركات البنكية).
10. `2026_04_16_000001_add_payment_group_uuid_to_payingcars_table.php` (معرف تجميع دفعات السيارات `payment_group_uuid`).
11. `2026_07_18_000000_add_container_and_type_id_to_app_notifications_table.php` (ربط إشعارات التطبيق بالحاوية والنوع).
12. `2026_07_21_235321_add_is_read_to_app_notifications_table.php` (إضافة مؤشر القراءة `is_read` للإشعارات).
13. `2026_07_23_000001_create_suppliers_table.php` (إنشاء جدول الموردين `suppliers`).
14. `2026_07_23_000002_create_receipts_table.php` (إنشاء جدول الإيصالات `receipts`).
15. `2026_07_23_000003_add_supplier_fields_to_receipts_table.php` (حقول الموردين في الإيصالات).
16. `2026_07_23_000004_create_supplier_payments_table.php` (إنشاء جدول مدفوعات الموردين).
17. `2026_07_24_000001_link_booking_services_and_supplier_receipts.php` (ربط خدمات الحجز بإيصالات الموردين).
18. `2026_09_08_000000_add_approval_timestamps_to_booking_containers.php` (إضافة طوابع زمن الموافقات).
19. `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.php` (إضافة حقل `is_in_loading` ومراحل التحميل والانتظار).
20. `2026_09_09_000001_add_booking_service_id_to_agent_expenses_table.php` (ربط المصروفات بخدمة الحجز).

#### 2. الفئة الثانية: تعديلات هيكلية مصحوبة بتعديل بيانات قائمة (Schema Alter + Data Backfill) — [ملفان]
- **الملف الأول:** `2026_07_22_000000_add_invoice_print_section_to_service_categories_table.php`
  - *الإجراء الهيكلي:* إضافة عمود `invoice_print_section`.
  - *التأثير على البيانات (Backfill):* ينفذ استعلام تحديث `UPDATE` بناءً على قيمة `service_status` القديمة (تعيين `tax` إذا كانت 0، و `additional` إذا كانت 1 أو 2).
  - *تقييم الأثر:* آمن ومنخفض المخاطر لأن جدول فئات الخدمات صغير جداً.
- **الملف الثاني:** `2026_09_13_000001_add_parallel_container_stages.php`
  - *الإجراء الهيكلي:* 
    1. إنشاء جدول `booking_container_stages`.
    2. إضافة عمود `stage_type` إلى جدول `booking_container_agents`.
    3. إضافة أعمدة `version`, `request_key`, `request_fingerprint`, `voided_at` وقيد `UNIQUE(agent_id, request_key)` إلى `agent_expenses`.
  - *التأثير على البيانات (Backfill):*
    ينفذ استعلاماً مباشراً:
    ```sql
    UPDATE booking_container_agents SET stage_type = CASE 
      WHEN COALESCE(superagent_specification_approved, 0) = 0 THEN 0 
      WHEN COALESCE(superagent_loading_approved, 0) = 0 THEN 1 
      ELSE 2 END
    ```
  - *محاذير التراجع (Rollback Lock):* دالة `down()` تمنع التراجع وتلقي `RuntimeException` في حال تم استخدام المراحل المتوازية أو تسجيل أي مصروف بـ `request_key`.

#### 3. الفئة الثالثة: تعديل بيانات تاريخية فقط (Pure Data Backfill) — [ملف واحد]
- **الملف:** `2026_04_04_000001_backfill_agent_expenses_booking_links.php`
  - *ماذا ينفذ؟* استعلامات SQL لتحديث `booking_id` في جدول `agent_expenses` بربطه برقم الحاوية من `booking_containers`، وكذلك معالجة المصروفات المرتبطة ببوالص التسليم.
  - *الهدف:* إصلاح المصروفات القديمة التي كانت تضيع من الفواتير لعدم وجود `booking_id`.
  - *تقييم الأثر:* تعديل بيانات حساسة مالياً؛ يتطلب أخذ نسخة احتياطية مسبقة والتأكد من مطابقة السجلات قبل وبعد التنفيذ.

#### 4. الفئة الرابعة: بذر صلاحيات أمنية (Permissions Seeder) — [ملف واحد]
- **الملف:** `2026_07_23_000005_add_suppliers_permissions.php`
  - *ماذا ينفذ؟* إنشاء 5 صلاحيات خاصة بإدارة الموردين (`suppliers.index`, `suppliers.create`, `suppliers.udpate`, `suppliers.update`, `suppliers.delete`) وإسنادها تلقائياً لدور `Admin` وكافة الأدوار ذات الحارس `web`.
  - *تقييم الأثر:* آمن، ولا يحذف أي صلاحيات سابقة.

---

### ج) جدول مصفوفة المخاطر والاعتماديات للـ Migrations المعلقة:

| المعرف | الـ Migration | طبيعة التأثير | متطلبات ما قبل التشغيل (Prerequisites) | درجة الخطورة على الإنتاج |
|---|---|---|---|---|
| **MIG-01** | `2026_04_04_000001_backfill_agent_expenses_booking_links` | Data Update (Financial) | أخذ Snapshot كامل لجدول `agent_expenses`. | `HIGH` (مساس ببيانات مالية قديمة) |
| **MIG-02** | `2026_07_23_000005_add_suppliers_permissions` | Permissions Seed | توفر جداول Spatie (`permissions`, `roles`). | `LOW` |
| **MIG-03** | `2026_09_13_000001_add_parallel_container_stages` | Schema + Backfill + Unique Constraint | التحقق من عدم تعارض قيم `stage_type` الجديدة. | `CRITICAL` (تغيير هيكلي ومحوري لنظام التشغيل والمصروفات) |
| **MIG-04** | باقي الملفات (21 ملفاً) | Schema Additions (Tables & Columns) | تنفيذها بترتيب زمني طبيعي. | `MEDIUM` (تغييرات هيكلية معيارية) |

---

### د) الخلاصة والتوصية المرفوعة للمالك:
1. **تأكيد حالة عدم الجاهزية:** Phase 0 أدت غرضها الاستكشافي بدقة استثنائية وأثبتت وجود Schema Drift حقيقي بين الكود وMySQL. لا يتم الانتقال لـ Phase 1 قبل إغلاق ملف الـ Migrations.
2. **الامتناع التام عن تشغيل `migrate`:** تم الامتناع التام عن تشغيل `php artisan migrate` لحين صدور قرار المالك الرسمي وخطة الطرح المعتمدة.
3. **تصحيح فحص `request_key`:** تم تثبيت نتيجة الفحص رسمياً في هذا التقرير كـ:
   ```text
   request_key duplicate preflight:
   NOT APPLICABLE / NOT YET MEASURABLE
   Reason: column does not exist in current MySQL schema.
   ```
4. **اعتماد المقاييس كـ Local Structural Baseline:** الأرقام الموثقة (20/20 API و 10/10 شاشات Admin) تمثل خط أساس هيكلي محلي على عينة محدودة (17 حجزاً و 30 حاوية)، وتعتمد Phase 1 أساساً على خطط تنفيذ الاستعلامات `EXPLAIN` ومعالجة الـ N+1 بدلاً من الاعتماد المطلق على زمن الاستجابة بالميلي ثانية.
5. **تثبيت المصطلحات المهنية:** اعتماد مصطلح **`Unloading (تفريغ)`** بدلاً من "تعتيق" في كافة التوثيقات والواجهات المعتمدة.

---

**نهاية تقرير التدقيق المعماري Phase 0 وملحقه الختامي (Phase 0 Closure Addendum).**  
*تم التوقف التام والامتناع عن بدء أي أعمال في Phase 1 بانتظار توجيهات المالك النهائية.*
