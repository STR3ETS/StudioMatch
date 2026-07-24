# Studiomatch MVP — Scope

**Bron:** Uitvraag Studiomatch MVP, versie #1 (15-07-2026, Noah Kuijpers)
**Status:** voorstel — ter akkoord
**Gewenste livegang:** september 2026
**Beslissers:** Medie & Noah

---

## 0. Leeswijzer

| Label | Betekenis |
|---|---|
| **IN** | Zit in de MVP en in de prijs |
| **FASE 2** | Bewust niet nu — technisch voorbereid waar dat gratis is |
| **BESLISSING** | Blokkerend: moet beslist zijn vóór de bouw start |
| **AANLEVEREN** | Studiomatch levert aan; zonder dit geen livegang |

Deze scope volgt de uitvraag. Waar de uitvraag zichzelf tegenspreekt (dat gebeurt op 12 punten), staat het punt in **§4** met een voorstel erbij. Die punten zijn geen detail: drie ervan raken het verdienmodel en de betaalarchitectuur.

---

## 1. Scope-principes

Vijf principes, direct uit jullie eigen uitvraag. Bij twijfel over "hoort dit erbij?" is dit de tiebreaker.

1. **Eén vloeiende boekingsflow gaat vóór veel functies.** ("we geven de voorkeur aan een volledig geoptimaliseerde boekingsflow boven een breed scala aan extra functies")
2. **Edge cases via de admin, niet via code.** ("Handmatige 'Admin' vangnetten in plaats van dure code")
3. **Stripe Connect is de financiële ruggengraat.** Wij bouwen geen eigen KYC, geen eigen IBAN-verificatie, geen eigen uitbetalingsengine.
4. **Mobiel is niet "ook", mobiel is leidend.** ("mobiel is essentieel")
5. **Alles wat niet nodig is voor `zoeken → boeken → betalen → uitbetalen` is fase 2.**

Eén uitzondering die jullie zelf al benoemen: de **"Meld een probleem"-knop** met payout-hold. Dat is de enige uitzonderingsflow die wél gebouwd wordt.

---

## 2. IN SCOPE

### 2.1 Publieke site & content

- Homepage: zoekbalk + uitleg + uitgelichte studio's
- Zoekresultaten: kaart + lijst naast elkaar (desktop), lijst met kaart-toggle (mobiel)
- Studio-/ruimtedetailpagina
- Boekflow
- "Voor studio's" — wervingspagina met aanmeldknop
- Hoe werkt het / FAQ
- Contact (formulier → mail naar admin)
- Blog/nieuws — simpel CMS (titel, afbeelding, tekst, publicatiedatum) → zie BESLISSING 13
- Juridisch: algemene voorwaarden, privacyverklaring, disclaimer, cookiebeleid
- **NL + EN** voor interface en eigen content. Studioteksten (door verhuurders ingevuld) worden **niet** vertaald → zie BESLISSING 12
- **SEO-basis**: server-side gerenderde, indexeerbare studiopagina's, sitemap, schema.org-markup, meta per pagina. *Dit is geen extraatje: "vindbaarheid" is jullie kernbelofte richting studio's.*
- Cookiebanner + consent

### 2.2 Studio- en ruimteprofielen

**Per verhuurder:** bedrijfs- of persoonsnaam, adres, telefoon, type (particulier/ondernemer), btw-plichtig ja/nee, KvK, btw-nummer. IBAN + identiteit lopen via Stripe, niet via ons formulier.

**Per ruimte** (apart boekbaar — één studio kan meerdere ruimtes hebben):

| Veld | Opmerking |
|---|---|
| Titel + omschrijving | |
| Type | opname / mix-master (zie BESLISSING 6) |
| Foto's | minimaal 1, meerdere toegestaan |
| Adres + kaartje | met geocoding |
| Uurtarief | |
| Minimale boekingsduur | zie BESLISSING 1 |
| **Capaciteit (max. personen)** | *ontbreekt in F1, maar nodig voor de filter in W1 en het huisregel-scenario in W3* |
| **Huisregels** | *idem — W3 gaat ervan uit dat deze op de pagina staan en bij boeken geaccepteerd zijn* |
| Technicus/engineer inbegrepen | ja/nee |
| Apparatuurlijst | gestructureerd (categorie + naam) uit een vaste lijst, met vrij veld — anders is filteren op "specifieke microfoon" onmogelijk |
| DAW's | Logic, Pro Tools, FL Studio, Ableton, Cubase (multi-select) |
| Voorzieningen | wifi, parkeerplaats, keuken, magnetron, koelkast, koffie/thee, roken, airco (multi-select) |

**Statussen listing:** concept → in review → live / afgekeurd → vakantiemodus.

### 2.3 Zoeken & kaart

- Zoeken op plaats/adres + "in de buurt van mij" (geolocatie), instelbare straal
- Filters: afstand, prijs van–tot, datum + tijd (alleen daadwerkelijk vrije slots), type, apparatuur, DAW, voorzieningen, met/zonder engineer, capaciteit
- Sortering: afstand / prijs / relevantie
- Kaart + lijst naast elkaar als standaardweergave
- Prijs per uur, korte beschikbaarheid en Boek-knop in de resultatenlijst

### 2.4 Beschikbaarheid

- Wekelijks schema per ruimte + uitzonderingen (extra open/dicht)
- Blokkades (eigen sessie, onderhoud)
- Vakantiemodus (ruimte tijdelijk onzichtbaar)
- Slotraster van 1 uur, **geen buffer** tussen sessies
- Onbeperkt vooruit boekbaar
- Geboekte en geblokkeerde slots verdwijnen uit zoekresultaat en detailpagina
- Tijdzone vast op Europe/Amsterdam
- **Geen koppeling met externe agenda's** → zie RISICO 3

### 2.5 Boeken & betalen

- Account verplicht; registreren mag ín de flow (na klik op Boek)
- Rollen: artiest / verhuurder / admin
- **Slot-reservering**: bij start checkout krijgt de boeking status `pending_payment` en is het slot 15 minuten geblokkeerd; daarna automatisch vrij. *De zichtbare aftelklok is fase 2 (zoals in de uitvraag), maar de blokkade zelf niet — zonder blokkade krijg je dubbele boekingen tijdens checkout.*
- Prijsbreakdown vóór betaling (zie BESLISSING 2 + 3)
- Verplicht akkoord op huisregels + AV bij checkout, gelogd met tijdstip
- Stripe: iDEAL + creditcard (zie BESLISSING 15)
- Webhooks: `payment_intent.succeeded` / `.failed`, `charge.refunded`, `account.updated`
- Idempotency + database-lock op het slot tegen dubbele boekingen

**Statusmachine boeking:**

```
pending_payment ─15 min─→ expired
       │ betaling geslaagd
       ▼
paid / pending_confirmation ──weigering──→ refunded (100%)
       │ acceptatie          └─geen reactie─→ auto-cancelled + refund (BESLISSING 7)
       ▼
   confirmed ──artiest annuleert──→ cancelled (restitutie volgens staffel)
       │ sessie voorbij
       ▼
   completed ──"meld een probleem" (tot 24u na start)──→ disputed (payout on hold)
       │ +24u na starttijd, geen dispuut
       ▼
    payout vrijgegeven
```

### 2.6 Facturatie & btw

- Rekenlogica in het winkelmandje, vóór de betaalprovider — conform uitvraag
- Per boeking twee documenten: **huurfactuur namens de verhuurder** (0% of 21%) en **commissiefactuur van Studiomatch** (21% over de fee)
- Voor **niet-btw-plichtige verhuurders**: geen btw-factuur maar een huurbevestiging/betaalbewijs. Een particulier reikt geen btw-factuur uit.
- Creditfacturen bij (gedeeltelijke) restitutie
- Koppeling met **Moneybird** (aanbevolen boven PDFMonkey: echte administratie + creditnota's + boekhouding in één, PDFMonkey maakt alleen pdf's)
- Downloadbaar in beide dashboards

### 2.7 Uitbetaling

- **Stripe Connect Express + Stripe Hosted Onboarding** voor KYC, IBAN en naamcontrole. Wij bouwen geen document-uploads, geen IBAN-check, geen microdeposits.
- Dynamisch: particulier (ID + bank) vs. zakelijk (KvK + btw) — dit regelt Stripe zelf op basis van het accounttype
- Split op basis van 9% (zie BESLISSING 2 + 4)
- Listing kan pas live als Stripe `charges_enabled` én `payouts_enabled` teruggeeft
- Uitbetalingsspecificatie in het verhuurdersdashboard: bruto, commissie, btw, netto
- De feitelijke payout naar de bank doet Stripe volgens zijn standaardschema

### 2.8 Annuleren, verzetten, restitutie

| Situatie | Regel |
|---|---|
| Artiest annuleert > 48u vóór start | 100% terug |
| Artiest annuleert 24–48u vóór start | 50% terug (zie BESLISSING 5) |
| Artiest annuleert < 24u vóór start | geen restitutie |
| Artiest verzet | tot 48u vóór start, hele tijdsblok, zelfde ruimte, zelfde duur (zie BESLISSING 9) |
| Artiest wil wijzigen | annuleren + opnieuw boeken |
| Verhuurder **weigert** aanvraag (`pending_confirmation`) | automatisch 100% terug |
| Verhuurder wil **geaccepteerde** boeking annuleren | **geen knop** — mail naar admin → admin doet refund in Stripe → admin zet status handmatig op geannuleerd → platform mailt beide partijen |
| Artiest meldt probleem (tot 24u na starttijd) | payout on hold + supportticket + adminalert; admin bemiddelt handmatig |
| Chargeback | handmatig in Stripe |
| No-show artiest | systeem doet niets, tijd verloopt, verhuurder wordt betaald |
| Huisregels overtreden | systeem doet niets, boeking blijft voltooid, verhuurder wordt betaald |
| Schade | melding + bewijs via dashboard → adminalert → admin deelt gegevens → afhandeling buiten platform (zie RISICO 6) |

### 2.9 Dashboards

**Artiest:** komende & eerdere boekingen, detail met adres en contactgegevens, annuleren, verzetten, "meld een probleem", facturen/betaalbewijzen downloaden, profiel & gegevens beheren, wachtwoord, account verwijderen (AVG).

**Verhuurder:** onboardingchecklist, ruimtes toevoegen/bewerken/verwijderen, prijzen wijzigen, beschikbaarheid + blokkades + vakantiemodus, boekingsinbox (accepteren/weigeren), agendaweergave, omzetoverzicht (bruto → commissie → netto), uitbetalingsspecificaties, facturen, schade melden, Stripe-gegevens beheren.

**Admin:** goedkeuringswachtrij studio's (goedkeuren / afwijzen met reden / info opvragen), alle boekingen + statussen, gebruikers, omzet & commissie per studio, tickets, export naar Excel/CSV, handmatige statuswijziging, payout hold/release.

### 2.10 Notificaties — volledige mailmatrix

De lijst in F6 (4 mails) dekt de flows in W1 en W2 niet. Dit is de complete set die de MVP nodig heeft:

| Trigger | Naar |
|---|---|
| Verificatiemail bij registratie | artiest / verhuurder |
| Wachtwoord vergeten | beide |
| Welkomstmail verhuurder | verhuurder |
| Listing in review | verhuurder |
| Nieuwe studio aangemeld | admin |
| Listing goedgekeurd / studio live | verhuurder |
| Listing afgekeurd + reden | verhuurder |
| Nieuwe betaalde aanvraag — actie vereist | verhuurder |
| Aanvraag ontvangen, wacht op bevestiging | artiest |
| Herinnering: nog niet gereageerd | verhuurder |
| Boeking bevestigd (met adres + contactgegevens) | beide |
| Boeking geweigerd + terugbetaling | artiest |
| Automatisch geannuleerd (geen reactie) + terugbetaling | beide |
| Herinnering 24 uur vooraf | beide |
| Annuleringsbevestiging + restitutiebedrag | beide |
| Verzetbevestiging | beide |
| Factuur / betaalbewijs | artiest |
| Probleem gemeld | admin + verhuurder |
| Schade gemeld | admin |
| Contactformulier | admin |

Alles in NL/EN op basis van de gebruikerstaal. Transactionele mailprovider met SPF/DKIM/DMARC op studiomatch.nl — anders komen bevestigingsmails in de spambox en valt de hele flow om.

### 2.11 Techniek & randvoorwaarden

Mobile-first responsive · huisstijl toepassen · beeldverwerking (validatie, compressie, thumbnails, CDN) · rate limiting + spamprotectie · backups, logging, foutmonitoring · AVG (verwerkersovereenkomsten Stripe/Moneybird/mail/hosting, bewaartermijnen, accountverwijdering) · staging-omgeving + Stripe testmodus · oplevering + overdracht.

---

## 3. NIET IN SCOPE

| Onderwerp | Waarom |
|---|---|
| Mollie | Vervangen door Stripe Connect (uitvraag) |
| Eigen KYC / IBAN-validatie / microdeposits | Doet Stripe Hosted Onboarding (W2 stap 8 vervalt daarmee) |
| Kortingscodes / lanceringsactie | Fase 2 (uitvraag F4) |
| Zichtbare hold met aftelklok | Fase 2 (uitvraag W1 stap 4) |
| "Verleng je sessie"-knop | Fase 2 (uitvraag W3) — overtime = nieuwe boeking of onderling |
| Toeslagen (avond/weekend) | Fase 2 (uitvraag F1) |
| Dagdeeltarieven | Volgt uit "wij rekenen per uur" |
| Repetitie-, DJ- en podcastruimtes | Fase 2 (uitvraag F2) — datamodel houdt er rekening mee, is later een configuratie |
| No-showregistratie | "Eventueel" in de uitvraag → fase 2 |
| Agenda-sync (iCal / Google) | Uitvraag F3: alles in StudioMatch → **zie BESLISSING 6, dit is het grootste risico** |
| Reviews / beoordelingen | Staat nergens in de uitvraag. Let op: het Airbnb-vertrouwensmodel dat jullie als voorbeeld noemen leunt hier volledig op |
| Chat tussen artiest en studio | Staat nergens in de uitvraag; contact loopt via de gedeelde contactgegevens na bevestiging |
| ID-verificatie van artiesten | Alleen account + geslaagde betaling als identificatie ("Onzekerheid over de artiest" blijft dus deels staan) |
| Borg / verzekering / schadeafhandeling | Uitvraag W3: buiten het platform |
| No-showboete | Uitvraag W3: eigen risico artiest |
| Annuleerknop voor verhuurders bij overmacht | Bewust admin-handmatig (uitvraag) |
| Reconciliatiejobs, automatische dispute- en chargebackworkflows | Uitvraag "Vrije ruimte" zegt handmatig — de paragraaf "Uitzonderingen en vertrouwen" in W1 belooft dit wél; wij volgen "Vrije ruimte" |
| Native app | Mobiel web is essentieel, een app is niet gevraagd |
| Meerdere landen / valuta | Heel NL, EUR |
| Automatische vertaling van studioteksten | Verhuurder vult in wat hij invult |
| Abonnementen / featured listings / advertenties | Verdienmodel is 9% commissie |
| DAC7-rapportage | Zie RISICO 7 |

---

## 4. Tegenstrijdigheden in de uitvraag — beslissen vóór de bouw

### Blokkerend (raakt architectuur of verdienmodel)

**BESLISSING 1 — Minimale boekingsduur: 1 uur of 2 uur?**
F1 zegt "minimale afname van 2 uur". F3 zegt "kleinste boekbare blok: 1 uur". W2 stap 6 zegt dat de verhuurder zelf de minimale boekingsduur instelt. Dat zijn drie verschillende antwoorden.
→ *Voorstel:* raster van 1 uur, minimum **per ruimte instelbaar**, standaard 2 uur. Dekt alle drie.

**BESLISSING 2 — Wie betaalt de 9%?** ⚠️ *Belangrijkste openstaande punt*
De uitvraag zegt twee dingen tegelijk:
- W1 stap 5 — de artiest ziet "huur (netto), commissie; 21% btw over commissie, totaal inclusief btw" → **artiest betaalt de fee bovenop de huur**
- De uitbetalingsspecificatie noemt "bruto verhuur, commissie, btw over commissie, retentie en netto uitbetaling", en "Te hoge commissie holt de winstmarges uit" staat bij de afknappers → **verhuurder betaalt de fee uit de huursom**

| | Artiest betaalt bovenop | Verhuurder betaalt uit huursom |
|---|---|---|
| Verhuurder ontvangt | 100% van zijn tarief | 91% van zijn tarief |
| Commissiefactuur op naam van | artiest | verhuurder |
| Artiest ziet in zoekresultaat | tarief + servicekosten | gewoon het tarief |
| Sluit aan bij | Airbnb-model, W1 stap 5 | Peerspace-model, de payout-specificatie |

→ *Voorstel:* **artiest betaalt bovenop**. Dat matcht de meest uitgewerkte passage (W1 stap 5), matcht "het resterende bedrag gaat direct naar de verhuurder", en haalt de afknapper "te hoge commissie" volledig weg. Consequentie: prijzen in zoekresultaten tonen we all-in inclusief btw en servicekosten, met breakdown — een consument mag niet pas bij checkout verrast worden door onvermijdelijke kosten.

**BESLISSING 3 — De btw-schakelaar klopt niet.**
De uitvraag laat de rekenlogica afhangen van "particulier of zakelijk". Dat is de verkeerde schakelaar:
- Een ondernemer in de **KOR** rekent 0% btw. De uitvraag erkent dit zelf al ("BTW-nummer (indien btw-plichtig)").
- Een **particulier die structureel verhuurt** kan voor de btw juist wél ondernemer zijn.

→ *Voorstel:* de logica draait op een apart veld **`btw_plichtig: ja/nee`**, los van particulier/ondernemer. Verder twee vragen voor jullie accountant, vóór de bouw: (a) valt kortdurende studioverhuur onder 21% of onder de vrijstelling voor verhuur van onroerend goed, en (b) klopt de constructie waarin Studiomatch facturen uitreikt namens de verhuurder — dat moet in de AV geregeld zijn. *Wij bouwen de logica die jullie aanleveren; ik ben geen fiscalist.*

**BESLISSING 4 — Directe split of transfer ná het dispuutvenster?**
Twee eisen die elkaar uitsluiten:
- "Bij elke boeking splitst Stripe de betaling automatisch, het resterende bedrag gaat **direct** naar het Connected Account"
- "Bij een probleemmelding zet het systeem de uitbetaling **onmiddellijk op pauze**"

Met een directe split staat het geld al bij de verhuurder — dan valt er niets meer te pauzeren.
→ *Voorstel:* separate charges & transfers met een `transfer_group` per boeking. Het bedrag gaat automatisch door op **starttijd + 24 uur** (precies wanneer het dispuutvenster sluit), of nooit als er een melding ligt. De verhuurder wacht dus maximaal één dag na de sessie — dat blijft ruim binnen "snelle, transparante payout". Consequentie: Studiomatch treedt op als incassogemachtigde; dat moet in de AV staan.

**BESLISSING 5 — 50% restitutie: waarover precies?**
Bij annulering tussen 24 en 48 uur krijgt de artiest 50% terug. Onbeantwoord: 50% van het totaal of van de huur? Krijgt de verhuurder de andere 50%? Gaat de commissie mee terug? Welke creditnota's gaan er uit?
→ *Voorstel:* 50% van de huur naar de verhuurder, 50% van de huur terug naar de artiest, servicekosten volledig terug. Creditnota over het teruggestorte deel.

**BESLISSING 6 — Geen agenda-koppeling.**
F3 zegt: studio's beheren alles in StudioMatch. Bij "wat mag studio's níet afschrikken" staat op nummer één: *"Dubbel agenda-beheer: een systeem dat niet synchroniseert."* Dat is precies wat we dan bouwen. Met 100 actieve studio's als succescriterium is dit reëel de grootste afhaakreden.
→ *Voorstel:* iCal-**export** (een feed die de studio in Google/Apple Agenda zet) meenemen — klein werk, haalt de helft van de pijn weg. iCal-**import** (dubbele boekingen voorkomen vanuit hun eigen agenda) is fase 2.

**BESLISSING 7 — Hoe lang mag de verhuurder over acceptatie doen?**
W1 zegt "binnen een afgesproken tijd", maar die tijd is nergens afgesproken. Er staat ook niet wat er gebeurt als hij niet reageert — terwijl het geld van de artiest dan al binnen is.
→ *Voorstel:* 24 uur, en uiterlijk 2 uur vóór de starttijd. Geen reactie = automatisch annuleren + 100% terug + mail naar beide partijen. Herinnering naar de verhuurder na 12 uur.

### Vóór livegang

**BESLISSING 8 — Weigeren vs. annuleren door de verhuurder.**
F5 en W2 geven de verhuurder "boeking namens artiest annuleren" en "boeking weigeren of annuleren". W2 verbiedt vervolgens expliciet "eenzijdige annulering van betaalde boekingen".
→ *Voorstel:* **weigeren** kan (alleen bij `pending_confirmation`, met automatische refund), **annuleren** kan niet (alleen via admin). De knop "annuleren namens artiest" verdwijnt uit het verhuurdersdashboard.

**BESLISSING 9 — Verzetten: moet de verhuurder opnieuw akkoord geven?**
En: wat als het nieuwe slot een andere prijs heeft? Hoe vaak mag het?
→ *Voorstel:* 1x, tot 48 uur vóór start, zelfde ruimte, zelfde duur, alleen naar een vrij slot; de boeking gaat opnieuw naar `pending_confirmation`; bij weigering volledige restitutie. Zelfde duur + zelfde ruimte = zelfde prijs, dus geen verrekening nodig.

**BESLISSING 10 — Eén account = één rol?** Kan een verhuurder ook zelf een studio boeken? *Voorstel MVP: één rol per account.*

**BESLISSING 11 — Studiotypes.** F2 filtert op "opname, mix/master, repetitie", W1 op "opname, mix, repetitie, podcast", maar het antwoord eronder zegt: fase 1 alleen opname en mix/master. *Voorstel:* filter en veld met alleen opname + mix/master; de rest is later een configuratieregel, geen code.

**BESLISSING 12 — Engels bij livegang of fase 2?** En wie levert de Engelse teksten? Dit verdubbelt het contentwerk aan jullie kant, niet alleen aan die van ons.

**BESLISSING 13 — Blog bij livegang of fase 2?** Hij staat aangevinkt, maar draagt niets bij aan de boekingsflow.

**BESLISSING 14 — Kaart & geocoding:** Google Maps of Mapbox, en op wiens rekening? Dit zijn doorlopende kosten per verbruik.

**BESLISSING 15 — Betaalmethoden:** iDEAL + kaart genoeg, of ook Bancontact / Apple Pay / Google Pay?

**BESLISSING 16 — De Lovable-blueprint:** referentiemateriaal voor het ontwerp, of verwachten jullie hergebruik van die code? *Voorstel: referentie. De blueprint is een prototype, geen fundament.*

**BESLISSING 17 — De harde deadline.** "Vanaf het moment dat we onderhoudskosten moeten betalen (2 maanden na accepteren offerte)" is geen datum. Bij akkoord op bijvoorbeeld 1 augustus is dat 1 oktober — ná de gewenste livegang in september. Datum vastpinnen bij akkoord.

---

## 5. Aannames

- Eén land (NL), één valuta (EUR), één tijdzone (Europe/Amsterdam)
- Volume MVP: orde van grootte 100 studio's en enkele honderden boekingen — geen schaalarchitectuur
- Studio's vullen hun profiel zelf; wij doen geen data-entry
- Geen datamigratie vanuit een bestaand systeem
- Stripe-account op naam van de VOF; Stripe accepteert het marketplace-model
- Moneybird-abonnement op naam van Studiomatch
- Jullie leveren de definitieve juridische teksten; wij plaatsen ze en bouwen de flow eromheen
- Één ontwerp- en één contentronde per pagina

---

## 6. AANLEVEREN door Studiomatch

| Wat | Uiterlijk |
|---|---|
| Beslissingen 1 t/m 7 | vóór start bouw |
| Beslissingen 8 t/m 17 | eerste sprint |
| Definitieve AV, privacyverklaring, disclaimer, cookiebeleid | 3 weken vóór livegang |
| Alle teksten NL (+ EN indien beslissing 12 = ja) | volgens planning |
| Huisstijlpakket + fontlicenties voor webgebruik | start ontwerp |
| Domein studiomatch.nl + DNS-toegang | vóór mailconfiguratie |
| Geverifieerd Stripe-account + Moneybird-account | vóór bouw betaalflow |
| Bevestiging van de accountant op de btw-behandeling | vóór bouw rekenlogica |
| 5 tot 10 studio's mét toezegging | vóór livegang — zie RISICO 1 |

---

## 7. Risico's

1. **Koude start.** Jullie plan is om studio's te benaderen *nadat* het platform live is, met momenteel één toezegging. Een marktplaats die live gaat met een lege kaart heeft geen tweede kans op een eerste indruk. *Advies: zachte lancering — platform af, studio's onboarden in een besloten omgeving, pas openbaar bij 5 tot 10 live listings.*
2. **100 actieve studio's binnen 3 maanden** tegenover 5 à 10 bij livegang is 10 tot 20 keer groei. Dat is een acquisitievraagstuk, geen softwarevraagstuk — geen enkele feature lost dit op.
3. **Dubbel agendabeheer** (BESLISSING 6) is de afknapper die jullie zelf op nummer één zetten, en de MVP bouwt hem in.
4. **De AV is nog concept.** Daarin moeten minimaal: annuleringsstaffel, huisregels, incassogemachtigde, facturatie namens de verhuurder, en het verbod op omzeilen van het platform. Zonder definitieve AV geen livegang.
5. **Btw en particuliere verhuurders.** Zie BESLISSING 3. Laat dit door jullie accountant bevestigen — de rekenlogica zit in de checkout en op de facturen, dus achteraf wijzigen raakt beide.
6. **Schadescenario en AVG.** "De admin stelt de gegevens van de artiest beschikbaar aan de verhuurder" is het doorgeven van persoonsgegevens. Dat heeft een grondslag nodig en moet in de privacyverklaring en AV staan.
7. **DAC7.** Platforms die verhuur van onroerende zaken faciliteren hebben mogelijk een rapportageverplichting richting de Belastingdienst, inclusief het verzamelen van fiscale nummers van verhuurders. Dat raakt het datamodel en de onboarding. Laat dit vóór livegang uitzoeken — ik ben geen fiscalist, maar dit is een punt dat marktplaatsen structureel over het hoofd zien.
8. **Stripe-onboarding voor particulieren** is een KYC-flow met ID-controle. Reken op uitval bij hobbymatige home-studio-eigenaren.
9. **De acceptatiestap is frictie.** De artiest betaalt en moet dan alsnog afwachten. Dat botst met "in 3 klikken boeken". *Overweging voor fase 2: per studio instelbaar "direct boekbaar".*
10. **Toegankelijkheid (EAA).** Voor consumentgerichte e-commerce gelden sinds 2025 toegankelijkheidseisen, met een mogelijke vrijstelling voor micro-ondernemingen. Uitzoeken of jullie eronder vallen.

---

## 8. Wat deze scope níet is

Dit is de functionele afbakening, geen offerte en geen planning. Zodra beslissingen 1 t/m 7 vastliggen, kunnen we de bouw in fases hangen en de datum uit BESLISSING 17 tegen september 2026 leggen.
