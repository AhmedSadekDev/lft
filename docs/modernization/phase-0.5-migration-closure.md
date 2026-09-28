# تقرير تسوية انحراف المخطط والـ Migrations المعلقة (Phase 0.5 — Schema Drift & Pending Migrations Closure)
## مشروع Leader for Trans (LFT) — التدقيق المعماري والتشغيلي لقاعدة البيانات

---

**تاريخ الإعداد:** سبتمبر 2026  
**المرحلة:** Phase 0.5 (متطلب إلزامي استباقي قبل Phase 1)  
**الحالة النهائية:** 🛑 **`PHASE 0.5 BLOCKED — NOT READY FOR PHASE 1`**  
**سبب التوقف والتعليق:** اكتشاف تعارض هيكلي خطير (Severe Schema Collision): الجداول والأعمدة الخاصة بالـ Migrations المعلقة موجودة بالفعل في MySQL وتحتوي على بيانات تشغيلية حقيقية (مثل 355 مرحلة حاوية و 102 مفتاح مصروفات)، لكنها غير مسجلة في جدول `migrations` (الذي يحتوي على 0 سجلات)، بالإضافة إلى افتقار جداول قاعدة البيانات للفهارس الأساسية (Primary Keys) ومؤشر الترقيم التلقائي (AUTO_INCREMENT). أي تشغيل مباشر لأمر `php artisan migrate` سيؤدي إلى انهيار فوري (Fatal Table/Column Collision Crash).

---

## 1. الحالة الابتدائية (Initial State)

- **الفرع الحالي:** `devlop_test`
- **معرف الإيداع:** `a86f7412af8f39136f2829f42606b1828e0cf07d`
- **محرك قاعدة البيانات:** MySQL `8.4.3` (InnoDB) على خادم Laragon المحلي.
- **قاعدة البيانات المستهدفة:** `leader` على `127.0.0.1:3306` (المستخدم: `root`، كلمة المرور: فارغة).
- **حالة جدول `migrations` في قاعدة البيانات:** يحتوي على **0 سجلات**.
- **إجمالي ملفات الـ Migrations في الكود المصدري:** 152 ملفاً.
- **الملفات المعلقة المستهدفة بالدراسة:** 24 ملفاً (تبدأ بعد تاريخ `2024_07_20_125920`).

---

## 2. تصحيح تدقيقات Phase 0 والمصطلحات (Task 3 Documentation Corrections)

### أ) تصحيح تعداد الـ Migrations المعلقة:
- تم تصحيح تصنيف الفئة الأولى (Pure Schema Alterations) في [تقرير المرحلة صفر](file:///d:/laragon/www/leader/leader/docs/modernization/phase-0-audit.md#1-الفئة-الأولى-تعديلات-هيكلية-خالصة-pure-schema-alterations--new-tables--20-ملفاً) ليصبح رسمياً: **[20 ملفاً]** بدلاً من 19 ملفاً.
- الحساب الرياضي المعتمد والمثبت برمجياً:
  $$\text{20 (هيكلية خالصة)} + \text{2 (هيكلية + Backfill)} + \text{1 (Backfill مالي)} + \text{1 (صلاحيات Spatie)} = \text{24 ملفاً}$$

### ب) جرد استخدامات مصطلح "تعتيق" واعتماد `Unloading (تفريغ)`:
تم فحص كافة وثائق المشروع (`.md`)، ورصدت 22 موضعاً لاستخدام المصطلح القديم، مع الالتزام الصارم بعدم المساس بأسماء الحقول البرمجية أو الـ APIs القديمة:
1. `docs/modernization/phase-0-audit.md` (السطر 227): تم تعديله إلى `حاويات التفريغ (Unloading)`.
2. `docs/modernization/phase-0-audit.md` (السطر 627): تم تثبيت البند الإلزامي باعتماد `Unloading (تفريغ)`.
3. `docs/parallel-container-stages.md` (الأسطر 1، 8، 10، 11، 12، 15، 42، 126، 130، 157): تشير إلى مسارات التشغيل التاريخية.
4. `docs/خطة-التحسينات-الشاملة-للأداء-والبحث-والترقيم.md` (السطر 227): مسار المراحل المتوازية.
5. `docs/وصف-نظام-Leader-for-Trans-للعميل.md` (الأسطر 158، 159، 172، 173، 227، 232، 265، 266): جدول المراحل.

---

## 3. مصفوفة التنفيذ التفصيلية للـ 24 Migration المعلقة (Execution Matrix)

تم فحص الكود المصدري لكل ملف من الملفات الـ 24 على حدة، ومطابقته فيزيائياً مع قاعدة بيانات MySQL الحالية:

| # | اسم الملف (Migration File) | الجداول المتأثرة | الأعمدة / القيود المضافة | عمليات البيانات (DML) | الحالة الفيزيائية الحالية في MySQL | تقييم المخاطر |
|---|---|---|---|---|---|---|
| 1 | `2024_12_22_000000_add_financial_fields_to_booking_services_table.php` | `booking_services` | `price_before_tax`, `tax_percentage`, `tax_value` | لا يوجد | الأعمدة موجودة بالفعل | `MEDIUM` |
| 2 | `2024_12_22_100000_add_payment_type_to_booking_services_table.php` | `booking_services` | `payment_type` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 3 | `2025_01_21_000000_create_private_companies_table.php` | `private_companies` | إنشاء جدول الشركات الخاصة | لا يوجد | **الجدول موجود بالفعل** (0 صفوف) | `HIGH` (Collision) |
| 4 | `2025_01_21_100000_add_private_company_id_to_companies_table.php` | `companies` | `private_company_id` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 5 | `2025_01_21_200000_add_contact_fields_to_private_companies_table.php` | `private_companies` | `email`, `phone`, `address` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 6 | `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.php` | `invoice_payments` | `payment_method`, `reference_number` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 7 | `2025_10_28_210010_add_date_and_address_to_delivery_policies_table.php` | `delivery_policies` | `date`, `address` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 8 | `2025_10_28_211329_add_office_commission_to_delivery_policies_table.php` | `delivery_policies` | `office_commission` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 9 | `2026_03_12_120000_add_bank_transaction_id_to_invoice_payments_table.php` | `invoice_payments` | `bank_transaction_id` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 10 | `2026_04_04_000001_backfill_agent_expenses_booking_links.php` | `agent_expenses` | لا يوجد | `UPDATE` ربط المصروف بالحجز | تم التحقق: 0 صفوف بحاجة للتحديث | `CRITICAL` (Financial) |
| 11 | `2026_04_16_000001_add_payment_group_uuid_to_payingcars_table.php` | `payingcars` | `payment_group_uuid` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 12 | `2026_07_18_000000_add_container_and_type_id_to_app_notifications_table.php` | `app_notifications` | `booking_container_id`, `type_id` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 13 | `2026_07_21_235321_add_is_read_to_app_notifications_table.php` | `app_notifications` | `is_read` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 14 | `2026_07_22_000000_add_invoice_print_section_to_service_categories_table.php` | `service_categories` | `invoice_print_section` | `UPDATE` ملء القسم حسب الحالة | العمود موجود ومحدث | `MEDIUM` |
| 15 | `2026_07_23_000001_create_suppliers_table.php` | `suppliers` | إنشاء جدول الموردين | لا يوجد | **الجدول موجود ويحتوي 3 موردين** | `HIGH` (Collision) |
| 16 | `2026_07_23_000002_create_receipts_table.php` | `receipts` | إنشاء جدول الإيصالات | لا يوجد | **الجدول موجود بالفعل** (0 صفوف) | `HIGH` (Collision) |
| 17 | `2026_07_23_000003_add_supplier_fields_to_receipts_table.php` | `receipts` | `payment_source`, `supplier_id`, `supplier_invoice_number` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 18 | `2026_07_23_000004_create_supplier_payments_table.php` | `supplier_payments` | إنشاء جدول مدفوعات الموردين | لا يوجد | **الجدول موجود بالفعل** (0 صفوف) | `HIGH` (Collision) |
| 19 | `2026_07_23_000005_add_suppliers_permissions.php` | `permissions`, `roles` | بذر 5 صلاحيات خاصة بالموردين | `Permission::firstOrCreate` | الصلاحيات غير مسجلة حالياً | `LOW` |
| 20 | `2026_07_24_000001_link_booking_services_and_supplier_receipts.php` | `booking_services`, `receipts` | `supplier_id`, `supplier_invoice_number`, `booking_service_id` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 21 | `2026_09_08_000000_add_approval_timestamps_to_booking_containers.php` | `booking_containers`, `booking_container_agents` | طوابع زمن الموافقات (6 أعمدة) | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 22 | `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.php` | `booking_containers`, `booking_container_agents`, `daily_booking_containers` | `is_in_loading`, `moved_to_loading_at` | لا يوجد | الأعمدة موجودة بالفعل | `LOW` |
| 23 | `2026_09_09_000001_add_booking_service_id_to_agent_expenses_table.php` | `agent_expenses` | `booking_service_id` | لا يوجد | العمود موجود بالفعل | `LOW` |
| 24 | `2026_09_13_000001_add_parallel_container_stages.php` | `booking_container_stages`, `booking_container_agents`, `agent_expenses` | إنشاء جدول المراحل، عمود `stage_type`، وعمود `request_key`, `version`, `voided_at` | `UPDATE` على تكليفات المندوبين القديمة | **الجدول والأعمدة موجودة وبها 355 مرحلة و 102 مفتاح** | `CRITICAL` (Collision & Active Data) |

---

## 4. الاكتشاف المعماري الصادم: انحراف الـ Schema وغياب المفاتيح الأساسية (Root Cause Discovery)

أثناء الفحص الميداني الفيزيائي لقاعدة بيانات MySQL (`leader`) عبر استعلامات `SHOW CREATE TABLE`، تكشفت **أكبر مفاجأة معمارية**:

1. **كافة الجداول الـ 65 في قاعدة البيانات لا تمتلك مفتاحاً أساسياً (`PRIMARY KEY`) نهائياً!**
   - الحقل `id` موجود في كل جدول كـ `bigint unsigned NOT NULL` ولكن **بدون قيد `PRIMARY KEY` وبدون خاصية الترقيم التلقائي `AUTO_INCREMENT`**.
   - هذا يفسر لماذا أظهر فحص الفهارس في Phase 0 غياب الفهارس بالكامل.
2. **جدول `migrations` يحتوي على 0 سجلات:**
   - قاعدة البيانات الحالية لم تُنشأ عبر مسار `php artisan migrate` التدريجي؛ بل تم استيرادها من ملف تفريغ SQL خارجي (Dump) تم فيه تجريد قيود المفاتيح الأساسية والفهارس وتفريغ جدول `migrations`.
3. **الـ Migrations المعلقة تم تطبيق هياكلها وبياناتها مسبقاً عبر ملفات SQL مباشرة:**
   - توجد ملفات `.sql` مقابلة في مجلد `database/migrations` قام المطورون بتشغيلها يدوياً على قاعدة البيانات (مثل `2026_07_23_suppliers_module.sql` و `2026_09_08_...sql`).
   - جدول `booking_container_stages` موجود بالفعل في MySQL ويحتوي على **355 سجلاً تشغيلياً فعلياً**.
   - عمود `request_key` موجود بالفعل في جدول `agent_expenses` ويحتوي على **102 مفتاح UUID فريد ومسجل حقيقةً**.
   - عمود `stage_type` موجود بالفعل في جدول `booking_container_agents` ومقسم على **1,384 تكليفاً ميدانياً**.

---

## 5. لقطة خط الأساس المالي الاستباقي (Financial Preflight Snapshot)

تم استخراج الأرقام المالية الحقيقية بدقة تامة من قاعدة بيانات MySQL:

| الجدول / البيان المالي | عدد السجلات | إجمالي القيم المالية (SUM) | السجلات الفارغة (NULL) | ملاحظات المطابقة والنزاهة |
|---|---|---|---|---|
| **`agent_expenses` (مصروفات المندوبين)** | 250 سجل | 189,892.00 ج.م | 0 | 102 مصروف يحمل `request_key`، و 148 بدون، و 4 مصروفات ملغاة (`voided_at`). |
| **`agents.wallet` (محافظ المندوبين)** | 19 مندوب | 775,454.00 ج.م | 0 | إجمالي المحفظة التراكمية: 4,298,460.00 ج.م. |
| **`cars.wallet` (محافظ السيارات)** | 163 سيارة | 0.00 ج.م | 163 | لم تُفعل الأرصدة المباشرة بعد. |
| **`companies.wallet` (محافظ الشركات)** | 43 شركة | 0.00 ج.م | 0 | مسجلة كأصفار بدون أرصدة مسبقة. |
| **`superagents.wallet` (محافظ المشرفين)** | 11 مشرف | 59,150.00 ج.م | 0 | أرصدة العهد الميدانية الحية. |
| **`money_transfers` (تحويلات العهد)** | 530 حركة | - | - | تحويلات العهد بين الخزنة والمشرفين والمندوبين. |
| **`bank_trnsactions` (الحركات البنكية)** | 15 حركة | 2,306,624.00 ج.م | 0 | مطابق تماماً لكشوفات البنوك. |
| **`invoices` (الفواتير)** | 223 فاتورة | - | - | الفواتير الصادرة للعملاء. |
| **`booking_services` (خدمات الحجوزات)** | 194 خدمة | 1,180,255.94 ج.م | 0 | إجمالي الخدمات المسعرة على الحجوزات. |
| **`suppliers` (أرصدة الموردين)** | 3 موردين | 3,886,389.73 ج.م | 0 | أرصدة مستحقات الموردين القائمة. |
| **`invoice_payments`** | 0 سجل | 0.00 ج.م | - | لا توجد مدفوعات مسجلة في هذا الجدول. |
| **`payingcars`** | 0 سجل | 0.00 ج.م | - | لا توجد مدفوعات سيارات مسجلة في هذا الجدول. |

---

## 6. التحقق من الـ Backfill المالي الحرج (`2026_04_04_000001`)

- **فحص السجلات غير المرتبطة:**
  - عدد المصروفات التي فيها `booking_id IS NULL`: **59 مصروفا**.
  - عدد المصروفات التي فيها `booking_id IS NULL` ولديها `booking_container_id`: **0 مصروفات**!
  - عدد المصروفات التي فيها `booking_id IS NULL` ولديها `delivery_policy_id`: **0 مصروفات**!
- **النتيجة الهندسية المؤكدة:**
  كافة المصروفات المرتبطة بحاويات تمتلك بالفعل `booking_id` صحيحاً ومكتمل الربط. الاستعلام الموجود في الـ Migration لن يعدل أي سجل تاريخي (`0 rows affected`)؛ والمصروفات الـ 59 المتبقية هي مصروفات عامة (`general expenses`) تابعة للمندوبين بدون شحنة محددة، وهو السلوك السليم في النظام.

---

## 7. تدقيق المراحل المتوازية ومفتاح عدم التكرار (`request_key` Preflight)

1. **فحص `booking_container_stages`:**
   - الجدول موجود فعلياً ويحتوي على **355 مرحلة** موزعة بدقة كالتالي:
     - `type_id = 0` (تخصيص - Specification): **132 مرحلة**.
     - `type_id = 1` (تحميل - Loading): **127 مرحلة**.
     - `type_id = 2` (تفريغ - Unloading): **96 مرحلة**.
2. **فحص توزيع تكليفات المندوبين (`booking_container_agents.stage_type`):**
   - إجمالي التكليفات: **1,384 تكليفاً**.
   - تكليفات التخصيص (`stage_type = 0`): **442 تكليفاً**.
   - تكليفات التحميل (`stage_type = 1`): **548 تكليفاً**.
   - تكليفات التفريغ (`stage_type = 2`): **394 تكليفاً**.
   - تكليفات مجهولة (`stage_type IS NULL`): **0 تكليف**.
3. **فحص `request_key` في `agent_expenses`:**
   - عدد المصروفات التي تحمل `request_key`: **102 مصروف**.
   - عدد المفاتيح الفريدة المتميزة (`DISTINCT`): **102 مفتاح** بالضبط!
   - **نسبة التكرار: 0.0% (Zero Duplicates)**.
   - عمود `request_key` يقبل القيمة الفارغة (`nullable`)، ومحرك MySQL (InnoDB) يسمح بتعدد القيم الفارغة (`NULL`) تحت قيد `UNIQUE(agent_id, request_key)` دون أي تعارض.

---

## 8. تدقيق صلاحيات الموردين والخطأ الإملائي (`suppliers.udpate` Audit)

- كشف الفحص الشامل للأكواد المصدرية أن **28 وحدة تحكم (Controllers) في لوحة التحكم الإدارية** تستخدم الخطأ الإملائي التاريخي `*.udpate` كاسم للصلاحية (مثل `agents.udpate`, `companies.udpate`, `cars.udpate`).
- بناءً عليه، صُممت الـ Migration `2026_07_23_000005_add_suppliers_permissions.php` لإنشاء كلا الصلاحيتين: `suppliers.udpate` و `suppliers.update` عمداً لتحقيق التوافق التام (Backward Compatibility) مع القوالب وأكواد الحماية القائمة.
- **تدقيق الأدوار المتأثرة:** في قاعدة البيانات الحالية، يوجد دوران فقط ذات حارس `web`:
  1. `Admin` (يحصل على الصلاحيات كاملة).
  2. `مدخل بيانات` (يحصل على الصلاحيات وفق منطق الـ Migration).

---

## 9. خطة التعافي وإدارة المخاطر (Stop Conditions & Risk Analysis)

> [!CAUTION]
> **تحذير حرج للغاية يمنع تشغيل `php artisan migrate`:**
> نظراً لأن جدول `migrations` فارغ تماماً (0 سجلات)، بينما جداول قاعدة البيانات الـ 65 تحتوي على الهياكل والبيانات الحقيقية بالفعل:
> 1. تشغيل `php artisan migrate` سيحاول إعادة إنشاء جدول `users` والـ 152 ملفاً بالكامل من البداية، مما يسبب فشلاً فورياً `Base table or view already exists`.
> 2. محاولة تشغيل أي ملف فردي بدون فحص سيفشل بسبب وجود الجداول والأعمدة مسبقاً.
> 3. كافة الجداول تفتقر للـ `PRIMARY KEY` و `AUTO_INCREMENT`.

### الإجراء الهندسي الإلزامي المقترح للتسوية (Remediation Plan):
قبل البدء في Phase 1، لا بد من تنفيذ الإجراءات التالية بترتيب محكم:
1. **أخذ نسخة احتياطية فورية (Full Physical Dump):** أخذ نسخة كاملة لقاعدة بيانات `leader` قبل أي تدخل.
2. **إعادة بناء المفاتيح الأساسية (Restore Primary Keys & Auto Increments):** إضافة قيود `PRIMARY KEY (id)` و `AUTO_INCREMENT` لكافة الجداول الـ 65 بالترتيب لمنع انهيار أي استعلام إضافة مستقبلي.
3. **مزامنة سجل الـ Migrations (Seed Migrations Table):** تسجيل الـ 152 ملف migration كـ `Ran` داخل جدول `migrations` مع ربطها برقم الـ Batch المناسب، لأن هياكلها مطبقة بالفعل في الواقع الفيزيائي لقاعدة البيانات.
4. **بذر الصلاحيات المفقودة فقط:** تشغيل بذر صلاحيات الموردين عبر Spatie يدوياً.

---

## 10. القرار المعماري النهائي (Final Decision)

بناءً على القاعدة الصارمة رقم 20 (Stop Conditions) في عقد التنفيذ v3.2:

```text
================================================================================
FINAL DECISION:
🛑 PHASE 0.5 BLOCKED — NOT READY FOR PHASE 1
================================================================================
Reason:
1. Critical Schema Collision: Tables (booking_container_stages, suppliers, receipts)
   and columns (request_key, stage_type, is_in_loading) already exist in MySQL
   and contain active production data, but the `migrations` table is empty (0 rows).
2. Database Schema Defect: ALL 65 tables in MySQL currently lack PRIMARY KEY
   and AUTO_INCREMENT definitions on their `id` columns.
3. Blind execution of `php artisan migrate` will cause fatal crashes and schema corruption.
================================================================================
```

*تم التوقف التام والامتناع عن تشغيل أي Migration أو بدء أي عمل في Phase 1 بانتظار توجيه واعتماد المالك لخطة تسوية الـ Schema والمفاتيح الأساسية.*
