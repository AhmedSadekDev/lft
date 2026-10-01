# صور المناديب المستقلة

شاشة التطبيق المقترحة: **صوري**، خارج شاشات الحجوزات والمصروفات. تعرض الصور السابقة وزر **رفع صور** لاختيار صورة أو أكثر من المعرض أو الكاميرا، مع حالة الرفع وإعادة تحميل القائمة بعد النجاح.

## API

كل الطلبات تحتاج `Authorization: Bearer <agent-token>` و`Accept: application/json`.

- `POST /api/agent/photos`: طلب `multipart/form-data` بحقل `images[]` لكل صورة. من 1 إلى 10 صور، بحد أقصى 10 MB للصورة. الصيغ: JPEG, PNG, WebP, GIF. حدود PHP/web server الإجمالية قد تكون أقل؛ يمكن رفع صورة في كل طلب.
- لا ترسل `booking_id` أو `booking_container_id` أو `type_id`. المندوب يُحدد من تسجيل الدخول، ولا يتغير رصيده.
- النجاح HTTP 200: `status: true` و`data` قائمة الصور، لكل صورة `id`, `image`, `original_name`, `created_at`.
- `GET /api/agent/photos?page=1&per_page=24`: صور المندوب الحالي فقط، الأحدث أولًا. الصور داخل `data.data` وبيانات الصفحات داخل `data`. أقصى `per_page` هو 100.
- رابط `image` يحتاج نفس ترويسة Authorization عند عرضه أو تحميله في التطبيق؛ الملفات غير عامة.
- أخطاء التحقق HTTP 422، تجاوز حجم الطلب HTTP 413، فشل الحفظ HTTP 500. فشل حفظ دفعة يلغي صور الدفعة كلها. لا تُعد إرسال طلب ناجح تلقائيًا؛ لا يوجد مفتاح لمنع التكرار في هذا المسار.

## Dashboard / deployment

صفحة **صور المناديب** تحت قائمة المناديب: `/dashboard/agent-photos`، بصلاحية `agents.index`، مع فلاتر المندوب والفترة وتكبير الصور.

شغّل migration الجديدة فقط عند نشر التغيير:

```sh
php artisan migrate --path=database/migrations/2026_10_01_000001_create_agent_photos_table.php
```

الملفات في `storage/app/private/agent_photos` على disk باسم `agent_photos`، ويجب أن يكون قابلًا للكتابة ومشمولًا بالنسخ الاحتياطي. أعد بناء config/route cache إن كان مستخدمًا في النشر. لا تحتاج الصور إلى `storage:link`.

هذا المستودع يحتوي الـAPI والداشبورد؛ شاشة تطبيق الموبايل تحتاج تنفيذًا في مستودع التطبيق.
