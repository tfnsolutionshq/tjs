# TJS System Documentation

**TurboFlux Journal System (TJS)** — roles, permissions, workflows, and architecture.

Production: `https://tjs.tfnsolutions.us/`  
API base: `/api/v1`  
Interactive API docs: `/api/documentation`

---

## Table of contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [User levels & platform roles](#3-user-levels--platform-roles)
4. [Journal team roles](#4-journal-team-roles)
5. [Permissions matrix](#5-permissions-matrix)
6. [Middleware & route access](#6-middleware--route-access)
7. [Journal activation & public listing](#7-journal-activation--public-listing)
8. [Memberships & article access](#8-memberships--article-access)
9. [Authentication flows](#9-authentication-flows)
10. [Author & submission lifecycle](#10-author--submission-lifecycle)
11. [Reviewer workflow](#11-reviewer-workflow)
12. [Production workflow](#12-production-workflow)
13. [Payment flows (Paystack)](#13-payment-flows-paystack)
14. [Journal management](#14-journal-management)
15. [Platform administration](#15-platform-administration)
16. [Mobile / external API](#16-mobile--external-api)
17. [Notifications](#17-notifications)
18. [Configuration reference](#18-configuration-reference)
19. [Glossary](#19-glossary)

---

## 1. Overview

TJS is a multi-journal publishing platform. One installation hosts many journals, each with its own branding, editorial team, fees, and public website.

**Core capabilities:**

- Public catalog (journals, volumes, issues, articles)
- Author manuscript submission via **calls for submissions**
- Peer review (open or blind)
- Production editing (layout PDF before publish)
- Paystack payments (fees, memberships, article purchase, journal activation)
- DOI deposit via Crossref
- Access-controlled full text (`open`, `members_only`, `paid`, `closed`)
- Web UI + JSON API (`/api/v1`) for mobile/external clients

**Authorization model:** There are no Laravel Policy classes. Access is enforced through **middleware**, **User/Journal model methods**, and **service-level checks**.

---

## 2. Architecture

### Surfaces

| Surface | Auth | Format |
|---------|------|--------|
| **Web** | Session + CSRF | Blade + Alpine.js |
| **API** | Laravel Sanctum bearer tokens | JSON |

### Route prefixes (web)

| Prefix | Purpose |
|--------|---------|
| `/` | Platform home, journal catalog |
| `/j/{journal}` | Public journal site |
| `/login`, `/register` | Platform auth |
| `/j/{journal}/login`, `/register` | Journal-branded auth |
| `/dashboard` | Member home |
| `/author/*` | Author submissions |
| `/reviewer/*` | Reviewer queue |
| `/production/*` | Production editor queue |
| `/j/{journal}/manage/*` | Journal editor/admin tools |
| `/admin/*` | Platform administration |
| `/memberships/*` | Membership checkout |
| `/payments/*` | Payment callbacks & receipts |

### Key directories

| Path | Role |
|------|------|
| `app/Models/` | User, Journal, Submission, Article, etc. |
| `app/Support/` | Status/role constants |
| `app/Services/` | Business logic |
| `app/Http/Middleware/` | Access control |
| `app/Http/Controllers/Api/V1/` | Mobile API |
| `routes/web.php`, `routes/api.php`, `routes/auth.php` | Routing |

---

## 3. User levels & platform roles

Every user has a **platform role** stored in `users.role`.

| Role | Value | Description |
|------|-------|-------------|
| **Admin** | `admin` | Full platform access (`/admin/*`). Bypasses all `role:*` middleware. Treated as journal admin on every journal. |
| **Member** | `member` | Default role on registration. Can submit manuscripts, join journals, purchase content. |
| **Reviewer** | `reviewer` | Global reviewer role. Can access reviewer queue when combined with assignments or journal team role. |
| **Editor** | `editor` | **Legacy** platform role. Still honored by `isEditor()` / `isReviewer()` but **not assignable** via admin UI. |

### Important notes

- There is **no separate `author` role**. Authorship is determined by `submissions.author_id`.
- A user can be a **member** on the platform and simultaneously hold **journal team roles** on one or more journals.
- Assigning someone as a journal **reviewer** auto-upgrades their platform role from `member` → `reviewer` if needed.

### Post-login landing (`User::homeRouteName()`)

Priority order:

1. Platform admin → `/admin`
2. Pure reviewer (review access, no managed journals, no production) → `/reviewer/reviews`
3. Pure production editor (production access, no managed journals, no review queue) → `/production/queue`
4. Everyone else → `/dashboard`

---

## 4. Journal team roles

Journal-specific roles live in the **`journal_user` pivot** (`journal_id`, `user_id`, `role`).

| Role | Value | Can manage journal? | Primary access |
|------|-------|-------------------|----------------|
| **Journal admin** | `admin` | Yes | Full journal manage UI |
| **Editor** | `editor` | Yes | Submissions, publish, announcements |
| **Reviewer** | `reviewer` | No | Reviewer queue for assigned work |
| **Production editor** | `production_editor` | No | Production queue |

**Rules:**

- One role per user per journal (re-assigning replaces the previous role).
- Platform admins effectively have journal admin on all journals regardless of pivot.
- `JournalTeamRoles::manageRoles()` = `[admin, editor]` — required for `/j/{slug}/manage/*`.

### How team members are assigned

| Method | Result |
|--------|--------|
| Member creates a journal | Creator → journal `admin` |
| Platform admin journal create | Creator → journal `admin`; activation waived |
| Platform admin team UI | Any team role |
| Reviewer request approved | User → journal `reviewer` |

---

## 5. Permissions matrix

### Platform capabilities

| Capability | Admin | Member | Reviewer | Editor (legacy) |
|------------|-------|--------|----------|-----------------|
| Platform admin UI | ✅ | ❌ | ❌ | ❌ |
| Create journal (member) | ✅ | ✅ | ✅ | ✅ |
| Submit manuscripts | ✅ | ✅ | ✅ | ✅ |
| Reviewer queue (if assigned) | ✅ | If assigned | ✅ | ✅ |
| Production queue (if assigned) | ✅ | If pivot role | If pivot role | If pivot role |

### Journal team capabilities

| Capability | j-admin | j-editor | j-reviewer | j-production |
|------------|---------|----------|------------|----------------|
| Journal manage dashboard | ✅ | ✅ | ❌ | ❌ |
| Assign reviewers / publish | ✅ | ✅ | ❌ | ❌ |
| Review assigned submissions | ✅* | ✅* | ✅ | ❌ |
| Production work | ✅* | ✅* | ❌ | ✅ |
| View closed articles | ✅ | ✅ | ✅ | ✅ |

\*Platform admin and journal staff bypass article access restrictions.

### Key authorization methods (`User` model)

| Method | True when |
|--------|-----------|
| `canManageJournal($journal)` | Platform admin **or** pivot ∈ `{admin, editor}` |
| `canAccessReviewQueue()` | `isReviewer()` **or** journal pivot reviewer **or** any past `ReviewerAssignment` |
| `canAccessProductionQueue()` | Platform admin **or** pivot `production_editor` on any journal |
| `isJournalStaffFor($journal)` | Platform admin **or** any pivot role on that journal |
| `isJournalMember($journal)` | Active membership **or** has submitted to that journal |
| `journalTeamRole($journal)` | Pivot role (admin override for platform admins) |

### Article access (`ArticleAccessResolver`)

| Visibility | Metadata (title/abstract) | Full text (PDF) |
|------------|---------------------------|-----------------|
| `open` | Public when published | Everyone |
| `members_only` | Public when published | Active platform or journal membership |
| `paid` | Public when published | Purchased (`Purchase` record) |
| `closed` | Staff only | Staff only |

Staff = platform admin or any journal team member. Authors always access their own work.

---

## 6. Middleware & route access

Middleware aliases are registered in `bootstrap/app.php`.

| Alias | Class | Requirement |
|-------|-------|-------------|
| `role` | `EnsureUserHasRole` | User role in list **or** platform admin |
| `journal.manage` | `EnsureCanManageJournal` | `canManageJournal()` + activation/mutation rules |
| `journal.manage.api` | `EnsureCanManageJournalApi` | JSON equivalent for API |
| `review.queue` | `EnsureCanAccessReviewQueue` | `canAccessReviewQueue()` |
| `production.queue` | `EnsureCanAccessProductionQueue` | `canAccessProductionQueue()` |
| `api.enabled` | `EnsureApiEnabled` | API feature flag |
| `optional.sanctum` | `OptionalSanctumAuth` | Bearer token optional (article access resolution) |

### Web route groups

| Routes | Middleware |
|--------|------------|
| `/admin/*` | `auth`, `verified`, `role:admin` |
| `/j/{journal}/manage/*` | `auth`, `verified`, `journal.manage` |
| `/reviewer/*` | `auth`, `verified`, `review.queue` |
| `/production/*` | `auth`, `verified`, `production.queue` |
| `/author/*` | `auth`, `verified` |
| Most other authenticated routes | `auth`, `verified` |

### API route groups

| Routes | Middleware |
|--------|------------|
| `/api/v1/*` | `api.enabled` |
| `/api/v1/me/*` | `auth:sanctum`, `verified` |
| `/api/v1/me/reviews/*` | + `review.queue` |
| `/api/v1/me/production/queue/*` | + `production.queue` |
| `/api/v1/me/journals/{slug}/manage/*` | + `journal.manage.api` |

### Email verification

Most write operations require a **verified email** (`verified` middleware on web; same on API `/me/*`).

Verification uses a **6-digit OTP** emailed to the user (15-minute expiry, 5 attempts, 60-second resend cooldown).

---

## 7. Journal activation & public listing

Journals must pass **two checks** to appear publicly:

1. **`is_active`** — editorial toggle (can disable a journal while keeping activation paid).
2. **Activation current** — commercial listing fee paid and not expired.

`Journal::isListed()` = `is_active` AND activation current.

### Activation statuses

| Status | Meaning |
|--------|---------|
| `unpaid` | Never paid or awaiting first payment |
| `active` | Paid; listing allowed until `activation_expires_at` |
| `expired` | Renewal required |

### What activation locks

When `activationLocked()` is true:

| Blocked | Still allowed |
|---------|---------------|
| Public journal pages | Activation page |
| Author submissions | Dashboard (read) |
| Most journal manage mutations | Billing (read) |
| API manage endpoints (403 `activation_locked`) | Settings, payment gateway, DOI settings |
| | Pay activation, skip activation |

### Creation behavior

| Creator | Initial activation |
|---------|-------------------|
| Member | `unpaid` if fees enabled, else waived |
| Platform admin | Always waived (active, no expiry) |

---

## 8. Memberships & article access

### Membership plan scopes

| Scope | `journal_id` | Grants access to |
|-------|--------------|------------------|
| `platform` | `null` | All journals |
| `journal` | required | One journal only |

### Active membership definition

```
status = 'active'
AND starts_at <= now()
AND ends_at >= now()
```

### Journal enrollment

When a user registers or logs in under a journal:

1. If the journal has a **paid membership plan** → redirect to checkout.
2. Otherwise → free journal membership granted (`enrollment_free_days`, default 365).

`isJournalMember()` also returns true if the user has **any submission** to that journal (for reviewer request eligibility).

---

## 9. Authentication flows

### Platform auth

```
Guest → /login or /register
     → email + password
     → (register) OTP email → /verify-email
     → POST /verify-email/otp
     → dashboard or role-based home
```

### Journal-branded auth

```
Guest → /j/{slug}/login or /register
     → same verification flow
     → JournalEnrollmentService grants membership or redirects to paid plan checkout
     → journal home or dashboard
```

### Journal picker

Used on login/register to choose which journal to authenticate under:

- **Web JSON:** `GET /journals/picker?action=login|register&q=&page=&mode=featured|other|all`
- **API:** `GET /api/v1/journals/picker?q=&page=&mode=featured|other|all`

Lists only **listed** (active + activation current) journals.

### API auth (Sanctum)

```
POST /api/v1/auth/login  { email, password, device_name? }
  → { token, user, capabilities }

GET  /api/v1/auth/user   (Bearer token)
POST /api/v1/auth/logout
POST /api/v1/auth/verify-email
POST /api/v1/auth/resend-verification
```

**Capabilities payload** (`UserCapabilities`):

```json
{
  "platform_admin": false,
  "can_review": true,
  "can_produce": false,
  "managed_journals": [{ "id", "slug", "title", "role" }],
  "staff_journals": [...],
  "production_journals": [...]
}
```

API clients should use capabilities rather than inferring access from platform role alone.

---

## 10. Author & submission lifecycle

### Submission statuses

| Status | Value | Meaning |
|--------|-------|---------|
| Submission fee pending | `fee_pending` | Awaiting submission fee payment |
| Submitted | `submitted` | In editorial inbox |
| Under review | `under_review` | Reviewer assigned |
| Revision requested | `revision_requested` | Author must revise |
| Resubmitted | `resubmitted` | Author uploaded revision |
| Publication fee pending | `publication_fee_pending` | Awaiting APC payment |
| Ready for production | `ready_for_production` | Accepted, awaiting production |
| In production | `in_production` | Production editor working |
| Ready to publish | `ready_to_publish` | Production complete |
| Published | `published` | Live article created |
| Rejected | `rejected` | Terminal |

### End-to-end flow

```
Author selects open Call for Submissions
        │
        ▼
Create submission (+ manuscript upload)
        │
   ┌────┴────┐
   │ fee?    │
   ▼ yes     ▼ no
fee_pending  submitted
   │ pay fee    │
   └─────┬──────┘
         ▼
      submitted
         │
   Editor assigns reviewer
         ▼
    under_review
         │
    ┌────┼────────────┐
    ▼    ▼            ▼
reject  revision    accept
         │              │
         │         APC on issue?
         │         yes → publication_fee_pending → pay → ready_for_production
         │         no  → ready_for_production
         │              │
         │         Production queue (see §12)
         │              │
         │         ready_to_publish
         │              │
         │         Editor publishToIssue()
         │              ▼
         │          published (+ optional DOI)
         │
    author resubmits → resubmitted → back to reviewer
```

### Creating a submission

Requirements:

- Authenticated, verified user
- Target journal is **listed** (active + activation current)
- An **open call for submissions** (`JournalAnnouncement` linked to an `issue_id`)

On create:

- Submission fee resolved via `JournalFeeResolver`
- Status = `fee_pending` if fee ≥ 1, else `submitted`
- Review type defaults from journal setting (`open` or `closed`)
- Manuscript stored; timeline event recorded if not fee-pending

### Resubmit

Allowed when status ∈ `{revision_requested, resubmitted}`.

- Prior document archived as `SubmissionRevision`
- Status → `resubmitted`
- Reviewer assignment reopened

### Publish gate

Editor `publishToIssue()` requires:

1. Status ∈ `{ready_to_publish, approved}` (deprecated)
2. Production document exists (`hasProductionDocument()`)
3. Target issue belongs to the submission's journal

Creates/updates `Article`, copies production PDF, sets submission → `published`.

---

## 11. Reviewer workflow

### Review types

| Type | Behavior |
|------|----------|
| `closed` | Blind review — reviewer does not see author identity |
| `open` | Reviewer sees author |

Resolution: submission override → journal default → `closed`.

### Reviewer access

Middleware `review.queue`. Qualifying users:

- Platform roles: `admin`, `editor`, `reviewer`
- Journal pivot: `reviewer`
- Anyone with a historical `ReviewerAssignment`

### Assignment

Editor assigns via journal manage or platform admin:

- Blocked while submission is `fee_pending`
- Sets submission → `under_review`
- Creates `ReviewerAssignment` (priority 1–5, optional due date)

### Decisions

| Decision | Effect |
|----------|--------|
| `accept` | Triggers acceptance (publication fee or production queue) |
| `reject` | Status → `rejected` + reason |
| `revision_requested` | Status → `revision_requested` + comment |

Blocked when submission is `fee_pending`.

### Becoming a reviewer

Members can request reviewer access:

```
POST /j/{journal}/reviewer-request
  → requires journal membership or prior submission
  → JournalReviewerRequest (pending)
  → editor approves/rejects
  → approve: assignTeamMember(reviewer)
```

---

## 12. Production workflow

### Access

Middleware `production.queue`:

- Platform admin (all journals)
- Journal pivot role `production_editor`

### Status flow

```
ready_for_production
        │ startProduction()
        ▼
   in_production
        │ uploadProductionDocument() (versioned files)
        │ completeProduction() + checklist
        ▼
  ready_to_publish
        │ editor publishToIssue()
        ▼
     published
```

### Operations

| Action | Effect |
|--------|--------|
| **Start** | `in_production`, assign actor |
| **Upload** | Versioned `SubmissionProductionFile`; auto-starts if needed |
| **Complete** | Requires production doc + checklist → `ready_to_publish`; notifies editors |

Production checklist covers title, authors, formatting, tables, figures, and related items.

---

## 13. Payment flows (Paystack)

All payments flow through **PaystackService** → **PaymentFulfillmentService**.

### Payment purposes

| Purpose | Payable | Gateway | Fulfillment |
|---------|---------|---------|-------------|
| `submission_fee` | Submission | Journal income | `fee_pending` → `submitted` |
| `publication_fee` | Submission | Journal income | → `ready_for_production` |
| `article_purchase` | Article | Journal income | Creates `Purchase` |
| `membership` | MembershipPlan | Platform or journal | Creates `Membership` |
| `journal_activation` | Journal | Platform only | `markActivationPaid()` |

### Gateway modes

| Mode | Used for |
|------|----------|
| `platform` | Journal activation, platform memberships |
| `personal` | Journal's own Paystack keys |
| `split` | Platform keys + journal split code (`SPL_`) |

Journal income resolution: personal (if allowed) → split → error.

### Payment sequence

```
User initiates payment
  → PaymentTransaction created (pending)
  → Paystack authorization_url returned (API) or redirect (web)
  → User pays on Paystack
  → Callback GET /payments/callback OR webhook POST /paystack/webhook
  → PaymentFulfillmentService.fulfillByReference()
  → Entitlement granted + PDF receipt emailed
```

### Entry points

| Payment | Web | API |
|---------|-----|-----|
| Submission fee | `POST /author/submissions/{id}/pay-fee` | `POST /api/v1/me/submissions/{id}/pay-submission-fee` |
| Publication fee | `POST /author/.../pay-publication-fee` | `POST /api/v1/me/submissions/{id}/pay-publication-fee` |
| Article | `POST /j/{journal}/articles/{article}/buy` | `POST /api/v1/me/payments/articles/{journal}/{article}/purchase` |
| Membership | `POST /memberships/{plan}/buy` | `POST /api/v1/me/payments/memberships/{plan}/purchase` |
| Journal activation | `POST /j/{journal}/manage/activation/pay` | `POST /api/v1/me/payments/journals/{journal}/activate` |
| Verify | callback/webhook | `POST /api/v1/me/payments/verify` |

---

## 14. Journal management

**Access:** platform admin or journal pivot `{admin, editor}`.

**Web prefix:** `/j/{journal}/manage/`  
**API prefix:** `/api/v1/me/journals/{slug}/manage/`

### Dashboard

Stats: articles, submissions, pending counts, membership plans, recent activity.

### Submissions inbox

| Action | Description |
|--------|-------------|
| List / show | Filter by status, search |
| Assign reviewer | Sets `under_review`, creates assignment |
| Update review type | Open / closed per submission |
| Publish | Requires `ready_to_publish` + production document |

### Announcements (calls for submissions)

Editors create **calls for submissions** tied to a specific **issue**. Authors submit only through open calls.

### Volumes & issues

- **Web:** full CRUD for volumes and issues
- **API:** read-only volume listing
- Publication fees attach to **issues** via `journal_fee_id`

### Other manage areas (web)

| Area | Purpose |
|------|---------|
| Fees | Submission and APC fee configuration |
| Payment gateway | Personal Paystack keys / split preference |
| Billing | Payment audit and export |
| Settings | Journal profile, categories, featured request |
| Editorial board | Board member management |
| Articles | Direct article upload (bypass submission workflow) |
| DOI | Settings, credit pool, per-article deposit |
| Membership plans | Journal-scoped plans |
| Reviewer requests | Approve/reject reviewer applications |
| Activation | Pay or skip listing fee |

---

## 15. Platform administration

**Access:** `users.role = admin` only (`/admin/*`).

| Area | Capabilities |
|------|--------------|
| Dashboard | Platform overview |
| Journals | Create, edit, delete, team management, activation waive |
| Users | Role assignment (admin, member, reviewer) |
| Submissions | Cross-journal editorial inbox (mirrors journal manage) |
| Articles | Cross-journal article management |
| Volumes / issues | Cross-journal structure |
| Membership plans | Platform-wide and per-journal plans |
| Featured journal requests | Approve/dismiss homepage featuring |
| Settings | Platform-wide configuration |

**Note:** There is **no platform admin API** today. Admin functions are web-only.

---

## 16. Mobile / external API

Base URL: `/api/v1`

Full interactive documentation: `/api/documentation`

### Public endpoints (no auth)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/journals` | List public journals |
| GET | `/journals/picker` | Searchable journal list |
| GET | `/journals/{slug}` | Journal detail |
| GET | `/journals/{slug}/browse` | Browse articles in journal |
| GET | `/articles` | Platform article catalog |
| GET | `/journals/{slug}/articles/{article}` | Article detail (optional auth improves access) |
| POST | `/auth/login` | Obtain bearer token |

### Authenticated endpoints (`/me/*`)

| Group | Paths |
|-------|-------|
| Auth | `/auth/user`, `/auth/logout`, verify/resend |
| Submissions | CRUD, resubmit, pay fees |
| Reviews | Queue, decide, download documents |
| Production | Queue, start/upload/complete, downloads |
| Payments | List, verify, receipts, purchases, activation |
| Memberships | List active memberships |
| Journal manage | Dashboard, submissions, announcements, volumes, reviewer requests |

### API error format

**404 responses:**

```json
{
  "message": "Journal not found.",
  "data": []
}
```

**403 activation locked:**

```json
{
  "message": "Pay or renew the journal activation fee...",
  "error": "activation_locked",
  "activation": { "status", "expires_at" }
}
```

### API gaps (web-only today)

- User registration
- Password reset
- Platform admin
- Full journal manage write operations (fees, settings, volumes write, DOI, billing export)
- Journal-branded login pages

---

## 17. Notifications

Key email notifications in the workflow:

| Notification | Trigger |
|--------------|---------|
| Email verification OTP | Registration |
| Reviewer assigned | Editor assigns reviewer |
| Review decision | Reviewer accepts/rejects/requests revision |
| Publication fee required | Acceptance with APC on issue |
| Ready for production | After acceptance / APC paid |
| Production complete | Production editor completes work |
| Payment receipt | Successful Paystack payment |
| DOI credits low | Journal DOI pool below threshold |
| Journal activation reminder | Activation nearing expiry |

---

## 18. Configuration reference

Key environment variables (see `.env.example` and `config/tjs.php`):

| Variable | Purpose |
|----------|---------|
| `TJS_JOURNAL_ACTIVATION_ENABLED` | Require listing fee |
| `TJS_JOURNAL_ACTIVATION_PRICE` | Activation fee amount |
| `TJS_JOURNAL_ACTIVATION_DAYS` | Activation period |
| `TJS_PLATFORM_MEMBERSHIP_ENABLED` | Platform-wide membership product |
| `TJS_PLATFORM_MEMBERSHIP_PRICE` | Platform membership price |
| `TJS_DOI_ENABLED` | DOI deposit feature |
| `CROSSREF_USERNAME/PASSWORD` | Crossref credentials |
| `PAYSTACK_*` | Platform Paystack keys |
| `API_ENABLED` | Toggle `/api/v1` |
| `API_DOCS_ENABLED` | Toggle `/api/documentation` |

---

## 19. Glossary

| Term | Meaning |
|------|---------|
| **Listed journal** | Active + activation paid; visible in catalog |
| **Call for submissions** | Announcement linking authors to a target issue |
| **APC** | Article processing (publication) charge |
| **Pivot role** | Per-journal team role in `journal_user` |
| **Sanctum token** | Bearer token for API authentication |
| **Production document** | Layout-ready PDF uploaded by production editor |
| **Split code** | Paystack `SPL_` code for revenue sharing |
| **Waived activation** | Admin-created journal with no listing fee requirement |

---

## Related documentation

- [README.md](../README.md) — setup and stack overview
- [deploy/DEPLOY.md](../deploy/DEPLOY.md) — deployment guide
- `/api/documentation` — interactive OpenAPI spec (when enabled)

---

*Generated from the TJS codebase. For the latest API surface, prefer the live Swagger UI over this document.*
