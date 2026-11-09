# StudioMatch

Nederlandse marktplaats waar artiesten opname- en mix/master-studio's vinden, boeken en betalen, en studio-eigenaren (verhuurders) uitbetaald krijgen via Stripe Connect. "Every sound deserves a studio." Klant: StudioMatch VOF; gebouwd door Eazyonline.

**Leidende stukken:**
- [studiomatch-scope.md](studiomatch-scope.md): functionele scope (§2), niet in scope (§3), beslispunten 1 t/m 17 (§4), aannames, aanleveringen en risico's. Bij twijfel "hoort dit erbij?" beslissen de scope-principes.
- De feedback-PDF's in de root zijn de feedbackrondes van de klant, in volgorde: MVP P1.0, P2.1, P3.0, Pilot V1, Pilot V2 (nieuwste). Elke ronde heeft een eigen testbestand (`FeedbackFixesTest`, `FeedbackP3Test`, `FeedbackPilotTest`); nieuwe feedback krijgt er ook een.
- [README.md](README.md): status, testaccounts, wat nog op de klant wacht.

## Scope-principes (tiebreaker)

1. Eén vloeiende boekingsflow gaat vóór veel functies.
2. Edge cases via de admin (handmatig vangnet), niet via extra code.
3. Stripe Connect is de financiële ruggengraat: geen eigen KYC, IBAN-check of uitbetalingsengine.
4. Mobiel is leidend, niet "ook".
5. Alles wat niet nodig is voor `zoeken → boeken → betalen → uitbetalen` is fase 2. Enige uitzondering: "Meld een probleem" met payout-hold.

## Domein

- **Rollen (`UserRole`):** `artiest`, `verhuurder`, `admin`. Routes per rol met middleware `role:...` ([EnsureUserHasRole](app/Http/Middleware/EnsureUserHasRole.php)); een admin mag overal bij, een verkeerde rol wordt naar het eigen dashboard gestuurd. Controllers per rol in `Http/Controllers/{Artist,Host,Admin}`.
- **Studio → ruimtes:** een verhuurder (`HostProfile`) heeft studio's (`Studio`, slug-URL, geocoding via PDOK), elke studio ruimtes (`Room`, type opname of mix-master, min. 5 foto's). Ruimtestatus (`RoomStatus`): `concept` → `in_review` → `live` (of `afgekeurd`), plus `vakantie`. Live zetten kan pas als de verhuurder Stripe-uitbetalingen heeft geactiveerd.
- **Beschikbaarheid:** weekschema (`RoomHour`), uitzonderingen en blokkades (`RoomException`, `ExceptionType`), vakantiemodus, iCal-export. Geen agenda-sync (BESLISSING 6, grootste risico).
- **Boeking (`BookingStatus`):** `pending_payment` (slot 15 min gereserveerd) → `pending_confirmation` (verhuurder heeft 24 uur, herinnering na 12 uur, anders auto-annulering met refund) → `confirmed` → `completed`; zijpaden `declined`, `cancelled`, `expired`, `disputed` ("Meld een probleem" met bewijsfoto's, houdt de uitbetaling vast). Ook nacht- en meerdaagse boekingen. Verzetten: 1 keer, tot 48 uur vooraf.
- **Geld, altijd in centen** (`*_cents`): huur (`rent_cents`), optioneel engineer, servicekosten (`service_fee_cents`, percentage uit `config('studio.service_fee_percent')`, nu 9%, betaald door de artiest), btw, totaal. De verhuurder krijgt 100% van zijn uurtarief. Annuleringsstaffel 100/50/0% over de huur; servicekosten en btw gaan bij een refund helemaal terug (`Booking::refundAmountCents`). Uitbetaling van de huur via een Stripe-transfer op starttijd + 24 uur.
- **Facturen:** per betaalde boeking een huurfactuur namens de verhuurder (21% btw, of een huurbevestiging zonder btw bij niet-btw-plichtigen) en een servicekostenfactuur van StudioMatch, plus creditnota's; PDF via dompdf ([Support/Invoices](app/Support/Invoices.php)). Moneybird kan later.

## Code

- **Laravel 13, PHP 8.4**, MySQL (`studiomatch`, via Laragon op `http://studiomatch.test`; `.env.example` staat nog op sqlite). Geen Livewire/Inertia.
- **Frontend:** Blade-components ([resources/views/components](resources/views/components): `layout`, `dashboard-layout`, `artist-layout`, `host-layout`, `admin-layout`, `studio-card`, `studio-map`, ...) + Tailwind CSS 4 + Vite 8. JavaScript is gewone JS in één bestand, [resources/js/app.js](resources/js/app.js) (dropdowns, sliders, modals, kaart via Leaflet, sorteren via SortableJS); er is geen Alpine. Iconen: Font Awesome lokaal in `public/fontawesome/` (daarom staan er zoveel bestanden in `public/`).
- **Huisstijl:** Ruby Red `#AD0924` (accenten, CTA's), Prussian Blue `#101529` (primair, donkere vlakken), Charcoal Blue `#383E46` (secundair), wit. Tokens in [resources/css/app.css](resources/css/app.css) (`bg-ruby-red`, `text-prussian-blue`, ...). Font Inter via Bunny Fonts. Logo's in `public/logos/`.
- **Twee talen:** NL (standaard) en EN via `lang/nl` en `lang/en`, taal via [SetLocale](app/Http/Middleware/SetLocale.php). Elke nieuwe UI-tekst komt in beide talen in hetzelfde langbestand; nooit tekst hard in een view. Teksten die verhuurders zelf invullen worden niet vertaald.
- **Ondersteuning in `app/Support`:** `StripeService` (Checkout, Connect Express, refunds, transfers; zonder keys simulatiemodus), `Invoices`, `Geocoder` (PDOK), `ImageProcessor`, `Hours`, `RichText`, `MailDelivery`, `AccountAnonymiser` (AVG-verwijdering).
- **Mails:** alles via notifications in `app/Notifications` (de mailmatrix uit scope §2.10). Zonder Mailgun loggen ze naar `storage/logs/laravel.log`.
- **Automatisering:** `bookings:maintain` elke 5 minuten (verlopen blokkades, herinneringen, auto-annulering, sessies afronden, uitbetalen). De queue draait ook via de scheduler (`queue:work --stop-when-empty`), dus er is geen aparte worker nodig, wel een scheduler (`schedule:work` lokaal, cron live). Verder: `studios:geocode`, `stripe:check`, `mail:check`, `legal:import` (juridische teksten, met [scripts/legal_pdf.py](scripts/legal_pdf.py), `PYTHON_BIN`).
- **Stripe:** webhook op `POST /stripe/webhook` ([StripeWebhookController](app/Http/Controllers/StripeWebhookController.php)), testmode. Formulieren met reCAPTCHA (`RECAPTCHA_*`) en throttles op contact en auth.

## Werkwijze

- **Opstarten:** `composer run setup` (install, .env, key, migrate, npm build), dan `php artisan storage:link`, `php artisan db:seed` (demodata, idempotent) en `composer run dev` (server, queue, logs, Vite). Scheduler in een tweede terminal: `php artisan schedule:work`.
- **Testaccounts** (na seeden, wachtwoord `wachtwoord123`): `artiest@`, `verhuurder@`, `demo-verhuurder@` (met data) en `admin@studiomatch.test`.
- **Tests:** feature-tests in `tests/Feature` (boekflow, beschikbaarheid, facturen, Stripe, feedbackrondes, ...). Na elke wijziging de relevante tests draaien (`php artisan test --filter=...`), en bij nieuw gedrag een test toevoegen of bijwerken. Formatteren met `vendor/bin/pint --dirty`.
- **Styling:** Tailwind 4 maakt alleen classes die het in templates ziet; laat `npm run dev` draaien, of `npm run build`. Geen styling en geen dev-server? Verwijder een achtergebleven `public/hot`.
- **Mobiel eerst:** elke pagina eerst op telefoonbreedte controleren (geen zijwaarts scrollen, tikvlakken groot genoeg).
- **Niet in Git:** `.env`, `vendor`, `node_modules`, `public/build`. De feedback-PDF's zijn stukken van de klant: lezen, niet aanpassen.
- **Wacht op de klant** (zie README): Mailgun-gegevens, Stripe-webhooksecret, btw-bevestiging van de accountant, definitieve juridische teksten, BESLISSING 13 (blog). Bouw daar niet omheen zonder overleg.
