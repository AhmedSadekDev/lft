# تغييرات API للتطبيق — 2026-10-22

ملخص لمبرمج الموبايل (Agent / Superagent / تتبع العميل).  
Base agent: `/api/agent/` — Superagent: `/api/superagent/` — Public: `/api/booking/`

---

## 1) مصروفات عامة — لا تشترط بوكينج

**`POST /api/agent/make_general_expenses`**

- مصروف عام حقيقي: `value` + `service_id` + `request_key` (+ `notes` / `image` اختياري).
- **لا ترسل** `type_id` ولا `booking_container_id` للمصروف العام.
- لو التطبيق بيبعت `type_id` بالغلط من غير حاوية ومن غير `booking_id` → السيرفر **يتجاهل** `type_id` ويسجّل مصروف عام.
- لو بعتّ `booking_id` + `type_id` من غير `booking_container_id` → رفض (سياق مرحلة ناقص).

---

## 2) مراجعة المصاريف + خصم العهدة المؤجّل

### السلوك الجديد
| الحدث | المحفظة `wallet` |
|--------|------------------|
| إنشاء / تعديل / حذف إيصال بانتظار المراجعة | **لا خصم** (حجز من الرصيد المتاح فقط) |
| اعتماد من **لوحة التحكم** فقط | خصم نهائي |

### `GET /api/agent/fetch_financial_custody`
حقول قديمة كما هي + حقول جديدة:

```json
{
  "total_financial_custody": 0,
  "spented_financial_custody": 0,
  "remaining_financial_custody": 0,
  "under_review_amount": 0,
  "confirmed_spent_today": 0
}
```

| المفتاح | المعنى |
|---------|--------|
| `remaining_financial_custody` | الرصيد **القابل للصرف** = `wallet − under_review_amount` |
| `under_review_amount` | مجموع إيصالات بانتظار اعتماد الداشبورد (لم تُخصم بعد) |
| `confirmed_spent_today` | مصروف مؤكد اليوم + تحويلات اليوم |

### `POST /api/agent/review_expenses`
- **403** دائمًا: `مراجعة المصروفات متاحة من لوحة التحكم فقط`
- التطبيق **يجب ألا** يعتمد على نجاح هذا الـ endpoint بعد الآن.
- بعد الاعتماد من الداشبورد: الإيصال يختفي من شاشة المندوب (`admin_approval = 1`).

### `ExpenseResource` (قوائم المصروفات)
حقول التاريخ/الوقت (إن لم تُستخدم بعد):

| مفتاح | مثال |
|-------|------|
| `date` | `2026-10-22` |
| `time` | `14:35` |
| `registered_at` | `2026-10-22 14:35` |

---

## 3) الرقم الداخلي `public_code` (5 أرقام عشوائية)

- يُنشأ مع كل حجز جديد؛ مش مسلسل.
- **ده** اللي في لينك التتبع وواتساب/إيميل:  
  `https://leaderfortrans.com/book/?track={public_code}`
- رقم الفاتورة الجديد: `YYYY-{public_code}-###`  
  مثال: `2026-48291-015`  
  (### = مسلسل عام حسب ترتيب إنشاء الفواتير في السنة)

### تتبع العميل (Public)
**`GET /api/booking/track?order_number={public_code}`**  
**`GET /api/booking/booking_papers?booking_number={public_code}`**

- يقبل **5 أرقام فقط** (`public_code`).
- **`booking_number` التشغيلي لم يعد يفتح التتبع العام.**

### واتساب (Superagent)
نص المرحلة يعرض `الرقم الداخلي` + لينك `track={public_code}` بدل `booking_number`.

---

## 4) بوليصة التوصيل — عهدة دولار (براد فقط)

**`POST /api/agent/create_delivery_policy`** (و update إن وُجد)

| حقل | قاعدة |
|-----|--------|
| `custody_usd` | **مطلوب** لو الحجز صادر برادات (`REEFER`) |
| `custody_usd` | **مرفوض** لو مش براد |

في الـ response (قائمة/تفاصيل بوليصة):

```json
"custody_usd": 120.5,
"custody_usd_is_record_only": true
```

`custody_usd_is_record_only: true` = تسجيل فقط، **مش** خصم من محفظة دولار.

---

## 5) تتبّع رحلة البراد — 11 مرحلة (Agent)

مسارات جديدة تحت `/api/agent/` (JWT مندوب):

| Method | Path | الوظيفة |
|--------|------|---------|
| GET | `fetch_reefer_trip_list` | قائمة رحلات البراد للمندوب + pagination |
| POST | `reefer_trip_details` | body: `delivery_policy_id` |
| POST | `save_reefer_trip_stage` | حفظ مرحلة |
| POST | `upload_reefer_trip_document` | رفع صورة/PDF |

### `save_reefer_trip_stage`
```json
{
  "delivery_policy_id": 1,
  "stage": 1,
  "occurred_at": "2026-10-22 08:00:00",
  "notes": "اختياري"
}
```

| stage | الاسم |
|-------|--------|
| 1 | دخول الميناء المصري |
| 2 | خروج من الميناء المصري |
| 3 | وصول سفاجا |
| 4 | حجز عبارة |
| 5 | وصول ضباء |
| 6 | خروج من ضباء |
| 7 | وصول جمرك دولة الوصول |
| 8 | خروج من السلع |
| 9 | وصول المخزن |
| 10 | تم التعتيق |
| 11 | ملاحظات (notes فقط؛ `occurred_at` مش مطلوب) |

المراحل 1–10: تاريخ/وقت. المرحلة 11: ملاحظات فقط.

### `upload_reefer_trip_document`
- `delivery_policy_id` (required)
- `document` ملف: png/jpg/jpeg/pdf، max 10MB
- `stage` اختياري (1–11) — لو موجود يربط بالمرحلة

---

## 6) سائق — بطاقة / جواز (عرض في API)

**`GET /api/agent/fetch_drivers`** — `DriverResource` أضاف:

```json
"national_id": "",
"passport_number": ""
```

(اختياري من الداشبورد؛ التطبيق يعرض إن وُجد.)

---

## 7) Superagent — فلتر ميناء التعتيق + بوالص المدير

| Method | Path | ملاحظة |
|--------|------|--------|
| GET | `/api/superagent/booking/unloading_aging_assignments` | تجميع التعتيق حسب `aging_id` |
| GET | `/api/superagent/booking/unloading_assignments?group_by=aging` | نفس الفكرة |
| GET | `/api/superagent/booking/unloading?aging_id=` | فلتر اختياري |
| GET | `/api/superagent/fetch_delivery_policies` | بوالص أنشأها المندوبون (+ فلاتر `aging_id`, `is_settled`, `search`) |

---

## 8) مواصفات على مستوى الحجز (Agent)

عند إنهاء المواصفات من التطبيق، السيرفر يكمل **كل حاويات الحجز** المكلّف عليها المندوب (booking-wide)، مش حاوية واحدة فقط — نفس endpoint الحالي `POST .../booking/done_specification` (تغيير سلوك داخلي).

---

## Checklist سريع للتطبيق

1. [ ] مصروف عام: متبعتش `type_id` / `booking_container_id`
2. [ ] شيل أو عطّل زر «مراجعة المصروفات» اللي بينادي `review_expenses` (بيرجع 403)
3. [ ] عهدة: اعرض `under_review_amount` + استخدم `remaining_financial_custody` كرصيد متاح
4. [ ] تتبع العميل: `public_code` (5 أرقام) فقط في `track` / `booking_papers`
5. [ ] واتساب: اللينك بالـ `public_code` (السيرفر بيجهّز النص في superagent)
6. [ ] بوليصة براد: ابعت `custody_usd`؛ اعرض `custody_usd_is_record_only`
7. [ ] شاشة تتبّع رحلة البراد: الـ 4 endpoints + 11 مرحلة
8. [ ] مصروفات: استخدم `date` / `time` / `registered_at` لو هتعرض وقت التسجيل
