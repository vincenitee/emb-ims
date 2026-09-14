# CLAUDE.md — EMB-IMS (Integrated Management System)

Government document management system for EMB CAR (DENR). Handles document routing,
multi-stage approval workflows, and audit trailing for official records (manuals,
procedures, guidelines, forms).

## Tech stack — do not suggest alternatives without asking

- **Backend**: CodeIgniter 4.0.5 (pinned exactly — see "Version constraints" below)
- **PHP**: 7.4.33 (server-locked, cannot be upgraded)
- **Database**: MySQL/MariaDB, single instance hosting both `ims` (this app) and
  `carhris` (external HR/identity system)
- **Frontend**: Vue 3 (Composition API only, no Options API), Vite, Tailwind CSS v4
- **State/routing**: Pinia, Vue Router 4
- **HTTP client**: axios, `withCredentials: true` (session-based auth, not JWT)
- **Linting**: ESLint + Prettier (frontend)
- **Dev environment**: XAMPP (Windows), `php spark serve` or Apache vhost for backend,
  Vite dev server (`:5173`) for frontend

## Version constraints — hard rules, do not change without explicit confirmation

- CodeIgniter framework version must stay pinned to **exactly 4.0.5** in `composer.json`
  (no `^` or `~`). This was a deliberate, risk-accepted decision — see Security section.
- Never suggest `composer update` without checking the pinned version first — CI4's
  ecosystem has a history of resolving to versions requiring PHP 8.1+/8.2, which this
  server cannot run.
- Do not suggest upgrading PHP. The production server is fixed at 7.4.33.
- Do not suggest Vue 3.6 (RC) or experimental tooling (e.g., Oxfmt) — stable releases only.

## Security posture — read before touching auth, uploads, or validation

CI4 4.0.5 has ~16 known security advisories (several critical RCEs) that are **accepted
risk**, documented and signed off internally, because no compatible CI4 version fully
clears them on PHP 7.4. Compensating controls are mandatory in code:

- Never interpolate user input into CI4 validation rule strings/placeholders (mitigates
  the validation-placeholder RCE).
- Never use CI4's ImageMagick image handler — use the GD handler if image processing is
  needed.
- Never trust CI4's built-in `is_image` / `ext_in` / `mime_in` validation rules alone for
  file uploads — always add an application-level MIME/extension whitelist check.
- Never pass a client-provided filename directly to `UploadedFile::move()` — always
  generate a safe server-side filename.
- `CI_ENVIRONMENT` must be `production` on any deployed environment — never leave it as
  `development` (this is the only mitigation for the info-disclosure advisory).
- Don't assume CSRF is handled by defaults — this is an API consumed by a separate SPA,
  not server-rendered forms. Confirm the actual CSRF strategy before building
  state-changing endpoints.

## Auth architecture — external identity, local authorization

This is the most load-bearing design decision in the system. Follow it exactly.

- **CARIS/CARHRIS is the authoritative identity source.** A read-only SQL view
  (`carhris_users`) joins `carhris.users` with `tblofficedivisions` and `tblsections`,
  filtered to `EmploymentStatus = 'PERMANENT'`. This filter is intentional — only
  permanent employees are ever eligible for IMS access.
- **Never duplicate CARIS credentials into IMS tables.** Passwords are validated live
  against the view's hash. No local password copy, ever.
- **`system_access` is the local authorization table** — maps a `caris_employee_id` to
  IMS-specific roles (`is_division_chief`, `is_document_handler`,
  `is_regional_director`) and grant/revoke state. Rows are never deleted, only toggled
  (`is_active`), to preserve audit history.
- **`native_admins` is a fully separate identity table** for super-admins — not tied to
  CARIS at all. Don't conflate this with `system_access`.
- **No FK constraint exists between `system_access.caris_employee_id` and the CARIS
  view** — MySQL cannot FK against a view. Any code that grants access (creates a new
  `system_access` row) must run an explicit existence check against `carhris_users`
  first and reject the grant if no match is found.
- **FKs from other tables** (`documents.process_owner_caris_id`,
  `document_reviews.reviewer_caris_id`, etc.) reference `system_access.caris_employee_id`
  — not `system_access.id`. Keep this consistent; it matches the semantic naming used
  throughout the schema.
- **Revocation has two mechanisms, both must be distinguishable in audit data:**
  - Manual, by a `native_admin` (e.g., employee resigned — CARIS has no resignation
    flag, so this is the only way IMS learns of it).
  - Automatic, via a lazy inactivity-threshold check performed **only at login**
    (comparing `last_signed_in` against a configurable threshold). No cron job exists
    on the production server — do not design anything that assumes scheduled tasks are
    available.
  - `system_access` should carry `revoked_by` (nullable — null means system-revoked)
    and a `revocation_reason` distinguishing `manual` vs `inactivity_threshold`.
- **Session freshness**: the inactivity threshold is checked only at login (cheap,
  correct — `last_signed_in` only changes at login). But `is_active` and role flags
  must be re-checked on **every authenticated request** via a global `AuthFilter`,
  since manual revocation can happen at any arbitrary moment mid-session. Do not
  conflate these two checks or their timing.
- **Sessions are file-based** (CI4 default), not database-backed — this is a
  single-server deployment, so there's no multi-server session-sharing need.
- Call `session()->regenerate()` on successful login to prevent session fixation.
- Cookie config must differ by environment: development is cross-origin (Vite `:5173`
  vs backend), requiring `SameSite=None; Secure`; production serves the built frontend
  from `backend/public/` (same-origin), allowing `SameSite=Lax`.

## Entities

This project uses CI4 Entities (not plain arrays/DTOs) for `CarhrisUser` and
`SystemAccess`. Key rule: **the password hash must never be exposed through normal
serialization** (`toArray()` or equivalent) — only through an explicitly named accessor
used solely during credential verification. Don't add convenience methods that
inadvertently leak it.

## Project structure

```
emb-ims/
├── backend/     ← CodeIgniter 4.0.5, composer-managed, /public is the webroot
└── frontend/    ← Vue 3 + Vite + Tailwind v4, builds into backend/public for prod
```

- Controllers under `app/Controllers/Api/` for all REST endpoints.
- Keep authentication logic (`AuthController`) and access-management logic (granting/
  revoking `system_access`, including the existence-check validation) in separate
  controllers — don't merge them.
- Models are data-access only — no business logic or decision-making inside models.

## Working style for this project

- This person is building CI4/Vue confidence deliberately and has strong PHP/CodeIgniter
  experience already. Prefer explaining tradeoffs and asking before assuming a design
  choice on anything security- or architecture-relevant — don't silently pick an
  approach when a real tradeoff exists.
- Flag any change that would affect the version-pinning, PHP constraint, or accepted-risk
  security posture explicitly — these were deliberate, discussed decisions, not defaults.
- Government system — bias toward auditability and explicit accountability fields
  (who/when/why) over convenience shortcuts.