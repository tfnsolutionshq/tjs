# TJS deployment notes

## Stack
- Laravel 12 + Blade (public + admin)
- Private galleys on `storage/app/private` (never under `public/`)
- Paystack webhook at `POST /paystack/webhook` (CSRF exempt; HMAC required)

## Production URLs
- Demo / showcase: **https://tjs.tfnsolutions.us**
- Planned live: `tjsjournals.org`

## Server layout (DreamHost)
- SSH host: `iad1-shared-b8-26.dreamhost.com`
- SSH user: `tfnsolutions`
- App path: `~/tjs.tfnsolutions.us`
- Web root (set in DreamHost panel): `~/tjs.tfnsolutions.us/public`

Point the domain’s web directory at `tjs/public`, not the repo root.

Example nginx (VPS — DreamHost shared uses Apache + `.htaccess` in `public/`):

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

---

## CI/CD (GitHub Actions → DreamHost)

Every push to `main`:
1. Runs tests on GitHub
2. Builds Vite assets (`public/build`)
3. Rsyncs assets to the server
4. SSHs in and runs `deploy/deploy.sh` (`git pull`, `composer install`, `migrate`, cache)

Workflow file: `.github/workflows/deploy.yml`

### One-time server setup

SSH into DreamHost:

```bash
ssh tfnsolutions@iad1-shared-b8-26.dreamhost.com
cd ~
git clone git@github.com:tfnsolutionshq/tjs.git tjs.tfnsolutions.us
cd tjs.tfnsolutions.us
bash deploy/server-setup.sh
```

Then:
1. **DreamHost panel** → Domains → `tjs.tfnsolutions.us` → Web directory → `tjs.tfnsolutions.us/public`
2. **PHP version** → 8.2 or 8.3 for this domain
3. Edit `~/tjs.tfnsolutions.us/.env` (DB, Paystack, mail, `APP_URL`)

### GitHub repository secrets

In GitHub: **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Value |
|--------|--------|
| `DREAMHOST_HOST` | `iad1-shared-b8-26.dreamhost.com` |
| `DREAMHOST_USER` | `tfnsolutions` |
| `DREAMHOST_SSH_KEY` | Private SSH key used to log into DreamHost (full key including `BEGIN/END` lines) |
| `DREAMHOST_APP_DIR` | `tjs.tfnsolutions.us` (path relative to home; optional — this is the default) |

Optional: create a GitHub **Environment** named `production` with required reviewers before deploy.

### SSH key for GitHub Actions

On your **local machine** (or any secure machine — not necessarily DreamHost):

```bash
ssh-keygen -t ed25519 -C "github-actions-tjs-deploy" -f ~/.ssh/tjs_dreamhost_deploy -N ""
```

Add the **public** key to DreamHost:

```bash
ssh tfnsolutions@iad1-shared-b8-26.dreamhost.com
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo 'PASTE_PUBLIC_KEY_HERE' >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Add the **private** key contents to GitHub secret `DREAMHOST_SSH_KEY`.

### Server git access (for `git pull` on deploy)

The server must pull from GitHub. Either:

**A — HTTPS with token (simplest on shared hosting):**

```bash
cd ~/tjs.tfnsolutions.us
git remote set-url origin https://github.com/tfnsolutionshq/tjs.git
# First manual pull may prompt for GitHub username + personal access token
```

**B — Deploy key (recommended):**

```bash
ssh-keygen -t ed25519 -C "dreamhost-tjs-git" -f ~/.ssh/tjs_github_deploy -N ""
cat ~/.ssh/tjs_github_deploy.pub
```

Add that public key in GitHub: **Repository → Settings → Deploy keys → Add** (read-only).

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
  IdentityFile ~/.ssh/tjs_github_deploy
  IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
cd ~/tjs.tfnsolutions.us
git remote set-url origin git@github.com:tfnsolutionshq/tjs.git
git fetch origin main
```

### Manual deploy (fallback)

```bash
ssh tfnsolutions@iad1-shared-b8-26.dreamhost.com
bash ~/tjs.tfnsolutions.us/deploy/deploy.sh
```

During maintenance mode, bypass with:  
`https://tjs.tfnsolutions.us/SECRET_FROM_DEPLOY_OUTPUT` (see `TJS_DEPLOY_SECRET` in deploy logs, or set a fixed secret in the server environment).

---

## Manual deploy steps (no CI)

```bash
cd ~/tjs.tfnsolutions.us
git pull origin main
php82 ~/composer.phar install --no-dev --optimize-autoloader
npm ci && npm run build   # only if Node is available on server; otherwise build locally and rsync public/build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R ug+rwx storage bootstrap/cache
```

First time only:

```bash
cp .env.example .env
php artisan key:generate
php artisan db:seed --force
php artisan storage:link
```

Optional queue worker (database queue):

```bash
php artisan queue:work --sleep=3 --tries=3
```

## Environment

```
APP_NAME="TJS"
APP_URL=https://tjs.tfnsolutions.us
APP_DEBUG=false
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
- Do not commit `.env`, `vendor/`, `node_modules/`, or `public/build/`
