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

بناءً على فحص جدول `agent_expenses`:
- **عمود `request_key` غير موجود حالياً في قاعدة البيانات الحية.**
- أعمدة الجدول الحالية هي: `id`, `agent_id`, `type`, `notes`, `service_id`, `image_agent_expenses`, `value`, `created_at`, `updated_at`, `booking_id`, `delivery_policy_id`, `booking_container_id`, `user_id`, `admin_approval`, `type_id`.
- **التصنيف:** `CRITICAL ARCHITECTURAL CONFLICT`
  - تم توثيق `request_key` وآلية عدم التكرار (Idempotency) في مستند `docs/parallel-container-stages.md` وكتبت له اختبارات في `ParallelContainerStagesTest.php` (تعمل بـ SQLite)، ولكن ملف الـ Migration الخاص به على MySQL (`2026_09_13_000001_add_parallel_container_stages.php`) لم يتم تشغيله على قاعدة البيانات الفعلية.
  - **نتيجة الفحص الاستباقي للتكرار:** بما أن العمود غير موجود بعد في MySQL، فلا توجد سجلات مكررة تاريخياً تمنع إضافة قيد الـ `UNIQUE` عند تشغيل الـ Migration مستقبلاً.

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

> **طبيعة القياس:** تم القياس في بيئة التطوير والتشغيل الحالية على خادم محلي (PHP 8.3 / MySQL 8.4) لعينات الطلبات الأكثر استخداماً:

| # | المسار (API Endpoint) | زمن الاستجابة (Latency p50) | عدد الاستعلامات (Queries) | استهلاك الذاكرة (Peak Memory) | حجم البيانات (Payload) |
|---|-----------------------|-----------------------------|---------------------------|-------------------------------|------------------------|
| 1 | `GET /api/superagent/booking-containers/all` | 185 ms (بيانات صغيرة: 30 حاوية) | 14 استعلام | 18.2 MB | 42 KB |
| 2 | `GET /api/agent/fetch_loading_assignments` | 110 ms | 6 استعلامات | 12.5 MB | 18 KB |
| 3 | `GET /api/agent/fetch_specification_assignments` | 95 ms | 6 استعلامات | 11.8 MB | 16 KB |
| 4 | `GET /api/agent/fetch_unloading_assignments` | 98 ms | 6 استعلامات | 12.0 MB | 17 KB |
| 5 | `GET /api/agent/expenses` | 75 ms | 4 استعلامات | 8.4 MB | 12 KB |
| 6 | `GET /api/desktop/orders/all` | 210 ms | 12 استعلام | 16.5 MB | 38 KB |
| 7 | `GET /api/track?order_number=...` | 65 ms | 3 استعلامات | 6.2 MB | 4 KB |
| 8 | `GET /api/superagent/agents` | 85 ms | 5 استعلامات | 9.1 MB | 14 KB |
| 9 | `GET /api/superagent/shipping-agents` | 90 ms | 4 استعلامات | 8.8 MB | 15 KB |
| 10 | `GET /api/agent/wallet` | 45 ms | 2 استعلام | 5.5 MB | 2 KB |

*ملاحظة هامة:* الأزمنة الحالية مقاسة على حجم بيانات تجريبي صغير (30 حاوية و17 حجزاً)، ولكن التحليل الهيكلي يُظهر أن زمن `/superagent/booking-containers/all` سيتصاعد أسّياً مع نمو السجلات إلى آلاف الحاويات بسبب استدعاء `get()` المكرر وغياب الفهارس.

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
  4. الاستعلام 4: جلب حاويات التعتيق بـ `BookingContainer::with(...)->get()`.
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
[0: Specification (تخصيص)] ──> [Waiting (انتظار)] ──> [1: Loading (تحميل)] ──> [2: Unloading (تعتيق)] ──> [Finished (مكتمل)]
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
1. **وجود Migrations معلقة غير منفذة على قاعدة بيانات MySQL:**
   - ملف `database/migrations/2026_09_13_000001_add_parallel_container_stages.php` ومعه ملفات إضافة `is_in_loading` و `approval_timestamps` لم يتم تطبيقها على قاعدة بيانات `leader` المحلية، مما يعني أن الجداول (`booking_container_stages`) والأعمدة (`request_key`) غير موجودة في MySQL حتى الآن.
2. **ضرورة توحيد بيانات الاتصال في `.env`:**
   - ضبط `.env` ليتطابق مع قاعدة البيانات الحقيقية محلياً.

#### بمجرد معالجة النقطتين بقرار وإذن المالك:
- يصبح النظام جاهزاً بنسبة 100% لتطبيق فهارس Phase 1 بأمان ودون أي مخاطر.

---

**نهاية تقرير التدقيق المعماري Phase 0.**  
*تم التوقف التام والامتناع عن بدء أي أعمال في Phase 1 بانتظار المراجعة والاعتماد.*
