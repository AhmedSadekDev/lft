# البصمة الجنائية لقاعدة البيانات والتدقيق التفصيلي للمخطط (Phase 0.5 — Forensic Schema Fingerprint & Migration-by-Migration Audit)
## مشروع Leader for Trans (LFT) — التدقيق الفيزيائي المقارن المعمق

---

**تاريخ الإعداد:** سبتمبر 2026  
**طبيعة المرحلة:** تدقيق جنائي استكشافي للقراءة فقط (Strict Read-Only Forensic Audit) — حظر تام لأي تعديل على الكود أو قاعدة البيانات.  
**الحالة النهائية:** 🏁 **`FORENSIC AUDIT COMPLETE — REMEDIATION PLAN CAN NOW BE DESIGNED`**  
*(التدقيق الجنائي مكتمل بنسبة 100% — تم فك كافة التناقضات وحصر الواقع الفيزيائي، والمنظومة جاهزة لتصميم خطة التسوية دون تنفيذ أي تعديل حالياً).*

---

## 1. إثبات بيئة التشغيل والهوية (Environment & Provenance Identity)

- **الفرع الحالي (Git Branch):** `devlop_test`
- **معرف الإيداع (Commit Hash):** `a86f7412af8f39136f2829f42606b1828e0cf07d`
- **حالة شجرة العمل (`git status --short`):**
  - `M docs/modernization/phase-0-audit.md` (تصحيح التعداد والمصطلحات المعتمدة).
  - `?? docs/modernization/phase-0.5-migration-closure.md` (تقرير إغلاق Phase 0.5 الاستباقي).
  - `?? docs/modernization/evidence/phase-0.5/` (ملفات الأدلة الآلية الرقمية JSON).
  - *ضمانة التدقيق:* لا يوجد أي تعديل على كود التطبيق، أو ملفات الـ `.env`، أو أي ملفات تشغيلية.
- **إصدار PHP:** `8.3.26` (CLI / x64).
- **إصدار إطار العمل Laravel:** `9.52.20`.
- **محرك قاعدة البيانات:** MySQL Community Server `8.4.3` (InnoDB) على خادم Laragon المحلي.
- **المنفذ والمضيف:** `127.0.0.1:3306`.
- **قاعدة البيانات الفعلية المربوطة والمفحوصة (`SELECT DATABASE()`):** **`leader`** حصرًا.
- **إجمالي الجداول الفيزيائية:** **65 جدولاً**.
- **عدد السجلات في جدول `migrations`:** **0 سجلات**.

---

## 2. حل التناقض الجوهري بين Phase 0 و Phase 0.5 (Contradictions Resolved)

كشف التحقيق الجنائي الدقيق سبب التناقض الكامل بين نتائج تقرير Phase 0 السابق ونتائج Phase 0.5:

### أ) لماذا سجل Phase 0 أن الجداول بها Primary Key وأن الميزات الحديثة مفقودة؟
1. **طبيعة الاستعلام السابق:** اعتمد فحص Phase 0 على استعلام `SHOW INDEX FROM table WHERE Key_name != 'PRIMARY'` لرصد الفهارس الثانوية، وحين كانت النتيجة صفراً، افترض التقرير أن المفاتيح الأساسية (`PRIMARY KEY`) الطبيعية موجودة، دون التحقق من الـ DDL الخام لكل جدول عبر `SHOW CREATE TABLE`.
2. **تقديرات أعداد السجلات:** اعتمد تقرير Phase 0 على جدول `information_schema.TABLES` وحقل `TABLE_ROWS`، وهو في محرك InnoDB مجرد **تقدير إحصائي تقريبي (Rough Statistical Estimate)** وليس تعداداً حقيقياً؛ حيث كان يعطي مثلاً 15 مصروفا و 23 فاتورة و 17 حجزا.
3. **افتراض الـ Migrations:** اعتمد Phase 0 على أن الـ Migrations توقفت عند 2024 لعدم وجود تسجيلات لها في جدول `migrations`، فاستنتج خطأً أن الجداول والأعمدة غير موجودة في قاعدة البيانات.

### ب) الإثبات القاطع لحادثة الاستيراد (The 22:27 Import Event):
أثبت فحص طوابع الإنشاء الفيزيائية للجداول (`information_schema.TABLES.CREATE_TIME`) حقيقة حاسمة:
- **تاريخ إنشاء كافة الجداول الـ 65:** **28 سبتمبر 2026 ما بين الساعة `22:27:13` والساعة `22:28:09`**!
- تم استيراد ملف تفريغ حديث (Database Dump) لقاعدة البيانات أثناء الجلسة.
- هذا الاستيراد هو الذي نقل قاعدة البيانات إلى الحالة الحديثة المطابقة لسبتمبر 2026 (حيث ظهرت الـ 355 مرحلة تشغيل للحاويات، و 102 مفتاح مصروفات، وجداول الموردين والشركات الخاصة).
- **لكن هذا الـ Dump كان مجرداً من قيود الفهارس والمفاتيح الأساسية (Stripped Dump)، وجاء بجدول `migrations` فارغ تماماً (0 سجلات).**

---

## 3. مصفوفة التحقق من المفاتيح الأساسية (65-Table Primary Key Matrix)

تم إجراء تدقيق فيزيائي شامل وقراءة سلامة الحقل `id` لكافة الجداول الـ 65، ونتجت المصفوفة الكاملة (المحفوظة رقمياً في [id-integrity.json](file:///d:/laragon/www/leader/leader/docs/modernization/evidence/phase-0.5/id-integrity.json)):

### خلاصة تدقيق الـ PK:
- **الجداول التي تمتلك عمود `id`:** **60 جدولاً**.
  - نوع العمود: `bigint unsigned NOT NULL` في كافة الجداول الـ 60.
  - وجود قيد `PRIMARY KEY` فيزيائي في MySQL: **غير موجود في أي جدول (0/60)**.
  - وجود خاصية `AUTO_INCREMENT`: **غير موجودة في أي جدول (0/60)**.
  - تعداد القيم الفارغة (`null_id_count`): **0 في كافة الجداول الـ 60**.
  - تعداد المجموعات المكررة (`duplicate_id_count`): **0 في كافة الجداول الـ 60**.
  - التصنيف الهندسي: **`SAFE PK CANDIDATE` (60/60 جدولاً مؤهل تماماً لإضافة PK بأمان مطلق)**.

- **الجداول التي لا تمتلك عمود `id` أصلاً (5 جداول):**
  1. `model_has_permissions` (0 صفوف): جدول وسيط لـ Spatie؛ المفتاح الطبيعي المتوقع له مركب: `(permission_id, model_id, model_type)`.
  2. `model_has_roles` (8 صفوف): جدول وسيط لـ Spatie؛ المفتاح الطبيعي المتوقع له مركب: `(role_id, model_id, model_type)` — تم التحقق: التكرار = 0.
  3. `role_has_permissions` (201 صف): جدول وسيط لـ Spatie؛ المفتاح الطبيعي المتوقع له مركب: `(permission_id, role_id)` — تم التحقق: التكرار = 0.
  4. `password_resets` (0 صفوف): جدول مؤقت لاستعادة كلمات المرور؛ لا يتطلب PK بل فهرساً على `email`.
  5. `telescope_entries` (68,940 صف): جدول سجلات مراقبة Laravel Telescope؛ المفتاح الطبيعي المتوقع له هو عمود `sequence` (تم التحقق: التكرار = 0).

---

## 4. تدقيق جدوى الترقيم التلقائي برمجياً (AUTO_INCREMENT Feasibility Audit)

تم فحص كامل شجرة الكود في مجلد `app/` للتحقق من كيفية تعامل الـ Models مع الـ `id`:
- **البحث عن التعيين اليدوي للـ ID (`'id' => ...`):** **0 حالات**. لا يوجد أي Model أو Controller يسند الـ `id` يدوياً عند الإنشاء.
- **البحث عن `$incrementing = false`:** **0 حالات**. كافة نماذج Eloquent تعتمد السلوك القياسي للترقيم التلقائي.
- **البحث عن تخصيص اسم المفتاح الأساسي `$primaryKey`:** وجد فقط في `Container.php` كـ `$primaryKey = 'id'` وهو الاسم القياسي.
- **النتيجة القطعية:** الكود البرمجي للتطبيق يفترض ويعتمد بنسبة 100% على وجود خاصية `AUTO_INCREMENT` للمفتاح الأساسي. غيابها حالياً هو خلل في بنية الداتابيز المستوردة يهدد بفشل أي عملية `Model::create()` جديدة.

---

## 5. التدقيق المقارن لملفات الـ Migrations الـ 152 (Migration-by-Migration Audit)

تم الفحص الثابت (Static Analysis) لكافة الـ 152 ملف migration في مجلد `database/migrations/` ومطابقتها دلالياً مع واقع جداول وأعمدة MySQL (ملف الأدلة: [migration-comparison.json](file:///d:/laragon/www/leader/leader/docs/modernization/evidence/phase-0.5/migration-comparison.json)):

### التوزيع النهائي لتصنيف الـ 152 ملفاً:

| التصنيف الدلالي | العدد | النسبة | التفسير الهندسي |
|---|---|---|---|
| **`MATCHED`** | **65 ملفاً** | 42.8% | الجداول والأعمدة المعدلة موجودة فيزيائياً في قاعدة البيانات بكامل مواصفاتها. |
| **`PARTIALLY MATCHED`** | **14 ملفاً** | 9.2% | الجداول والأعمدة موجودة بالفعل، ولكن القيود أو الفهارس أو الـ PK/FK المصاحبة لها مفقودة فيزيائياً. |
| **`SUPERSEDED`** | **68 ملفاً** | 44.7% | ملفات تاريخية قامت بإنشاء أو تعديل أو إعادة تسمية أو حذف جداول تم استبدالها بملفات أحدث (مثل دمج حجوزات 2 و 3 في جدول `bookings` وإعادة هيكلة الفواتير). |
| **`DATA EFFECT NOT PROVABLE`** | **2 ملفان** | 1.3% | ملفات تعديل بيانات بحتة (Backfill و Permission Seeder)؛ الجداول قائمة ولكن أثر الـ DML لا يمكن إثباته من الـ Schema المجرد. |
| **`NOT MATCHED`** | **3 ملفات** | 2.0% | أعمدة قديمة جداً أضيفت في بدايات 2023 وحُذفت أو تم استبدالها لاحقاً بملفات أخرى (تعتبر ضمن الـ Superseded وظيفياً). |
| **`AMBIGUOUS`** | **0** | 0.0% | لا يوجد أي ملف غامض أو غير مفهوم الأثر. |

---

## 6. التدقيق الجنائي للفهارس والقيود الأجنبية (Indexes & Foreign Keys Audit)

تمت المقارنة بين ما تفترضه وتنشئه ملفات الـ Migrations وبين ما هو موجود فيزيائياً في MySQL (ملفات الأدلة: [schema-indexes.json](file:///d:/laragon/www/leader/leader/docs/modernization/evidence/phase-0.5/schema-indexes.json) و [schema-foreign-keys.json](file:///d:/laragon/www/leader/leader/docs/modernization/evidence/phase-0.5/schema-foreign-keys.json)):

- **المفاتيح الأجنبية المتوقعة (Expected Foreign Keys):** **130 قيد مفتاح أجنبي**.
- **المفاتيح الأجنبية الفيزيائية الحالية (Physical FKs):** **0 قيد**!
- **الفهارس الثانوية المتوقعة من الـ Migrations:** **5 فهارس** (مثل `unique(['booking_container_id', 'type_id'])` و `unique(['agent_id', 'request_key'])`).
- **الفهارس الثانوية الفيزيائية الحالية:** **0 فهرس**!
- **الاستنتاج الجنائي:** قاعدة البيانات الحالية مجردة بنسبة 100% من كافة القيود المرجعية والفهارس الثانوية والمفاتيح الأساسية (`ZERO CONSTRAINTS`).

---

## 7. فحص ملفات الـ SQL اليدوية وتقاطعها (Repository SQL Overlap Audit)

تم حصر وتحليل كافة ملفات الـ SQL الموجودة داخل مجلد `database/migrations/` (ملف الأدلة: [sql-overlap.json](file:///d:/laragon/www/leader/leader/docs/modernization/evidence/phase-0.5/sql-overlap.json)):

| ملف الـ SQL | الـ Migration المقابلة | ما يطبقه الملف | هل مطبق في MySQL؟ | درجة الثقة |
|---|---|---|---|---|
| `2024_05_25_111836_add_feilds_to_companies_table.sql` | `2024_05_25_111836_...php` | أعمدة `wallet`, `is_taxed` في `companies` | نعم | `HIGH` |
| `2024_12_22_000000_add_financial_fields_to_booking_services_table.sql` | `2024_12_22_000000_...php` | أعمدة مالية في `booking_services` | نعم | `HIGH` |
| `2024_12_22_100000_add_payment_type_to_booking_services_table.sql` | `2024_12_22_100000_...php` | عمود `payment_type` في `booking_services` | نعم | `HIGH` |
| `2025_01_20_000000_add_accounts_permissions.sql` | - | بذر صلاحيات الحسابات | نعم | `MEDIUM` |
| `2025_01_21_000000_create_private_companies_table.sql` | `2025_01_21_000000_...php` | إنشاء جدول `private_companies` | نعم | `HIGH` |
| `2025_01_21_100000_add_private_company_id_to_companies_table.sql` | `2025_01_21_100000_...php` | عمود `private_company_id` | نعم | `HIGH` |
| `2025_01_21_200000_add_contact_fields_to_private_companies_table.sql` | `2025_01_21_200000_...php` | أعمدة التواصل بالشركات الخاصة | نعم | `HIGH` |
| `2025_01_25_000000_add_payment_fields_to_invoice_payments_table.sql` | `2025_01_25_000000_...php` | أعمدة الدفع في `invoice_payments` | نعم | `HIGH` |
| `2026_07_23_fix_suppliers_permissions.sql` | `2026_07_23_000005_...php` | بذر صلاحيات الموردين | لا (مفقودة بجدول permissions) | `HIGH` |
| `2026_07_23_suppliers_module.sql` | `2026_07_23_000001` إلى `000004` | جداول `suppliers`, `receipts`, `supplier_payments` | نعم (الجداول موجودة) | `HIGH` |
| `2026_07_24_000001_link_booking_services_and_supplier_receipts.sql` | `2026_07_24_000001_...php` | أعمدة ربط الموردين بالإيصالات | نعم | `HIGH` |
| `2026_09_08_000000_add_approval_timestamps_to_booking_containers.sql` | `2026_09_08_000000_...php` | طوابع زمن الموافقات | نعم | `HIGH` |
| `2026_09_08_000001_add_waiting_and_loading_stage_to_booking_containers.sql`| `2026_09_08_000001_...php` | عمود `is_in_loading` ومراحل التحميل | نعم | `HIGH` |

---

## 8. مطابقة الفارق في تعداد الجداول (60 مقابل 65 جدولاً)

تم حسم الفارق الدقيق بين الـ 60 جدولاً الموثقة في Phase 0 والـ 65 جدولاً الحالية:
- **الجداول الـ 5 المضافة:**
  1. `booking_container_stages` (جدول المراحل المتوازية — أنشئ في سبتمبر 2026).
  2. `private_companies` (جدول الشركات الخاصة — أنشئ في موديول الشركات الخاصة).
  3. `suppliers` (جدول الموردين).
  4. `receipts` (جدول إيصالات الموردين).
  5. `supplier_payments` (جدول مدفوعات الموردين).
- **التفسير:** هذه الجداول تم إدخالها مع الـ Dump الحديث المستورد في تمام الساعة 22:27، ولم تكن ضمن عينة الـ Dump الأقدم التي فُحصت في مطلع Phase 0.

---

## 9. مطابقة الفارق في تعداد الصفوف التشغيلية والمالية

تم التحقق من سبب القفزة في أعداد الصفوف (مثل `agent_expenses` من 15 إلى 250):
1. **السبب الأول:** كان تقرير Phase 0 يقرأ تقديرات `TABLE_ROWS` الإحصائية التقريبية من `information_schema.TABLES` والتي لا تعكس الحقيقة في محرك InnoDB.
2. **السبب الثاني:** قاعدة البيانات الحالية تحتوي على بيانات تشغيل حقيقية تغطي الفترة حتى 17 سبتمبر 2026، بينما النسخة الأولية كانت تحتوي على سجلات اختبارات مجتزأة.

---

## 10. إثبات عدم المساس بالبيانات المالية (Zero Financial Mutation Proof)

تمت إعادة مطابقة البصمة المالية للتأكد القاطع من عدم تعديل أي قيمة أثناء هذا الفحص الجنائي:
- **`agent_expenses`:** 250 مصروفا | إجمالي القيم: **189,892.00 ج.م** (مطابق 100%).
- **`agents.wallet`:** إجمالي المحافظ: **775,454.00 ج.م** (مطابق 100%).
- **`suppliers.balance`:** إجمالي أرصدة الموردين: **3,886,389.73 ج.م** (مطابق 100%).
- **`booking_container_stages`:** 355 مرحلة تشغيل حية (مطابق 100%).
- **`booking_container_agents`:** 1,384 تكليفاً ميدانياً (مطابق 100%).

---

## 11. التقييم المقارن لاستراتيجيات المعالجة المستقبلية (Strategies Evaluation)

*تنبيه ملزم: هذا التقييم تحليلي فقط للمالك لاختيار المسار، دون تطبيق أي استراتيجية حالياً.*

| الاستراتيجية | المتطلبات المسبقة | المزايا | المخاطر والمحاذير | الملاءمة |
|---|---|---|---|---|
| **Strategy A: إعادة بناء سجل `migrations` القديم** | حصر دقيق للملفات الـ 152 وتسجيلها كـ `Ran`. | تجعل بيئة Laravel متوافقة مع الأوامر الاعتيادية. | قد تخفي اختلافات دقيقة بين ملفات قديمة وواقع الجداول. | `MEDIUM` |
| **Strategy B: إنشاء Baseline Migration Checkpoint جديد** | توليد migration أساسية واحدة تلتقط واقع الـ 65 جدولاً كما هي. | نظيفة، صريحة، وتنهي تضارب الـ 152 ملفاً السابقة نهائياً. | تتطلب أرشفة ملفات الـ migrations القديمة والتوافق مع بيئات الإنتاج. | `HIGH RECOMMENDATION` |
| **Strategy C: إعادة بناء DB من الصفر واستيراد البيانات** | تشغيل الـ migrations على قاعدة جديدة ثم ضخ البيانات. | تضمن سلامة القيود والفهارس المرجعية منذ البداية. | مخاطرة عالية في استيراد البيانات الحالية بسبب غياب القيود الأجنبية وتعارض الـ Types. | `RISKY / HIGH OVERHEAD` |
| **Strategy D: إصلاح الـ Schema موضعيًا (In-Place Repair)** | 1. إضافة `PRIMARY KEY(id)` و `AUTO_INCREMENT` لـ 60 جدولاً.<br>2. إضافة المفاتيح المركبة للـ 5 جداول.<br>3. إضافة الفهارس الفريدة (`request_key` وغيرها).<br>4. مزامنة الـ migrations. | تحافظ على سلامة البيانات الحية بنسبة 100% دون أي نقل أو مخاطر فقدان بيانات. | تتطلب فحصاً مسبقاً قبل كل أمر DDL. (تم إثبات أمانها الكامل بفحص الـ Duplicates الذي أسفر عن 0 تكرار). | **`MOST PRACTICAL & RECOMMENDED`** |

---

## 12. القرار المعماري النهائي للتدقيق (Final Forensic Decision)

```text
================================================================================
FINAL DECISION:
🏁 FORENSIC AUDIT COMPLETE — REMEDIATION PLAN CAN NOW BE DESIGNED
================================================================================
1. All physical facts have been proven via direct read-only MySQL evidence.
2. Contradiction between Phase 0 and Phase 0.5 is 100% resolved: an un-indexed
   dump was imported at 22:27:13 on 2026-09-28 with live operational data.
3. ID integrity audit proves 60/60 tables are 100% SAFE for PRIMARY KEY(id).
4. All pivot tables have zero duplicate key tuples.
5. All 152 migrations are classified (65 Matched, 14 Partial, 68 Superseded, 2 DML, 3 Legacy).
6. ZERO schema, data, code, or configuration mutations occurred.
================================================================================
```

*تم التوقف التام والامتناع عن تشغيل أي أوامر تعديل أو معالجة، بانتظار قرار واعتماد المالك لاختيار استراتيجية المعالجة.*
