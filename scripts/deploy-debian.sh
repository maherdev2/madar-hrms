#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
  echo 'ملف .env غير موجود؛ أنشئ إعدادات الإنتاج أولًا.' >&2
  exit 1
fi

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --include=dev
npm run build
php artisan migrate --force
php artisan db:seed --class=HrmsReferenceSeeder --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize

echo 'اكتمل تجهيز التطبيق. أعد تحميل PHP-FPM وNginx بعد مراجعة الصلاحيات.'
