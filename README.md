# مدار — نظام إدارة الموارد البشرية

تطبيق عربي باتجاه RTL مبني على Laravel 12 وVue 3 وInertia.js وTailwind CSS. يتضمن دليل الموظفين، الأدوار الهرمية، الإجازات بمسارات موافقة قابلة للتكوين، الحضور، الرواتب والقسائم، والتقييم الدوري.

## التجربة الجاهزة

البيانات التجريبية تُنشأ محليًا فقط. الحسابات التالية تستخدم كلمة المرور `Demo1234!`:

| الدور | البريد |
| --- | --- |
| مدير النظام | `admin@madar.test` |
| مسؤول الموارد البشرية | `hr@madar.test` |
| مدير قسم | `manager@madar.test` |
| موظف | `employee@madar.test` |

تشمل البيانات 10 موظفين، 3 أقسام، أرصدة وطلبات إجازة، حضورًا، تقييمًا، ودورة رواتب معتمدة. لا تستخدم هذه الحسابات في الإنتاج.

## Windows + WSL2 + Docker Desktop / Laravel Sail

1. ثبّت WSL2 بتوزيعة Ubuntu، وفعّل WSL integration للتوزيعة من إعدادات Docker Desktop.
2. ضع نسخة المشروع داخل نظام ملفات WSL مثل `~/projects/madar-hrms` لتفادي بطء ربط الملفات من قرص Windows.
3. نفّذ داخل طرفية WSL من جذر المشروع:

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 composer install
./vendor/bin/sail up -d --build
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

افتح `http://localhost:8000`. خدمة MySQL في `compose.yaml` تستخدم المنفذ الخارجي 3307 افتراضيًا، بينما يتصل التطبيق بالخدمة `mysql:3306` داخل الشبكة. إذا كنت تملك `vendor` وNode محليًا، يمكنك الاستغناء عن أمر Docker الخاص بـ Composer.

للتطوير الحي: `./vendor/bin/sail npm run dev`. لإيقاف الحاويات: `./vendor/bin/sail down`. تُحفظ بيانات MySQL في volume باسم `sail-mysql`.

## تشغيل بديل دون Docker للتجربة المحلية

في بيئة لا يتاح فيها Docker/WSL، يمكن استخدام PHP 8.2+ وComposer وNode 20+ مع SQLite:

```powershell
composer install
npm ci
Copy-Item .env.example .env
```

عدّل `.env` إلى `DB_CONNECTION=sqlite` و`DB_DATABASE=database/database.sqlite`، وأنشئ ملف SQLite، ثم:

```powershell
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

هذه آلية المعاينة المستخدمة هنا؛ التكوين الأساسي للمشروع هو MySQL عبر Sail.

## النشر على Debian

المتطلبات: Debian مع PHP 8.3 وامتدادات `mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`, `bcmath`, `intl`، وComposer 2 وNode 20+ وMySQL 8 وNginx وPHP-FPM. اضبط قاعدة بيانات ومستخدم MySQL خاصين بالتطبيق.

1. انسخ المشروع إلى `/var/www/madar-hrms` أو مسار مناسب. اجعل جذر Nginx يشير إلى `public` كما في [deploy/nginx.conf.example](deploy/nginx.conf.example).
2. أنشئ `.env` من `.env.example` مع `APP_ENV=production` و`APP_DEBUG=false` و`APP_URL` الفعلي و`DB_HOST=127.0.0.1` وبيانات قاعدة الإنتاج. عيّن كلمات مرور قوية واحفظ الملف خارج نظام الإصدارات.
3. شغّل `php artisan key:generate` مرة واحدة فقط عند الإنشاء الأول، واحتفظ بقيمة `APP_KEY` في النشرات اللاحقة.
4. نفّذ `bash scripts/deploy-debian.sh` من جذر المشروع. يشغّل Composer وVite والترحيلات وزرع الأدوار وأنواع الإجازات المرجعية وتحسين Laravel. `DemoSeeder` محصور في البيئة المحلية.
5. عند الإنشاء الأول فقط، شغّل `php artisan hrms:bootstrap-admin admin@example.com "اسم المدير"` وأدخل كلمة مرور قوية عند الطلب. لا تسجل كلمة المرور في سطر الأوامر.
6. امنح مستخدم PHP-FPM صلاحية الكتابة على `storage` و`bootstrap/cache`، ثم أعد تحميل PHP-FPM وNginx. استخدم HTTPS ونسخًا احتياطيًا دورية لقاعدة البيانات وملف `.env`.

سكربت النشر لا ينشئ قاعدة البيانات ولا يستبدل `.env` ولا يعيد تشغيل الخدمات تلقائيًا. راجع ترحيلات قاعدة البيانات قبل تنفيذها على بيانات إنتاجية.

## الاختبارات والتصميم

```bash
php artisan test
npm run build
```

مخطط الجداول والقيود في [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)، ونظام التصميم في [design-system/hrms/MASTER.md](design-system/hrms/MASTER.md). القسائم تُنزّل كملف HTML قابل للطباعة أو الحفظ PDF من المتصفح.
