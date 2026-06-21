# دليل حل مشكلة عدم وصول الإيميلات 🔧

## المشكلة
الـ logs تظهر "Email sent successfully" لكن الإيميل لا يصل للمندوبين.

## التحديثات التي تمت ✅

### 1. إصلاح Controller
- **إزالة إرسال OTP** الذي كان يرسل إيميل ثاني غير ضروري
- **الآن يرسل إيميل واحد فقط** مع رابط تعيين كلمة المرور

### 2. تحسين PhpMailChannel
- **إضافة logging مفصّل** لإعدادات SMTP
- **فحص failed recipients**
- **رمي Exception** عند فشل الإرسال

## خطوات الفحص والحل 🔍

### الخطوة 1: تحقق من إعدادات SMTP في `.env`

افتح ملف `.env` وتأكد من الإعدادات التالية:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com       # أو SMTP الخاص بك
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=booking@leaderfortrans.com
MAIL_FROM_NAME="Leader for Trans"
```

#### إعدادات Gmail:
إذا كنت تستخدم Gmail:
1. يجب تفعيل **2-Step Verification**
2. إنشاء **App Password** من: https://myaccount.google.com/apppasswords
3. استخدام App Password في `MAIL_PASSWORD`

### الخطوة 2: اختبر الإرسال من Terminal

افتح terminal في مجلد المشروع وجرّب:

#### أ) اختبار بسيط:
```bash
php artisan tinker
```

ثم اكتب:
```php
Mail::raw('اختبار إرسال إيميل', function($msg) {
    $msg->to('your-email@gmail.com')->subject('Test Email');
});
```

#### ب) اختبار مع Command الجديد:
```bash
php artisan email:test your-email@gmail.com
```

### الخطوة 3: فحص الـ Logs بالتفصيل

الآن الـ logs تحتوي على معلومات أكثر. افحص:
```bash
tail -f storage/logs/laravel.log
```

ابحث عن:
- ✅ "Attempting to send email" - يعرض إعدادات SMTP
- ✅ "Email sent successfully" - نجح الإرسال
- ❌ "Failed to send email" - فشل الإرسال مع التفاصيل
- ❌ "Email failed to some recipients" - فشل للبعض

### الخطوة 4: تحقق من الأسباب الشائعة

#### أ) مشاكل SMTP:
```bash
# اختبر اتصال SMTP
telnet smtp.gmail.com 587
```

#### ب) Firewall/Antivirus:
- تأكد أن Firewall لا يمنع port 587 أو 465

#### ج) مجلد Spam:
- **تحقق من مجلد Spam/Junk** في البريد الإلكتروني

#### د) إعدادات DNS (للإيميلات من domain خاص):
- تأكد من إعداد **SPF**, **DKIM**, **DMARC** records

### الخطوة 5: استخدام Mailtrap للاختبار

للتطوير، استخدم Mailtrap:

1. سجل في https://mailtrap.io (مجاني)
2. احصل على SMTP credentials
3. ضع في `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

### الخطوة 6: استخدام Queue (اختياري)

إذا كانت المشكلة في timeout:

1. في `AssignAgentPasswordNotification.php`:
```php
class AssignAgentPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

2. شغّل Queue worker:
```bash
php artisan queue:work
```

## الأخطاء الشائعة وحلولها 🐛

### خطأ: "Connection timed out"
```env
MAIL_TIMEOUT=30  # زود timeout
```

### خطأ: "Authentication failed"
- تأكد من username/password صحيحة
- Gmail: استخدم App Password

### خطأ: "SSL certificate problem"
```bash
# في Windows، حمّل cacert.pem:
# https://curl.se/docs/caextract.html
# وحدثه في php.ini:
curl.cainfo = "C:\path\to\cacert.pem"
```

### لا يوجد أخطاء لكن الإيميل لا يصل:
1. ✅ تحقق من Spam folder
2. ✅ جرب Mailtrap أولاً
3. ✅ اتصل بمزود SMTP (قد يكون blocked)

## أدوات إضافية للفحص 🛠️

### 1. فحص mail configuration:
```bash
php artisan tinker
config('mail')
```

### 2. Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### 3. تشغيل Queue في الخلفية (production):
```bash
php artisan queue:work --daemon
```

## تواصل 📞

إذا استمرت المشكلة:
1. شارك آخر 100 سطر من `storage/logs/laravel.log`
2. شارك نتيجة `php artisan email:test your-email@gmail.com`
3. شارك إعدادات SMTP (بدون password)

---

تم التحديث: 2025-12-18

