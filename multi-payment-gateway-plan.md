# Multi-Payment Gateway Architecture Plan

## Current State

The project already supports multiple payment types at the UI and business-logic level, but it does not yet have a clean multi-gateway architecture.

Current payment types:

- `pt_type=card`: Stripe card flow.
- `pt_type=gpay`: Stripe Payment Request flow, such as Google Pay or Apple Pay when available.
- `pt_type=paypal`: legacy PayPal redirect flow.
- `pt_type=cash`: admin-only cash flow.

Current Stripe credential behavior:

- The system uses one active Stripe credential pair at a time.
- Live mode uses `live_public_key` and `live_secret_key`.
- Test mode uses `test_public_key` and `test_secret_key`.
- Payment and subscription records store broad processor values like `stripe` or `paypal`.
- Records do not currently store which Stripe account/profile processed the transaction.

## Main Problem

Adding a second Stripe account by simply adding another key pair and switching between them would be fragile.

Risks:

- Webhooks would not know which Stripe signing secret/account to verify against.
- Refunds, imports, subscription cancellation, and payment lookup could use the wrong Stripe account.
- Payment rows only say `processor = stripe`, not `stripe_main` or `stripe_backup`.
- The frontend Stripe public key must always match the backend Stripe secret key.
- Subscriptions must always be managed through the same Stripe account that created them.

The correct solution is a payment gateway profile layer.

## Recommended Architecture

Create a new concept called payment gateway profiles.

Example profiles:

- `stripe_main`
- `stripe_backup`
- `paypal_main`

Each profile should define:

- `id`
- `gateway_type`: `stripe`, `paypal`, `manual`, etc.
- `label`
- `mode`: `test` or `live`
- `public_key`
- `secret_key`
- `webhook_secret`
- `paypal_merchant`
- `is_active`
- `priority` or `weight`
- `supported_currencies`
- `supported_countries`
- `supports_one_time`
- `supports_recurring`
- `supports_payment_request`

Every checkout should resolve a gateway profile before rendering.

Example:

```text
country=FR + ctc=2 + service=X
=> choose stripe_backup
=> frontend uses stripe_backup public key
=> backend uses stripe_backup secret key
=> payment row stores gateway_profile_id = stripe_backup
```

## Gateway Selection Strategies

### 1. Manual Per Item

Each product chooses which payment profile to use.

This is the safest and easiest first version.

### 2. Country-Based

Example:

- France uses Stripe account B.
- United States uses Stripe account A.

This fits the existing `country` and convenient currency flow.

### 3. Weighted Rotation

Example:

- 70% Stripe Main.
- 30% Stripe Backup.

This can distribute traffic, but should come after recordkeeping and webhook handling are correct.

### 4. Fallback

Example:

- Try Stripe Main.
- If disabled/unavailable, use Stripe Backup.

This is more complex because the frontend public key must be selected before the payment attempt.

### 5. Customer Payment Method Choice

Show multiple methods to the customer:

- Card
- PayPal
- Apple Pay / Google Pay
- Future providers

Each method maps to a processor and a resolved gateway profile.

## Implementation Plan

### Phase 1: Database Foundation

Add tables:

```text
pt_payment_gateways
pt_item_payment_gateways
```

Add columns to payment and subscription records:

```text
gateway_profile_id
gateway_type
gateway_label
```

This allows each payment/subscription to remember exactly which processor account handled it.

### Phase 2: Gateway Resolver

Create a helper/service like:

```php
pt_resolve_payment_gateway($itemId, $country, $ctc, $paymentType)
```

It should return the selected gateway profile:

```php
[
    'id' => 2,
    'type' => 'stripe',
    'public_key' => 'pk_live...',
    'secret_key' => 'sk_live...',
    'webhook_secret' => 'whsec...',
]
```

### Phase 3: Frontend Integration

In `index6.php` and the other root checkout files, pass the selected gateway to the frontend:

```js
window.paymentGateway = {
    type: "stripe",
    profileId: "stripe_backup",
    publicKey: "pk_..."
};
```

Then Stripe Elements initializes with the selected profile public key.

### Phase 4: Backend Integration

Modify:

- `backoffice/ajax/get_stripe_payment_intent.php`
- `backoffice/ajax/get_recurring.php`
- `includes/classes/pt_stripe_payment.class.php`

These should read `gateway_profile_id`, load the matching secret key, and create the PaymentIntent/subscription under the correct Stripe account.

### Phase 5: Record Keeping

When saving payment/subscription rows, store:

```text
processor = stripe
gateway_profile_id = 2
gateway_type = stripe
gateway_label = Stripe Backup FR
```

This is necessary for later refunds, imports, cancellations, webhooks, and debugging.

### Phase 6: Webhooks

For multiple Stripe accounts, webhook handling must know which gateway profile applies.

Best option:

```text
/webhook.php?gateway=stripe_main
/webhook.php?gateway=stripe_backup
```

Each URL verifies with that profile's webhook secret.

### Phase 7: Admin UI

Add backoffice screens to manage gateways:

- Add/edit Stripe profile.
- Add/edit PayPal profile.
- Enable/disable profiles.
- Assign profiles to products/items.
- Configure country rules.
- Configure weights/fallback order.

## Recommended First Version

Implement the system in this order:

1. Add second Stripe profile support.
2. Add per-item gateway assignment first.
3. Add country-based gateway rules.
4. Add PayPal as a selectable adaptive LP button.
5. Add weighted rotation/fallback later.

The safest MVP is:

```text
Item A -> Stripe Main
Item B -> Stripe Backup
Adaptive LP can use either depending on item settings
```

Then expand to:

```text
country=FR -> Stripe Backup
country=US -> Stripe Main
```

## Adaptive LP Notes

The current adaptive LP renders a Stripe card-style UI.

To add PayPal or multiple methods there, add a payment method selector inside the LP card:

```text
Card
PayPal
Apple Pay / Google Pay
```

Each option should set:

```text
pt_type=card
pt_type=paypal
pt_type=gpay
```

With gateway profiles, the form should also include:

```text
gateway_profile_id=...
```

## Recommendation

Do not add a second Stripe account only inside `PT_Stripe_Payment`.

That would work short-term, but it would be fragile for webhooks, refunds, imports, subscriptions, and reporting.

Build a small gateway profile layer first. After that, second Stripe accounts, PayPal, and future processors become much easier to support safely.
