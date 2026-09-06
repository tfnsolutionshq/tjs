#!/usr/bin/env bash
#
# Run on the DreamHost server after each push to main.
# Called by GitHub Actions (.github/workflows/deploy.yml).
#
set -euo pipefail

APP_DIR="${TJS_APP_DIR:-tjs.tfnsolutions.us}"
if [[ "$APP_DIR" != /* ]]; then
  APP_DIR="$HOME/$APP_DIR"
fi
cd "$APP_DIR"

if [[ ! -d .git ]]; then
  echo "ERROR: $APP_DIR is not a git repository. Run deploy/server-setup.sh first." >&2
  exit 1
fi

# DreamHost shared hosting: prefer PHP 8.2+ when available.
if command -v php82 >/dev/null 2>&1; then
  PHP=php82
elif command -v php8.2 >/dev/null 2>&1; then
  PHP=php8.2
else
  PHP=php
fi

PHP_VERSION="$("$PHP" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
if "$PHP" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
  :
else
  echo "ERROR: PHP 8.2+ required, found ${PHP_VERSION} (${PHP})." >&2
  exit 1
fi

if [[ -f "$HOME/composer.phar" ]]; then
  COMPOSER=( "$PHP" "$HOME/composer.phar" )
elif command -v composer >/dev/null 2>&1; then
  COMPOSER=( composer )
else
  echo "ERROR: composer not found. Install composer.phar in ~ or on PATH." >&2
  exit 1
fi

echo "==> Deploying TJS in ${APP_DIR} (PHP ${PHP_VERSION})"

MAINTENANCE_SECRET="${TJS_DEPLOY_SECRET:-tjs-deploy-$(date +%Y%m%d)}"
"$PHP" artisan down --secret="$MAINTENANCE_SECRET" --retry=60 || true

git fetch origin main
git reset --hard origin/main

"${COMPOSER[@]}" install \
  --no-dev \
  --optimize-autoloader \
  --no-interaction \
  --prefer-dist

if [[ ! -L public/storage ]]; then
  "$PHP" artisan storage:link || true
fi

"$PHP" artisan migrate --force

"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

"$PHP" artisan up

echo "==> Deploy complete ($(git rev-parse --short HEAD))"
