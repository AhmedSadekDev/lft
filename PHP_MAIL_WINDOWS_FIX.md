# 🔧 حل مشكلة mail() على Windows/Laragon

## ⚠️ المشكلة

`mail()` function في PHP على Windows قد لا تعمل بشكل صحيح بدون إعداد SMTP في `php.ini`.

## ✅ الحلول

### الحل 1: إعداد SMTP في php.ini (Laragon)

1. افتح `php.ini` في Laragon:
   ```
   C:\laragon\bin\php\php-8.x.x-Win32-vs16-x64\php.ini
   ```

2. ابحث عن `[mail function]` وأضف/عدّل:

```ini
[mail function]
; For Win32 only.
SMTP = smtp.gmail.com
smtp_port = 587

; For Win32 only.
sendmail_from = your-email@gmail.com

; For Unix only.  You may supply arguments as well (default: "sendmail -t -i").
;sendmail_path = "C:\laragon\bin\sendmail\sendmail.exe -t"
```

3. أعد تشغيل Laragon

### الحل 2: استخدام Sendmail (Laragon)

1. في Laragon، اذهب إلى **Menu → Tools → Sendmail**
2. أدخل إعدادات SMTP:
   - SMTP Server: `smtp.gmail.com`
   - SMTP Port: `587`
   - Username: `your-email@gmail.com`
   - Password: `your-app-password`
   - From Email: `your-email@gmail.com`

3. في `php.ini`، فعّل:
```ini
sendmail_path = "C:\laragon\bin\sendmail\sendmail.exe -t"
```

### الحل 3: استخدام Laravel Mail بدلاً من mail() (مُوصى به)

إذا استمرت المشكلة، استخدم Laravel Mail مع SMTP:

```php
// في AgentEmailService.php
use Illuminate\Support\Facades\Mail;

Mail::html($html, function ($message) use ($to, $subject) {
    $message->to($to)
        ->subject($subject)
        ->from(config('mail.from.address'), config('mail.from.name'));
});
```

## 🧪 اختبار mail() function

أنشئ ملف `test-mail.php`:

```php
<?php
$to = "your-email@gmail.com";
$subject = "Test Email";
$message = "This is a test email";
$headers = "From: test@example.com\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email. Check error logs.";
    print_r(error_get_last());
}
```

## 📋 Checklist

- [ ] ✅ `mail()` function متاحة: `function_exists('mail')`
- [ ] ✅ `sendmail_path` مُعد في `php.ini`
- [ ] ✅ SMTP settings في `php.ini` (Windows)
- [ ] ✅ Sendmail مُعد في Laragon
- [ ] ✅ Firewall يسمح بالاتصال على port 587/465
- [ ] ✅ Gmail App Password (إذا استخدمت Gmail)

## 🔍 Debugging

### فحص إعدادات PHP:
```php
echo "mail() available: " . (function_exists('mail') ? 'Yes' : 'No') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
```

### فحص آخر خطأ:
```php
$result = @mail($to, $subject, $message, $headers);
$error = error_get_last();
if ($error) {
    print_r($error);
}
```

## 💡 نصيحة

على Windows، **الأفضل استخدام Laravel Mail مع SMTP** بدلاً من `mail()` function لأن:
- ✅ أكثر موثوقية
- ✅ يدعم authentication
- ✅ أسهل في debugging
- ✅ يعمل على جميع الأنظمة

---

**آخر تحديث:** 21 ديسمبر 2025

