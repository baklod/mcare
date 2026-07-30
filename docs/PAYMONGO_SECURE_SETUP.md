# PayMongo Secure Checkout Setup

MCARE uses PayMongo Hosted Checkout V2. The Laravel server creates the
Checkout Session, PayMongo hosts the payment screen, and only a verified
`checkout_session.payment.paid` webhook can mark an online payment as paid.
The browser success URL is never treated as proof of payment.

## 1. Start in test mode

Keep these values in `.env` only:

```dotenv
PAYMONGO_SECRET_KEY=sk_test_your_key
PAYMONGO_WEBHOOK_SECRET=whsk_your_test_endpoint_secret
PAYMONGO_LIVE_MODE=false
PAYMONGO_PAYMENT_METHODS=gcash,card,qrph
PAYMONGO_WEBHOOK_TOLERANCE=300
```

Do not commit `.env`, paste keys into chat, expose a secret key to JavaScript,
or use the PayMongo API secret as the webhook secret. The two secrets have
different purposes.

`PAYMONGO_PUBLIC_KEY` is not required for the current Hosted Checkout flow
because MCARE creates Checkout Sessions entirely on the server.

## 2. Register the test webhook

In the PayMongo test dashboard, create one webhook endpoint:

```text
https://YOUR-PUBLIC-HTTPS-DOMAIN/api/paymongo/webhook
```

Subscribe it to:

```text
checkout_session.payment.paid
```

Copy that endpoint's separate signing secret into
`PAYMONGO_WEBHOOK_SECRET`. Localhost cannot receive PayMongo webhooks directly,
so local testing needs a public HTTPS tunnel or a deployed test environment.

After changing `.env`, run:

```powershell
php artisan config:clear
php artisan migrate
```

## 3. Test the complete round trip

1. Submit or open an enrollment application.
2. Choose **Pay online**.
3. Confirm that the browser opens `https://checkout.paymongo.com/...`.
4. Complete a PayMongo test transaction using details from PayMongo's test-mode documentation.
5. Return to MCARE. A browser return alone should still show pending.
6. Confirm the signed webhook changes the application and payment attempt to `paid`.
7. Confirm a duplicate webhook stays harmless and creates no duplicate event.

Useful local checks:

```powershell
php artisan route:list --path=paymongo
php artisan test tests\Feature\PayMongoCheckoutTest.php tests\Feature\PayMongoWebhookTest.php
```

Official references:

- <https://docs.paymongo.com/docs/payment-channels-hosted-checkout>
- <https://docs.paymongo.com/docs/developer-tools-webhook-setup-management>
- <https://docs.paymongo.com/docs/developer-tools-go-live-checklist>

## 4. Before live mode

Do not change to live mode until the complete test flow succeeds.

1. Create a separate live webhook endpoint in PayMongo.
2. Replace the test API key and test webhook secret with the live values.
3. Set `PAYMONGO_LIVE_MODE=true`.
4. Verify the enabled payment methods against the live account's capabilities.
5. Use a real HTTPS application URL and secure cookies.
6. Run `php artisan config:clear`, then perform a small controlled live transaction.

MCARE deliberately pauses online checkout when either the API secret or
webhook signing secret is missing. This avoids collecting a payment that the
system cannot securely reconcile.
