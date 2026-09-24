APM_RULES {

## Stack and UI

When adding application code, use Laravel (Blade) and Bootstrap 5. Do not introduce Tailwind, a SPA framework, or a second application runtime. Keep PHP/HTML/JS in the Laravel tree. Do not delete or overwrite `.apm/` or `.cursor/`.

When adding user-visible text (views, emails, flash messages, validation messages, print layouts, WhatsApp templates), add keys in both `lang/fr` and `lang/sw` and load them through Laravel localization. Do not hardcode French or Swahili sentences in PHP or Blade.

## Data and business invariants

When persisting catalog or sales data, do not add stock quantities, warehouses, or stock-out blocking. Sales must succeed without an on-hand check.

When encoding sponsorship amounts, membership PV thresholds, reward tiers, or equilibrium rules, read `plan_settings`, `reward_tiers`, and `equilibrium_rules`. Do not hardcode 15, 40, or other plan figures in services or views.

When creating members, treat photo and ID document as optional. Do not fail validation if they are absent. Generate usernames as the user-chosen base plus 4 random alphanumeric characters, retrying on collision. Keep `sponsor_id` (who referred) separate from `placement_parent_id` + `placement_side` (binary tree). Do not auto-fill left/right. Reject a second child on an already taken side; do not silently move the member.

When converting money, store accounting amounts in USD and apply a dated exchange rate for other currencies.

## Security and roles

When storing passwords, use Laravel hashing only. Never persist plaintext.

When adding staff routes, enforce policies: `admin` for institution, users, plan settings, audit, and backup; `cashier` and `accountant` scoped to their `branch_id` where the feature is operational; `cashier` must have a branch. Member authentication must use a separate guard. Members must not reach admin, plan settings, or global accounting.

When mutating users, members, sales, plan settings, payments, or deletions, write an `audit_logs` row (actor, action, old/new values).

## Sync and offline

When creating, updating, or deleting a syncable business record, assign a UUID if missing and enqueue `sync_outbox` via the shared outbox service. Do not invent a second sync mechanism.

When showing PV or commissions produced while offline, distinguish pending from confirmed. Authoritative commission and tree results come from the server-side engine after sync, not from calculations invented only in the browser.

When two placements compete for the same parent side, keep the first accepted record and reject the other with a clear replace instruction. Do not auto-shift the member to the opposite leg.

When remote sync URL is empty, keep local MySQL as the office source of truth and do not fail the request.

## Messaging and documents

When notifying via WhatsApp in this delivery, enqueue templates and open `wa.me`. Do not call the Meta Business API. Keep an `api` driver stub unused.

When adding print views, use separate 58 mm, 80 mm, and A4 layouts. Do not stretch one layout to all widths. Do not add silent print bridges (for example QZ Tray).

## Tests

When adding or changing a business rule (placement, sale pricing, PV, commissions, roles, sync conflict), add a Laravel Feature test that would fail if the rule were reversed. Do not rely on "works in the browser" as the only check.

## Version control

When committing, use `type: description` with types `feat`, `fix`, `refactor`, `docs`, `test`, `chore`. Create branches named `type/short-description`. Do not put task numbers, stage numbers, or agent names in branch names or commit messages. Commit on the assigned feature branch only. Do not merge into the base branch and do not push to a remote unless the User explicitly asks.

} //APM_RULES
