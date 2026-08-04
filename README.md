<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/logos/sm-primary-logo-wit.png">
    <img src="public/logos/sm-primary-logo-blauw.png" alt="StudioMatch" width="260">
  </picture>
</p>

<h3 align="center"><em>Every sound deserves a studio</em></h3>

<p align="center">
  Het platform waar artiesten en muziekstudio's elkaar vinden.<br>
  Zoeken &rarr; boeken &rarr; betalen &rarr; uitbetalen, in &eacute;&eacute;n vloeiende flow.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/status-in_ontwikkeling-AD0924?style=flat-square" alt="Status">
  <img src="https://img.shields.io/badge/Laravel-13-AD0924?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.4-101529?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-101529?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Vite-8-101529?style=flat-square&logo=vite&logoColor=white" alt="Vite 8">
  <img src="https://img.shields.io/badge/talen-NL_/_EN-383E46?style=flat-square" alt="NL/EN">
</p>

---

## Over StudioMatch

StudioMatch is een Nederlandse marktplaats voor opname- en mix/master-studio's. Veel studio's zijn nu alleen vindbaar via Instagram en mond-tot-mond; StudioMatch haalt ze uit de anonimiteit en verbindt ze met artiesten die actief naar studiotijd zoeken.

**Voor artiesten:** zoeken op locatie, prijs, datum, apparatuur en meer; direct online boeken en veilig betalen via iDEAL of creditcard.

**Voor studio-eigenaren:** één agenda, automatische facturatie en snelle uitbetaling via Stripe Connect. De verhuurder ontvangt 100% van zijn uurtarief (servicekosten liggen bij de artiest), zonder abonnement of exclusiviteit.

### Kernprincipes (uit de scope)

1. Eén vloeiende boekingsflow gaat vóór veel functies
2. Edge cases via de admin, niet via code
3. Stripe Connect is de financiële ruggengraat
4. Mobiel is niet "ook", mobiel is leidend
5. Alles wat niet nodig is voor `zoeken → boeken → betalen → uitbetalen` is fase 2

## MVP-scope in het kort

| In de MVP | Bewust niet (fase 2 / buiten scope) |
|---|---|
| Publieke site met SEO-basis (NL + EN) | Blog/CMS *(BESLISSING 13: naar fase 2)* |
| Studio- en ruimteprofielen met goedkeuringsflow | Agenda-sync (iCal/Google) *(grootste risico, zie scope)* |
| Zoeken en filteren + kaartweergave | Chat tussen artiest en studio |
| Beschikbaarheid, blokkades en vakantiemodus | Reviews *(placeholder-UI aanwezig, formeel beslispunt)* |
| Boeken en betalen via Stripe (iDEAL + creditcard) | Kortingscodes, toeslagen, dagdeeltarieven |
| Facturatie en btw via Moneybird | Borg, verzekering en no-showboetes |
| Uitbetaling via Stripe Connect Express | Native app, meerdere landen/valuta |
| Dashboards: artiest, verhuurder en admin | Automatische dispute-/chargebackworkflows |
| "Meld een probleem"-flow met payout-hold | Eigen KYC/IBAN-validatie *(doet Stripe)* |

De volledige functionele afbakening, beslispunten (1 t/m 17) en risico's staan in [`studiomatch-scope.md`](studiomatch-scope.md).

## Status

Het platform is functioneel compleet, op de onderdelen na die op aanleveringen van de klant wachten.

**Publieke site (NL/EN, echte data)** — home met uitgelichte studio's en kaart, zoeken met werkende filters (incl. datum/tijd op echte beschikbaarheid), studiopagina's met slug-URL's, boekingskalender, exacte kaartpin (PDOK-geocoding), schema.org, sitemap.xml en cookiebanner.

**Artiest** — registreren/inloggen (ook ín de boekflow), boeken met slot-reservering (15 min blokkade), prijsopbouw en huisregels-akkoord, verzetten (1x, tot 48u vooraf), annuleren met staffel, "meld een probleem" met bewijsfoto's, accountbeheer (AVG-verwijdering).

**Verhuurder** — bedrijfsgegevens, meerdere studio's met automatische geocoding, ruimtebeheer met reviewflow (min. 5 foto's), beschikbaarheid (weekschema, uitzonderingen, blokkades, vakantiemodus), iCal-export, boekingsinbox met 24-uurstermijn, agenda, omzetoverzicht en schade melden.

**Admin** — goedkeuringswachtrij, tickets met payout-hold, alle boekingen met handmatige statuswijziging (vangnet), gebruikers, omzet per studio en CSV-exports.

**Automatisering** — `bookings:maintain` (scheduler, elke 5 min): verlopen betaalblokkades, auto-annulering na 24u zonder reactie, herinneringsmails en afronden van sessies. Alle mails uit de mailmatrix lopen via notifications en loggen lokaal naar `storage/logs/laravel.log` tot Mailgun is geconfigureerd.

### Testaccounts (na `php artisan db:seed`)

| Rol | E-mail | Wachtwoord |
|---|---|---|
| Artiest | `artiest@studiomatch.test` | `wachtwoord123` |
| Verhuurder | `verhuurder@studiomatch.test` | `wachtwoord123` |
| Demo-verhuurder (met data) | `demo-verhuurder@studiomatch.test` | `wachtwoord123` |
| Admin | `admin@studiomatch.test` | `wachtwoord123` |

### Wacht op aanlevering klant

| Wat | Ontgrendelt |
|---|---|
| Mailgun-gegevens (`MAIL_*`) | Echte mails, e-mailverificatie, contactformulier |
| Geverifieerd Stripe-account | Betaalfase: iDEAL/kaart, Connect-onboarding, refunds, uitbetaling op start + 24u |
| Moneybird + btw-bevestiging accountant | Facturatie (scope §2.6) |
| Definitieve juridische teksten | AV, privacy, disclaimer, cookiebeleid *(stubs staan klaar)* |
| BESLISSING 13 | Blog wel/niet in MVP |

## Huisstijl

| Kleur | Hex | Gebruik |
|---|---|---|
| ![#AD0924](https://img.shields.io/badge/AD0924-AD0924?style=flat-square) | `#AD0924` | Ruby Red - accenten, CTA's |
| ![#101529](https://img.shields.io/badge/101529-101529?style=flat-square) | `#101529` | Prussian Blue - primaire kleur, donkere vlakken |
| ![#383E46](https://img.shields.io/badge/383E46-383E46?style=flat-square) | `#383E46` | Charcoal Blue - secundair |
| ![#FFFFFF](https://img.shields.io/badge/FFFFFF-FFFFFF?style=flat-square) | `#FFFFFF` | White |

- **Font:** [Inter](https://rsms.me/inter/) (400/500/600/700), geladen via Bunny Fonts (AVG-vriendelijk)
- **Logo's:** [`public/logos/`](public/logos/) (primair/secundair/sub-mark, in blauw en wit)
- **Tailwind-tokens:** gedefinieerd in [`resources/css/app.css`](resources/css/app.css) (`bg-ruby-red`, `text-prussian-blue`, enz.)

## Techniek

| Laag | Keuze |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Blade-components + Tailwind CSS 4 + Vite 8 |
| Database | MySQL (`studiomatch`) |
| Betalingen | Stripe Connect Express *(gepland)* |
| Facturatie | Moneybird *(gepland)* |
| i18n | Laravel-localisatie, NL (standaard) + EN |
| Iconen | Font Awesome (lokaal, `public/fontawesome/`) |

## Aan de slag

**Vereisten:** PHP 8.4+, Composer, Node.js, MySQL (bijv. via [Laragon](https://laragon.org)).

```bash
# 1. Alles in één keer: composer install, .env, app-key, migraties, npm install + build
composer run setup

# 2. Maak de database aan (naam: studiomatch) en check de DB-gegevens in .env

# 3. Migraties, storage-link en demodata
php artisan migrate
php artisan storage:link
php artisan db:seed

# 4. Ontwikkelen: server + queue + logs + Vite in één commando
composer run dev

# 5. Scheduler (boekingsonderhoud) in een tweede terminal
php artisan schedule:work
```

### Handige commando's

| Commando | Doel |
|---|---|
| `php artisan test` | Volledige testsuite (feature-tests) |
| `php artisan bookings:maintain` | Boekingsonderhoud eenmalig draaien |
| `php artisan studios:geocode` | Studio-adressen geocoderen via PDOK (`--force` voor alles) |
| `php artisan db:seed` | Demodata (idempotent) |

Draai je via Laragon, dan is de site bereikbaar op `http://studiomatch.test`. Los ontwikkelen kan ook met `php artisan serve` + `npm run dev`.

> **Let op:** laat tijdens het stylen altijd `npm run dev` draaien; Tailwind 4 genereert alleen classes die het in de templates tegenkomt. Zie je geen styling en draait er geen dev-server, verwijder dan een achtergebleven `public/hot`-bestand of draai `npm run build`.

## Projectstructuur (hoogtepunten)

```
resources/
├── css/app.css              # Tailwind-thema: kleuren, fonts, animaties
├── js/app.js                # Dropdowns, sliders, header, modals, scroll-reveal
└── views/
    ├── components/          # layout, header, footer, hero, studio-card,
    │                        # studio-slider, filter-group, phone-mockup, ...
    └── *.blade.php          # pagina's (welcome, studios, hosts, how, faq, ...)
lang/
├── nl/                      # Nederlands (standaard)
└── en/                      # Engels
config/localization.php      # ondersteunde talen
studiomatch-scope.md         # functionele scope, beslispunten en risico's
```

---

<p align="center">
  <sub>© StudioMatch VOF · KvK 94893527 · Software gemaakt in samenwerking met <a href="https://eazyonline.nl"><strong>Eazyonline</strong></a></sub>
</p>
