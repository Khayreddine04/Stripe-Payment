Stripe Payment Terminal - Domain Rotation and Enforcement Plan

Goal
Implement secure multi-domain checkout routing so each item can be served from one or more approved domains, while blocking direct access from non-approved hosts.

Infrastructure Prerequisites
1. Dokploy Traefik: every rotated domain must be attached to the Stripe Payment container in Dokploy Domains.
2. DNS: all rotated domains must resolve to the Dokploy server IP.
3. TLS: HTTPS certificates must be issued and valid for every rotated domain.
4. Shared DB: migrations are executed from Laravel project cosmowebapp using php artisan migrate.

Proposed Architecture
1. Admin assigns allowed domains per item.
2. Customer enters through go.php with item_id or idInvoice.
3. System chooses a valid domain from the item's active domain pool.
4. System redirects to chosen domain with a signed short-lived token.
5. payment_form.php or index.php validates both host and token.
6. If host is not allowed for the item or token is invalid, request is blocked.

Database Changes
New migration file
2026_06_25_000000_create_stripe_domain_rotation_tables.php

Creates and updates
1. pt_domains
- id bigint unsigned primary key
- domain varchar(255) unique
- is_active tinyint(1) default 1
- timestamps

2. pt_item_domains
- id bigint unsigned primary key
- item_id varchar(50) not null
- domain_id bigint unsigned not null
- composite unique: item_id + domain_id
- foreign key domain_id -> pt_domains.id on delete cascade
- index on item_id

3. pt_invoices
- add checkout_domain varchar(255) nullable
- add index on checkout_domain

Backend Changes
1. Modify bootstrap.php
- Add helper to normalize host values (lowercase, strip port).
- Add helper to fetch active domains for an item.
- Add helper to validate if host is allowed for item.
- Add helper to pick target domain.
- Add helper to sign and verify redirect token.

2. Add go.php
- Accept item_id or idInvoice.
- Resolve item and active domain pool.
- Determine final domain using rotation strategy.
- Build signed token with short TTL.
- Redirect to https://selected-domain/payment_form.php with required params.

3. Modify payment_form.php and index.php
- Normalize current host before comparison.
- Validate token (signature, expiry, payload).
- Validate host is assigned to item and active.
- If validation fails: terminate immediately (blank response policy).
- On success: save host into pt_invoices.checkout_domain if missing.

Admin Panel Changes
1. Add domains.php
- Add domain form.
- Domain list with active or inactive toggle.
- Delete protection policy for mapped domains.

2. Modify edit.php
- Load active domains.
- Show checkbox list for domain assignment.
- Add Select All checkbox.
- Persist mappings in pt_item_domains with safe upsert logic.

Verification Plan
Automated or local
1. Migration test
- Run php artisan migrate in cosmowebapp.
- Verify pt_domains, pt_item_domains, pt_invoices.checkout_domain.

2. Admin mapping test
- Assign and unassign domains from item edit page.
- Verify mapping rows and uniqueness behavior.

3. Redirect and rotation test
- go.php with item_id uses only active mapped domains.
- idInvoice flow resolves correct item and domain.
- Check chosen domain and redirect params.

4. Access control test
- Allowed host + valid token should pass.
- Unmapped host should be blocked.
- Inactive domain should be blocked.
- Expired or tampered token should be blocked.

5. Persistence test
- Confirm checkout_domain is stored in pt_invoices.
- Confirm repeat invoice opens use expected domain behavior.

Manual staging verification
1. Deploy to Dokploy staging.
2. Map test domains such as domain-a.test and domain-b.test.
3. Validate TLS, redirect behavior, and block behavior end to end.

Open Decisions You Need To Choose
1. Rotation strategy
- A: Random on first hit, then sticky per invoice.
- B: Round-robin global counter.
- C: Weighted routing.

2. Sticky scope
- A: Sticky per invoice only.
- B: Sticky per item and per session.
- C: Always random each request.

3. No active domains fallback
- A: Hard block with blank page.
- B: Redirect to one fixed fallback domain.
- C: Show maintenance message.

4. Domain deletion policy
- A: Never hard delete, only deactivate.
- B: Hard delete only if no mappings and no invoice usage.
- C: Always hard delete with cascade cleanup.

5. Block response style
- A: Strict blank response only.
- B: HTTP 403 with blank body.
- C: HTTP 403 with minimal plain text.

6. Token TTL
- A: 60 seconds.
- B: 180 seconds.
- C: 300 seconds.

7. Host source behind proxy
- A: Use trusted forwarded host headers from Traefik.
- B: Use HTTP_HOST only.
- C: Hybrid trusted-proxy approach with fallback.

Selected Decisions (Confirmed)
1. Rotation strategy
- Random on first redirect, then sticky per invoice.

2. No active domains fallback
- Redirect to a primary/default domain configured in settings.

3. Domain deletion policy
- Prefer soft-delete behavior: deactivate domains instead of hard delete.

4. Sticky scope
- Sticky per invoice only.

5. Block response style
- HTTP 403 with blank body.

6. Token TTL
- 300 seconds.

7. Host source behind proxy
- Hybrid trusted-proxy approach with fallback.

Implementation note for fallback
1. Add a global setting key for primary checkout domain.
2. Validate that the default domain exists and is active.
3. If no mapped active domains for item, redirect to default domain.
4. If default domain is missing or inactive, block request.

Recommended Defaults
1. Rotation strategy: A
2. Sticky scope: A
3. No active domains fallback: B (with primary/default domain in settings)
4. Domain deletion policy: A
5. Block response style: B
6. Token TTL: C
7. Host source behind proxy: C

