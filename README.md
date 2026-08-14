# TFN Journal System (TJS)

Multi-journal publishing platform for **Turbo Flux Network Solutions**.

TJS runs branded public journal sites, author submissions, peer review, editorial management, memberships, and Paystack checkout for paid full text — with platform-level admin separate from per-journal team roles.

## Features

- **Multi-journal catalog** — volumes, issues, articles, themed public sites
- **Access models** — `open`, `members_only`, `paid`, `closed`
- **Roles**
  - Platform admin (`/admin`) — journals, users, platform settings
  - Journal team (pivot) — admin / editor / reviewer per journal
  - Members — submissions, memberships, purchases
  - Same user can be an ordinary member in one journal and a reviewer in another
- **Editorial workflow** — submissions, assignments, revisions, publish to issue
- **Public journal UX** — current issue, archives, browse, article pages, APA 7 cite/copy
- **Payments** — Paystack article purchase + memberships (callback + signed webhook)
- **SEO** — Google Scholar / citation meta, JSON-LD, PDF handling

## Stack

- Laravel 12, PHP 8.2+
- Blade + Alpine.js + Vite
- SQLite (local default) or MySQL
- Paystack

## Local setup

```bash
cd tjs
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

App: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Seeded users

Password for all: `password`

| Email | Role |
|-------|------|
| `admin@tfnsolutions.us` | Platform admin → `/admin` |
| `editor@tfnsolutions.us` | Journal admin for *TFN Open Research* → `/j/tfn-open-research/manage` |
| `reviewer@tfnsolutions.us` | Reviewer |
| `author@tfnsolutions.us` | Member / author → member portal |

Platform admins create journals and assign journal teams. Journal admins manage only their assigned journal(s). Super admins can enter a journal manage portal and return to `/admin` via **Exit to platform admin**.

### Useful URLs

- Journals catalog: `/journals`
- Sample journal: `/j/tfn-open-research`
- Browse: `/j/tfn-open-research/browse`
- Sample article: `/j/tfn-open-research/articles/welcome-to-tjs`

## Environment

Copy from `.env.example`. Important keys:

```env
APP_URL=http://127.0.0.1:8000

# Paystack (test keys locally)
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_CURRENCY=NGN
```

- Callback: `{APP_URL}/payments/callback`
- Webhook: `{APP_URL}/paystack/webhook` (use a tunnel for local webhook delivery)

Optional branding / membership defaults: `TJS_NAME`, `TJS_FULL_NAME`, `TJS_ORG`, `TJS_PLATFORM_MEMBERSHIP_PRICE`, `TJS_PLATFORM_MEMBERSHIP_DAYS`.

After changing `.env`:

```bash
php artisan config:clear
```

## Tests

```bash
php artisan test --filter=JournalAccessAndSeoTest
```

## Deploy

See [deploy/DEPLOY.md](deploy/DEPLOY.md).

## License

Proprietary — Turbo Flux Network Solutions. All rights reserved unless otherwise noted.
