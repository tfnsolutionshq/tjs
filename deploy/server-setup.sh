#!/usr/bin/env bash
#
# One-time DreamHost server setup for tjs.tfnsolutions.us
# Run this ON THE SERVER after SSH login:
#   bash deploy/server-setup.sh
#
set -euo pipefail

APP_DIR="${TJS_APP_DIR:-tjs.tfnsolutions.us}"
if [[ "$APP_DIR" != /* ]]; then
  APP_DIR="$HOME/$APP_DIR"
fi
REPO="${TJS_REPO:-git@github.com:tfnsolutionshq/tjs.git}"

if command -v php82 >/dev/null 2>&1; then
  PHP=php82
elif command -v php8.2 >/dev/null 2>&1; then
  PHP=php8.2
else
  PHP=php
fi

echo "==> TJS server setup"
echo "    App directory: ${APP_DIR}"
echo "    Repository:    ${REPO}"

if [[ ! -d "$APP_DIR/.git" ]]; then
  git clone "$REPO" "$APP_DIR"
fi

cd "$APP_DIR"
git fetch origin main
git checkout main
git reset --hard origin/main

if [[ ! -f .env ]]; then
  cp .env.example .env
  "$PHP" artisan key:generate
  echo ""
  echo "IMPORTANT: Edit ${APP_DIR}/.env before going live:"
  echo "  APP_URL=https://tjs.tfnsolutions.us"
  echo "  APP_DEBUG=false"
  echo "  DB_* (DreamHost MySQL credentials)"
  echo "  PAYSTACK_* and MAIL_*"
  echo ""
fi

if [[ -f "$HOME/composer.phar" ]]; then
  "$PHP" "$HOME/composer.phar" install --no-dev --optimize-autoloader --no-interaction
else
  composer install --no-dev --optimize-autoloader --no-interaction
fi

"$PHP" artisan storage:link || true
"$PHP" artisan migrate --force

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

cat <<EOF

==> Server setup finished.

Next steps:
1. DreamHost panel → Domains → tjs.tfnsolutions.us
   Set web directory to: ${APP_DIR}/public

2. Ensure PHP 8.2+ is selected for this domain in the DreamHost panel.

3. Finish editing ${APP_DIR}/.env

4. Add a GitHub Actions deploy key (see deploy/DEPLOY.md):
   - Generate SSH key on this server for git pull (if using private repo)
   - Add GitHub repository secrets for CI deploy

5. Push to main on GitHub — deployment runs automatically.

EOF
