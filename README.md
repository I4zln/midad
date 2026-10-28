# مِداد | MIDAD

منصة اقتباسات عربية: يتصفح الزوار الاقتباسات، وينشر المستخدمون المسجلون اقتباساتهم (3–155 حرفًا) ويعدّلونها ويحذفونها، مع الإعجاب والحفظ والبحث والتصنيف.

المشروع النهائي الفردي — معسكر القمّة، نادي طويق (مسار البرمجة والتطوير).

## المتطلبات

PHP 8.2+ (مع امتداد SQLite)، Composer، Node.js 18+ وnpm.

## التشغيل

```sh
composer install
cp .env.example .env            # في PowerShell: copy .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

افتح `http://127.0.0.1:8000`. لا يضيف الـ seeder بيانات تجريبية؛ ابدأ بإنشاء حساب من الواجهة.

## الاختبارات

```sh
php artisan test
```

تعمل الاختبارات على قاعدة SQLite في الذاكرة ولا تلمس قاعدة التطوير.

## الوثائق

- `docs/SRS.md` — مواصفات المتطلبات
- `docs/ERD.mmd` — مخطط قاعدة البيانات (Mermaid) من ملفات migrations
- `docs/DEMO-1-MIN.md` — سيناريو فيديو العرض
- `docs/AUDIT.md` — مراجعة الامتثال لدليل المشروع
