# TJS deployment notes

## Stack
- Laravel 12 + Blade (public + admin)
- Private galleys on `storage/app/private` (never under `public/`)
- Paystack webhook at `POST /paystack/webhook` (CSRF exempt; HMAC required)

## Server layout
Point the web server document root at `tjs/public`.

Example nginx:

```nginx
server {
    server_name journal.example.com;
    root /var/www/tjs/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ^~ /storage/app/private {
        deny all;
    }
}
```

Scholar-friendly URLs are first-class Blade routes (no UA cloaking required):
- `/j/{journal}/articles/{slug}` HTML abstract + `citation_*` meta
- `/j/{journal}/articles/{slug}.pdf` access-checked stream
- `/j/{journal}/browse` plain HTML listing
- `/sitemap.xml`

## Deploy steps
```bash
cd /var/www/tjs
git pull
composer install --no-dev --optimize-autoloader
cp .env.example .env   # first time
php artisan key:generate
# configure DB, APP_URL, PAYSTACK_*, MAIL_*
php artisan migrate --force
php artisan db:seed --force   # first time only
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

Optional queue worker (database queue):
```bash
php artisan queue:work --sleep=3 --tries=3
```

## Environment
```
APP_NAME="TJS"
APP_URL=https://journal.example.com
FILESYSTEM_DISK=local
PAYSTACK_SECRET_KEY=sk_live_...
PAYSTACK_PUBLIC_KEY=pk_live_...
TJS_PLATFORM_MEMBERSHIP_PRICE=15000
```

## Security checklist
- `APP_DEBUG=false` in production
- Never expose `storage/app/private`
- Fulfill payments only after Paystack HMAC + amount verification
- Gated PDFs use `Cache-Control: private, no-store`
- Rate-limit checkout routes (`throttle:10,1` already applied)
