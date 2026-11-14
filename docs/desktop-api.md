# Desktop API

تحليل مسارات `routes/apiDesktop.php` وشكل الردود عبر `App\Traits\ResponseTrait`.

البادئة الفعلية: **`/api/desktop`** (من `RouteServiceProvider`).  
المجموعة كلها تحت middleware `web` ثم `localization`.  
المسارات المحمية تحت `auth:desktop` (JWT، مزود `users` / موديل `User`).

الهيدر المتوقع بعد الدخول:

```http
Authorization: Bearer {token}
Accept: application/json
```

---

## شكل ResponseTrait

الـ trait مستخدم من `App\Http\Controllers\Controller`، ومن `LoginRequest` لفشل التحقق.

### نجاح مع بيانات — `returnAllData($data, $msg)`

HTTP `200`

```json
{
  "status": true,
  "errNum": "0000",
  "message": "نص الرسالة",
  "data": {}
}
```

### نجاح مع مفتاح مخصص — `returnData($key, $value, $msg)`

HTTP `200`. المفتاح يُحقن في جذر الـ JSON. في Desktop المفتاح دائمًا `"data"`.

```json
{
  "status": true,
  "errNum": "0000",
  "message": "تم استرجاع الداتا",
  "data": {}
}
```

### نجاح بدون بيانات — `returnResponseSuccessMessage($msg, $errNum)`

HTTP `200`

```json
{
  "status": true,
  "errNum": "0000",
  "message": "نص الرسالة"
}
```

`returnSuccessMessage($msg, $errNum)` ترجع **مصفوفة** بنفس الشكل، بدون `response()->json()`. Laravel يحوّلها إلى JSON `200` تلقائيًا. مسار الخروج يستخدمها ويمرر `errNum = 200` (رقم، وليس `"0000"`).

### خطأ — `returnError($errNum, $msg)`

`$errNum` يُستخدم كـ **HTTP status** وكقيمة `errNum` معًا. لازم يكون رقم حالة HTTP صالح.

```json
{
  "status": false,
  "errNum": 401,
  "message": "نص الخطأ"
}
```

### خطأ تحقق — `validationError($errors)`

HTTP `422`. **بدون** `errNum`. في `LoginRequest` يُمرَّر أول خطأ فقط كنص.

```json
{
  "status": false,
  "message": "The email field is required."
}
```

### دوال غير مستخدمة في Desktop

- `returnCodeAccordingToInput` / `getErrorCode`: أكواد `E001`…`E056` لحقول قديمة (حجز طبي). لا يستدعيها أي مسار Desktop.
- `saveLogActivity`: سجل نشاط عام، غير مستدعى من Desktop.

---

## 1) تسجيل الدخول

`POST /api/desktop/login`  
عام (بدون توكن).

**Body**

| الحقل | القاعدة |
|---|---|
| `email` | مطلوب، إيميل، موجود في جدول `users` |
| `password` | مطلوب |

قيد إضافي في الكود: الإيميل لازم يكون بالضبط `admin@admin.com`، وإلا `401`.

**نجاح `200`** عبر `returnAllData`

```json
{
  "status": true,
  "errNum": "0000",
  "message": "...",
  "data": {
    "user": {
      "id": 1,
      "name": "...",
      "email": "admin@admin.com",
      "phone": "",
      "address": ""
    },
    "token": "jwt..."
  }
}
```

التوكن يُحفظ أيضًا في `users.session_id`.

**فشل**

| الحالة | الشكل |
|---|---|
| تحقق ناقص | `422` + `validationError` |
| إيميل غير المسموح أو كلمة مرور خاطئة أو استثناء | `401` + `returnError` ورسالة `__('auth.credentials_incorrect')` |

---

## 2) تسجيل الخروج

`POST /api/desktop/logout`  
يتطلب `auth:desktop`.

**نجاح** عبر `returnSuccessMessage` (مصفوفة تُحوَّل JSON):

```json
{
  "status": true,
  "errNum": 200,
  "message": "تم تسجيل الخروج بنجاح"
}
```

**فشل:** لا جلسة → `401` ورسالة `__('alerts.notAuth')`. استثناء → `401` ورسالة بيانات الدخول.

---

## 3) كل الطلبات

`GET /api/desktop/orders/all`  
يتطلب `auth:desktop`.

**Query**

| المعامل | الاستخدام |
|---|---|
| `search` | اختياري. يطابق رقم الحاوية، أو `employee_name`، أو `invoice_number` |
| `limit` | حجم الصفحة في `paginate` |
| `page` | رقم الصفحة (سلوك Laravel الافتراضي) |

الترتيب: `is_submitted` تصاعدي.

**نجاح `200`** عبر `returnData('data', ...)`

```json
{
  "status": true,
  "errNum": "0000",
  "message": "تم استرجاع الداتا",
  "data": {
    "orders": [
      {
        "id": 10,
        "company_name": "اسم الشركة أو __",
        "signature_company": "",
        "signature_company_id": "",
        "signature_date": "",
        "factory_name": "اسم المصنع أو __",
        "booking_number": "رقم فاتورة الحجز أو فارغ",
        "taxed": 0,
        "taxed_invoice": null,
        "created_at": "2026-11-05 02:18 pm",
        "submission_id": "",
        "invoice_uuid": "",
        "invoice_status": "",
        "is_submitted": 0,
        "invoice_link": ""
      }
    ],
    "pagination": {
      "total": 100,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 7
    }
  }
}
```

ملاحظات على عنصر الطلب (`OrderResource`):

- `booking_number` هنا هو **رقم فاتورة الحجز** `invoices.invoice_number`، وليس رقم الحجز التشغيلي.
- `is_submitted` في الرد = `1` فقط إذا `invoice_status == "Valid"`، وليس عمود `is_submitted` مباشرة.
- `invoice_link` يُملأ فقط إذا عمود `bookings.is_submitted` صحيح، ويشير إلى `bookings.previewInvoicePDF`.
- `signature_date` و`created_at` بصيغة `Y-m-d h:i a`.

أي استثناء غير ممسوك يُعاد رميه (ليس `returnError`).

---

## 4) تجهيز فواتير ETA

`POST /api/desktop/orders/invoices`  
يتطلب `auth:desktop`.

**Body**

| الحقل | المعنى |
|---|---|
| `company_id` | معرّف `company_fatoorahs` |
| `order_ids` | **نص JSON** لمصفوفة معرّفات حجوزات، مثل `"[10,11]"` وليس مصفوفة form عادية |

**نجاح `200`**

```json
{
  "status": true,
  "errNum": "0000",
  "message": "تم استرجاع الداتا",
  "data": {
    "company_id": 1,
    "invoiceOrders": [
      {
        "reference_id": 10,
        "invoiceData": {
          "issuer": {},
          "receiver": {},
          "documentType": "I",
          "documentTypeVersion": "1.0",
          "dateTimeIssued": "2026-11-05T12:18:00Z",
          "taxpayerActivityCode": "...",
          "internalID": "رقم الفاتورة أو رقم الحجز",
          "invoiceLines": [],
          "totalSalesAmount": 0,
          "netAmount": 0,
          "totalAmount": 0,
          "taxTotals": [{ "taxType": "T1", "amount": 0 }]
        }
      }
    ]
  }
}
```

كل سطر فاتورة يُبنى من حاوية: الوصف = اسم المصنع، `itemType=EGS`، `unitType=KGM`، الكمية `1`، السعر من `booking_containers.price`. المجموع = مجموع أسعار الحاويات. التوقيت يُحسب بتوقيت القاهرة ثم يُحوَّل إلى UTC.

**أخطاء**

| HTTP | متى |
|---|---|
| `404` | الشركة غير موجودة |
| `400` | `order_ids` ليست مصفوفة JSON غير فارغة |
| استثناء | حجز غير موجود (`findOrFail`) يُرمى كما هو |

هذا المسار **لا يرسل** للضرائب. يجهّز الحمولة فقط.

---

## 5) إرسال الفواتير إلى ETA

`POST /api/desktop/orders/submitInvoices`  
يتطلب `auth:desktop`.

**Body**

| الحقل | المعنى |
|---|---|
| `company_id` | شركة الفاتورة الإلكترونية |
| `invoiceOrders` | **نص JSON**. يقبل أحد الأشكال بعد `normalizeInvoicePayload`: مصفوفة `{invoiceData, reference_id}`، أو كائن واحد بنفس المفتاحين، أو الشكل القديم حيث العنصر الأول يحتوي `invoiceData` |

**تدفق كل فاتورة**

1. جلب Access Token من `EInvoiceService` ببيانات الشركة.
2. `submitInvoice`.
3. عند القبول: انتظار 10 ثوانٍ ثم `getInvoiceDetails`.
4. `Valid`: تحديث الحجز (`submission_id`, `invoice_uuid`, `invoice_status=Valid`, `signature_*`, `is_submitted=1`) ثم **رجوع فوري** من أول فاتورة ناجحة (الحلقة لا تكمل الباقي).
5. غير ذلك: حفظ `Failed` / `Invalid` و`invoice_errors` ثم `returnError`.

**نجاح عند Valid** — JSON مباشر، **بدون** `errNum` وبدون `data`:

```json
{
  "status": true,
  "message": "تم توقيع الفواتير بنجاح"
}
```

**نجاح إذا انتهت الحلقة بلا رجوع داخلي** (مثلاً عناصر بلا `reference_id` تم تخطيها):

```json
{
  "status": true,
  "message": "تم إرسال الفواتير، وجاري معالجتها في الخلفية."
}
```

المعالجة ليست في طابور خلفية؛ الرد نص فقط.

**أخطاء `returnError`**

| HTTP | متى |
|---|---|
| `404` | الشركة غير موجودة |
| `400` | JSON غير صالح، أو لا فواتير بعد التوحيد، أو ETA رفض الإرسال، أو الفاتورة ليست `Valid` |
| `500` | فشل Access Token، أو شكل رد ETA غير متوقع |

---

## 6) تحديث حالة فاتورة

`POST /api/desktop/orders/updateStatus`  
يتطلب `auth:desktop`.

**Body**

| الحقل | المعنى |
|---|---|
| `company_id` | شركة الفاتورة الإلكترونية |
| `invoice_uuid` | UUID الفاتورة عند ETA |
| `status` | الحالة الجديدة المطلوبة |
| `reason` | سبب التحديث |

يستدعي `EInvoiceService::updateInvoiceState`.

**نجاح** — JSON مباشر، ليس `returnAllData`:

```json
{
  "status": true,
  "message": "تم تحديث حالة الفاتورة بنجاح",
  "data": {}
}
```

`data` هو رد خدمة ETA كما رجع. الحجز ذو نفس `invoice_uuid` يُحدَّث: `invoice_status = status` و`is_submitted = 0`.

**فشل ETA** (`response.status === false`): الحجز يصبح `Invalid` و`is_submitted = 0` و`invoice_errors` = JSON الرد، ثم `returnError(400, message)`.

| HTTP | متى |
|---|---|
| `404` | الشركة غير موجودة |
| `500` | فشل Access Token |
| `400` | ETA أعاد `status: false` |

---

## 7) شركات الفاتورة الإلكترونية

`GET /api/desktop/companies/all`  
يتطلب `auth:desktop`.

**نجاح `200`** عبر `returnData`. المفتاح الداخلي مكتوب **`compaines`** (خطأ إملائي ثابت في العقد).

```json
{
  "status": true,
  "errNum": "0000",
  "message": "تم استرجاع الداتا",
  "data": {
    "compaines": [
      {
        "id": 1,
        "name": null,
        "ETA_CLIENT_ID": "...",
        "ETA_CLIENT_SECRET": "...",
        "pn": null,
        "sn": null
      }
    ]
  }
}
```

`CompanyResource` يقرأ `name` و`pn` و`sn`. موديل `CompanyFatoorah` في بقية المسارات يستخدم `company_name`. إذا العمود في الجدول `company_name` فحقل `name` في الرد يكون `null`. الرد يتضمن أسرار العميل (`ETA_CLIENT_SECRET`) كما هي في المورد.

---

## خريطة الدالة لكل مسار

| المسار | الدالة | غلاف الرد |
|---|---|---|
| `POST login` | `returnAllData` / `returnError` / `validationError` | موحّد |
| `POST logout` | `returnSuccessMessage` / `returnError` | نجاح بمصفوفة، `errNum` = `200` |
| `GET orders/all` | `returnData` | `data.orders` + `data.pagination` |
| `POST orders/invoices` | `returnData` / `returnError` | `data.invoiceOrders` |
| `POST orders/submitInvoices` | `response()->json` أو `returnError` | نجاح بلا `errNum` |
| `POST orders/updateStatus` | `response()->json` أو `returnError` | نجاح بلا `errNum`، مع `data` |
| `GET companies/all` | `returnData` | `data.compaines` |

---

## ملاحظات للعقد

- مسارات النجاح ليست كلها بنفس المفاتيح: بعضها `errNum: "0000"` وبعضها بلا `errNum`.
- `returnError` يكرر كود HTTP داخل `errNum`.
- `order_ids` و`invoiceOrders` نصوص JSON، ليست حقول مصفوفة.
- `submitInvoices` يتوقف عند أول فاتورة `Valid` ولا يرسل الباقي في نفس الطلب.
- السجلات تُكتب على قناة `desktop_eta`.
