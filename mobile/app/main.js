// StudioMatch-app: de schermen zitten in de app zelf, de server levert alleen gegevens (api/app/*).
// Wat hier nog geen eigen scherm heeft, opent de app als serverpagina met een eenmalige inlogcode.
import Alpine from 'alpinejs';
import './app.css';
import bundledTexts from './texts.json';
import { iconSvg } from './icons.js';

const API = import.meta.env.VITE_STUDIOMATCH_API;
const TOKEN_KEY = 'sm-token';
const PROFILE_KEY = 'sm-profile';
const TEXTS_KEY = 'sm-texts';
const CACHE_PREFIX = 'sm-cache:';
const PUSH_KEY = 'sm-push';
// Rollen die pushmeldingen krijgen (nu: de beheerder, bij een nieuw ticket). Anderen vragen we niet om
// toestemming zolang er niets voor ze is.
const PUSH_ROLES = ['admin'];
// Serverpaden die in de app een eigen scherm hebben (AppMenu::SCREENS op de server).
const SCREEN_PATHS = {
    '/dashboard/artiest': 'boekingen', '/studios': 'studios', '/dashboard/verhuurder/boekingen': 'inbox', '/dashboard/verhuurder/agenda': 'agenda',
    '/dashboard/admin': 'overzicht', '/dashboard/admin/tickets': 'tickets', '/dashboard/admin/wachtrij': 'wachtrij', '/dashboard/admin/boekingen': 'admin-boekingen',
    '/dashboard/admin/gebruikers': 'gebruikers', '/dashboard/admin/omzet': 'omzet', '/dashboard/admin/blog': 'blog',
};
// Schermen per rol; 'boeking' en 'ruimte' zijn detailschermen (met een id), 'account' heeft iedereen.
const ROLE_SCREENS = {
    artiest: ['boekingen', 'boeking', 'studios', 'studio', 'boeken', 'afrekenen', 'betalen', 'verzetten', 'account'],
    verhuurder: ['inbox', 'agenda', 'account'],
    admin: ['overzicht', 'tickets', 'wachtrij', 'ruimte', 'admin-boekingen', 'gebruikers', 'omzet', 'blog', 'account'],
};
// Detailschermen: zonder id naar het beginscherm. Terug: naar het scherm erboven (':id' = hetzelfde nummer,
// bijv. van afrekenen terug naar het kiezen in dezelfde studio). De tab die oplicht staat in TAB_OF.
const DETAIL_SCREENS = ['boeking', 'ruimte', 'studio', 'boeken', 'afrekenen', 'betalen', 'verzetten'];
const PARENT_SCREENS = {
    boeking: 'boekingen', ruimte: 'wachtrij', gebruikers: 'account', omzet: 'account', blog: 'account',
    studio: 'studios', boeken: 'studio/:id', afrekenen: 'boeken/:id', betalen: 'boeking/:id', verzetten: 'boeking/:id',
};
const TAB_OF = { studio: 'studios', boeken: 'studios', afrekenen: 'studios', betalen: 'boekingen', verzetten: 'boekingen' };
// Het moment dat de artiest koos (Kies een moment), tot het afrekenen: ook na een herstart van de app.
const DRAFT_KEY = 'sm-draft';

const capacitor = () => window.Capacitor?.Plugins ?? {};

const storage = {
    get: (key) => { try { return localStorage.getItem(key); } catch { return null; } },
    set: (key, value) => { try { localStorage.setItem(key, value); } catch { /* geen opslag */ } },
    remove: (key) => { try { localStorage.removeItem(key); } catch { /* geen opslag */ } },
};
const readJson = (key) => { try { return JSON.parse(storage.get(key) ?? 'null'); } catch { return null; } };

// Het token staat ook in de native opslag (Preferences): iOS mag de opslag van de webview leegmaken als
// de telefoon vol raakt, en dan zou je zomaar uitgelogd zijn.
const secure = {
    set: (value) => capacitor().Preferences?.set({ key: TOKEN_KEY, value }).catch(() => {}),
    remove: () => capacitor().Preferences?.remove({ key: TOKEN_KEY }).catch(() => {}),
    get: async () => (await capacitor().Preferences?.get({ key: TOKEN_KEY }).catch(() => null))?.value ?? null,
};

const phoneLocale = () => ((navigator.language ?? '').toLowerCase().startsWith('nl') ? 'nl' : 'en');

class ApiError extends Error {
    constructor(status, data) {
        super(`HTTP ${status}`);
        this.status = status;
        this.data = data;
    }
}

async function api(path, { method = 'GET', body } = {}) {
    const token = storage.get(TOKEN_KEY);
    const store = Alpine.store('app');
    let response;
    try {
        response = await fetch(`${API}/api/app/${path}`, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'Accept-Language': store?.locale ?? phoneLocale(),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch (error) {
        // Geen verbinding met de server: laten zien dat de gegevens van eerder zijn.
        if (store) store.offline = true;
        throw error;
    }
    if (store) store.offline = false;
    if (response.status === 401 && token) store?.forget();
    if (!response.ok) throw new ApiError(response.status, await response.json().catch(() => null));
    return response.status === 204 ? null : response.json();
}

const routeFromHash = () => {
    const [route, param] = location.hash.replace(/^#\/?/, '').split('?')[0].split('/');
    return { route: route || null, param: /^\d+$/.test(param ?? '') ? param : null };
};

// Opmaak in de taal van de gebruiker (bedragen in centen, zoals de server ze geeft).
const format = {
    euro: (cents) => new Intl.NumberFormat(Alpine.store('app').locale === 'nl' ? 'nl-NL' : 'en-GB', { style: 'currency', currency: 'EUR' }).format((cents ?? 0) / 100),
    moment: (at) => {
        const date = new Date(at);
        const locale = Alpine.store('app').locale === 'nl' ? 'nl-NL' : 'en-GB';
        const time = new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' }).format(date);
        return date.toDateString() === new Date().toDateString()
            ? `${Alpine.store('app').t('common.today')} ${time}`
            : new Intl.DateTimeFormat(locale, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(date);
    },
    // "9 nov. 2026"
    date: (at) => new Intl.DateTimeFormat(Alpine.store('app').locale === 'nl' ? 'nl-NL' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(at)),
};
window.smFormat = format;

Alpine.store('app', {
    token: storage.get(TOKEN_KEY),
    profile: readJson(PROFILE_KEY),
    texts: readJson(TEXTS_KEY),
    route: null,
    param: null,
    offline: false,
    shownAt: null,
    toastText: null,
    toastSticky: false,
    toastOpen: false,
    question: null,
    leaving: false,
    chosenLocale: null,
    previousRoute: null,
    previousPath: null,
    version: null,
    // Een venster onderin dat een scherm open heeft (sluiten met de terugknop van Android, smBack).
    sheetClose: null,
    // De taal waarnaar de app aan het wisselen is.
    localeLoading: null,

    get signedIn() {
        return Boolean(this.token && this.profile);
    },
    get role() {
        return this.profile?.user?.role ?? null;
    },
    get locale() {
        return this.profile?.user?.locale ?? this.chosenLocale ?? this.texts?.locale ?? phoneLocale();
    },
    // De teksten: van het account, anders van de server voor deze taal, anders die uit de build.
    get ui() {
        if (this.profile?.ui) return this.profile.ui;
        if (this.texts?.locale === this.locale && this.texts.ui) return this.texts.ui;
        return bundledTexts[this.locale] ?? bundledTexts.nl;
    },
    get tabs() {
        return this.profile?.menu?.tabs ?? [];
    },
    get links() {
        return this.profile?.menu?.links ?? [];
    },
    // t('login.title') of t('inbox.respond_before', { time: '...' })
    t(key, replacements = {}) {
        let text = key.split('.').reduce((node, part) => node?.[part], this.ui) ?? key;
        Object.entries(replacements).forEach(([name, value]) => { text = text.replace(`:${name}`, value); });
        return text;
    },
    count(name) {
        return this.profile?.counts?.[name] ?? 0;
    },
    // plural('admin.bookings', 3): "3 boekingen" (sleutels _none, _one en _many)
    plural(key, count) {
        return this.t(`${key}_${count === 0 ? 'none' : count === 1 ? 'one' : 'many'}`, { count });
    },
    // De kop: de naam van het scherm, en op een detailscherm een knop terug.
    get title() {
        const key = {
            boekingen: 'bookings.title', boeking: 'booking.title', studios: 'studios.title', inbox: 'inbox.title', agenda: 'agenda.title', account: 'account.title',
            overzicht: 'admin.overview', tickets: 'admin.tickets', wachtrij: 'admin.queue', ruimte: 'admin.room', 'admin-boekingen': 'admin.bookings',
            gebruikers: 'admin.users', omzet: 'admin.revenue', blog: 'admin.blog',
            studio: 'studio.title', boeken: 'book.title', afrekenen: 'checkout.title', betalen: 'payment.title', verzetten: 'reschedule.title',
        }[this.route];
        return key ? this.t(key) : '';
    },
    get backTo() {
        return PARENT_SCREENS[this.route]?.replace(':id', this.param) ?? null;
    },
    // De tab die oplicht: het scherm zelf, of op een detailscherm het scherm erboven.
    get activeTab() {
        return this.tabs.some((tab) => tab.screen === this.route) ? this.route : (TAB_OF[this.route] ?? PARENT_SCREENS[this.route]);
    },
    // Terug in de geschiedenis als je van de lijst kwam (dan staat die er nog zoals je hem verliet), anders
    // naar de lijst zonder een extra stap (bijv. na openen vanuit een melding).
    back() {
        if (this.previousPath === this.backTo) history.back();
        else location.replace(`#/${this.backTo}`);
    },
    // Een sessie in de agenda: een aanvraag beantwoord je in Aanvragen, de rest staat op de website.
    openAgendaEntry(entry) {
        if (entry.status === 'pending_confirmation') location.hash = '#/inbox';
        else if (entry.path) this.openWeb(entry.path);
    },

    init() {
        window.addEventListener('hashchange', () => this.navigate());
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.token) this.refreshProfile().then(() => preload(this.role));
        });
        // Elke minuut de tellers; zonder verbinding elke 15 seconden kijken of de server er weer is.
        setInterval(() => document.visibilityState === 'visible' && !this.offline && this.refreshProfile(), 60_000);
        setInterval(() => this.offline && document.visibilityState === 'visible' && this.refreshProfile().then(() => !this.offline && preload(this.role)), 15_000);
        // Terug van een serverpagina: de webview kan deze pagina uit zijn geheugen halen, met het
        // wachtscherm ("pagina openen") er nog over. Dat moet dan weg, en de gegevens vers.
        window.addEventListener('pageshow', (event) => {
            if (!event.persisted) return;
            this.leaving = false;
            if (this.token) this.refreshProfile().then(() => preload(this.role));
        });
        capacitor().StatusBar?.setStyle({ style: 'DARK' }).catch(() => {});
        capacitor().StatusBar?.setBackgroundColor({ color: '#101529' }).catch(() => {});
        // Versie en build onderaan Account, handig bij vragen.
        capacitor().App?.getInfo().then((info) => { this.version = `${info.version} (${info.build})`; }).catch(() => {});
        this.navigate();
        // Pas na het laden van dit bestand: Alpine roept init() al aan bij het registreren van de store,
        // als fetchScreen en de cache verderop nog niet bestaan.
        setTimeout(() => this.restore());
    },

    // Na een herstart: het token terug uit de native opslag als de webview het kwijt is (iOS).
    async restore() {
        capacitor().SplashScreen?.hide().catch(() => {});
        if (!this.token) {
            const token = await secure.get();
            if (token && this.profile) {
                this.token = token;
                storage.set(TOKEN_KEY, token);
            }
        }
        if (!this.token) {
            this.loadTexts();
            return;
        }
        preload(this.role);
        await this.refreshProfile();
        registerPush();
    },

    async loadTexts(locale = this.locale) {
        try {
            const texts = await api(`teksten?locale=${locale}`);
            this.texts = texts;
            storage.set(TEXTS_KEY, JSON.stringify(texts));
        } catch { /* offline: de teksten uit de build */ }
    },

    allowed(route) {
        return (ROLE_SCREENS[this.role] ?? []).includes(route);
    },
    get home() {
        return this.tabs[0]?.screen ?? 'account';
    },

    navigate() {
        const { route, param } = routeFromHash();
        if (route !== this.route || param !== this.param) {
            this.previousRoute = this.route;
            this.previousPath = this.route ? [this.route, this.param].filter(Boolean).join('/') : null;
        }
        // Een melding na een actie (bijv. "betaald") blijft staan op het scherm waar de actie heen gaat.
        if (route !== this.route && !this.toastSticky) this.toastOpen = false;
        this.toastSticky = false;
        if (route !== this.route && this.sheetClose) this.sheetClose();
        if (this.signedIn && (!route || !this.allowed(route) || (DETAIL_SCREENS.includes(route) && !param))) {
            history.replaceState(null, '', `#/${this.home}`);
            this.route = this.home;
            this.param = null;
        } else {
            this.route = route;
            this.param = param;
        }
        window.scrollTo(0, 0);
    },

    // Een pad op de server: naar het scherm in de app als dat er is, anders de serverpagina.
    go(path) {
        const pathname = path.split('?')[0];
        if (SCREEN_PATHS[pathname] && this.allowed(SCREEN_PATHS[pathname])) location.hash = `#/${SCREEN_PATHS[pathname]}`;
        else this.openWeb(path);
    },

    remember(token, profile) {
        this.token = token;
        storage.set(TOKEN_KEY, token);
        secure.set(token);
        this.setProfile(profile);
        this.navigate();
        preload(this.role);
        registerPush();
    },

    setProfile(profile) {
        this.profile = profile;
        storage.set(PROFILE_KEY, JSON.stringify(profile));
    },

    forget() {
        cache.clear();
        this.token = null;
        this.profile = null;
        storage.remove(TOKEN_KEY);
        storage.remove(PROFILE_KEY);
        storage.remove(PUSH_KEY);
        secure.remove();
        // Ook de websessie van deze telefoon (serverpagina's) eindigt.
        capacitor().CapacitorCookies?.clearAllCookies().catch(() => {});
        history.replaceState(null, '', '#/');
        this.route = null;
        this.loadTexts();
    },

    async refreshProfile() {
        if (!this.token) return;
        try {
            this.setProfile(await api('ik'));
        } catch { /* offline: de vorige gegevens blijven staan */ }
    },

    async logout() {
        if (!(await this.ask(this.t('account.logout_confirm'), this.t('account.logout')))) return;
        try {
            // Met het pushtoken, zodat deze telefoon na uitloggen geen meldingen meer krijgt.
            await api('uitloggen', { method: 'POST', body: { push_token: readJson(PUSH_KEY)?.token } });
        } catch { /* offline: het token vervalt op de telefoon toch */ }
        this.forget();
    },

    // Taal wisselen: laten zien dat het bezig is (localeLoading) tot de teksten én de schermen in de nieuwe
    // taal binnen zijn, anders staat er even een mengelmoes van twee talen.
    async setLocale(locale) {
        if (locale === this.locale || this.localeLoading) return;
        this.localeLoading = locale;
        try {
            if (!this.signedIn) {
                this.chosenLocale = locale;
                await this.loadTexts(locale);
                return;
            }
            this.setProfile(await api('taal', { method: 'PATCH', body: { locale } }));
            // De schermen bevatten teksten van de server: in de nieuwe taal opnieuw ophalen.
            cache.clear();
            Object.keys(fetchedAt).forEach((name) => delete fetchedAt[name]);
            await preload(this.role);
            window.dispatchEvent(new Event('sm-refresh'));
        } catch {
            this.toast(this.t('common.failed'));
        } finally {
            this.localeLoading = null;
        }
    },
    // "Taal wijzigen..." in de taal waar je naartoe gaat (die zit al in de app).
    get switchingText() {
        return (bundledTexts[this.localeLoading] ?? bundledTexts.nl)?.common?.switching ?? '';
    },

    // Een scherm dat nog niet in de app zit: de serverpagina openen, ingelogd via een eenmalige code.
    async openWeb(path) {
        this.leaving = true;
        try {
            const { url } = await api('webcode', { method: 'POST', body: { naar: path, terug: location.origin } });
            location.href = url;
        } catch {
            this.leaving = false;
            this.toast(this.t('common.failed'));
        }
    },

    // Pagina's voor wie (nog) niet is ingelogd, zoals registreren.
    // Pagina's voor wie (nog) niet is ingelogd, zoals registreren. De terugknop van Android (MainActivity)
    // gaat een pagina terug, dus ook van een serverpagina weer naar de app.
    openGuestPage(path) {
        this.leaving = true;
        location.href = `${API}${path}`;
    },

    // De tekst blijft staan tot de melding helemaal is weggefaded, anders zie je even een leeg balkje.
    // sticky: de melding blijft staan bij het scherm waar je daarna heen gaat (bijv. na betalen).
    toast(text, { sticky = false } = {}) {
        this.toastSticky = sticky;
        this.toastText = text;
        this.toastOpen = true;
        setTimeout(() => { if (this.toastText === text) this.toastOpen = false; }, 3500);
    },

    // Bevestigen in een eigen venster in plaats van de grijze browserdialoog.
    ask(text, confirmLabel) {
        return new Promise((resolve) => { this.question = { text, confirmLabel: confirmLabel ?? this.t('common.yes'), resolve }; });
    },
    answer(yes) {
        this.question?.resolve(yes);
        this.question = null;
    },
});

// Gegevens van een scherm: eerst wat er al bewaard is (meteen zichtbaar, ook na een herstart of zonder
// verbinding), daarna vers van de server. Per gebruiker, en weg bij uitloggen.
const memory = {};
const cache = {
    key: (name) => `${CACHE_PREFIX}${readJson(PROFILE_KEY)?.user?.id ?? 'onbekend'}:${name}`,
    read(name) {
        if (!(name in memory)) {
            const json = storage.get(cache.key(name));
            try {
                memory[name] = json ? { data: JSON.parse(json), json, at: Number(storage.get(`${cache.key(name)}:at`)) || null } : null;
            } catch {
                memory[name] = null;
            }
        }
        return memory[name];
    },
    write(name, data, json) {
        memory[name] = { data, json, at: Date.now() };
        storage.set(cache.key(name), json);
        cache.touch(name);
    },
    // Gelijk gebleven, maar wel net opgehaald: alleen de tijd bijwerken.
    touch(name) {
        if (memory[name]) memory[name].at = Date.now();
        storage.set(`${cache.key(name)}:at`, String(Date.now()));
    },
    clear() {
        Object.keys(memory).forEach((name) => delete memory[name]);
        try {
            Object.keys(localStorage).filter((key) => key.startsWith(CACHE_PREFIX)).forEach((key) => localStorage.removeItem(key));
        } catch { /* geen opslag */ }
    },
};

// Eén verzoek per scherm tegelijk, en kort na een verse lading niet nog een keer.
const FRESH_MS = 20_000;
const inflight = {};
const fetchedAt = {};

function fetchScreen(name, path, { force = false } = {}) {
    if (inflight[name]) return inflight[name];
    if (!force && Date.now() - (fetchedAt[name] ?? 0) < FRESH_MS) return Promise.resolve(cache.read(name));
    inflight[name] = api(path)
        .then((fresh) => {
            const json = JSON.stringify(fresh);
            if (json !== cache.read(name)?.json) cache.write(name, fresh, json);
            else cache.touch(name);
            fetchedAt[name] = Date.now();
            return cache.read(name);
        })
        .finally(() => delete inflight[name]);
    return inflight[name];
}

// Bij het opstarten alles van de onderbalk alvast ophalen, zodat elk scherm al vers is als je erheen gaat.
async function preload(role) {
    const screens = { artiest: [['boekingen', 'boekingen'], ['studios', 'studios']], verhuurder: [['inbox', 'inbox'], ['agenda', 'agenda']], admin: [['overzicht', 'beheer/overzicht'], ['tickets', 'beheer/tickets'], ['wachtrij', 'beheer/wachtrij'], ['admin-boekingen', 'beheer/boekingen']] }[role] ?? [];
    for (const [name, path] of screens) {
        try {
            await fetchScreen(name, path);
        } catch { /* offline: het scherm probeert het zelf weer bij openen */ }
    }
}

// ===== Pushmeldingen (Firebase) =====
// Na inloggen en bij elke start: toestemming vragen, het token van deze telefoon ophalen en aanmelden bij
// de server (api/app/apparaten). Niet vaker dan nodig: alleen bij een ander token, een andere gebruiker
// of na een dag. Zonder Firebase-configuratie (google-services.json) of verbinding gebeurt er niets.
async function registerPush() {
    const app = Alpine.store('app');
    const messaging = capacitor().FirebaseMessaging;
    if (!messaging || !app.signedIn || !PUSH_ROLES.includes(app.role)) return;
    try {
        let { receive } = await messaging.checkPermissions();
        if (receive !== 'granted') ({ receive } = await messaging.requestPermissions());
        if (receive !== 'granted') return;
        const platform = window.Capacitor?.getPlatform?.() ?? 'android';
        if (platform === 'android') {
            await messaging.createChannel({ id: 'meldingen', name: app.locale === 'nl' ? 'Meldingen' : 'Notifications', importance: 4 });
        }
        const { token } = await messaging.getToken();
        const user = app.profile?.user?.id;
        const previous = readJson(PUSH_KEY);
        if (previous?.token === token && previous?.user === user && Date.now() - previous.at < 86_400_000) return;
        await api('apparaten', { method: 'POST', body: { token, platform } });
        storage.set(PUSH_KEY, JSON.stringify({ token, user, at: Date.now() }));
    } catch { /* geen Firebase of geen verbinding: bij de volgende start opnieuw */ }
}

// Tikken op een melding opent waar die over gaat (bijv. /dashboard/admin/tickets: het scherm Tickets).
// Staat de app open, dan toont hij geen melding, maar worden de teller en het scherm meteen ververst.
setTimeout(() => {
    const messaging = capacitor().FirebaseMessaging;
    if (!messaging) return;
    messaging.addListener('notificationActionPerformed', ({ notification }) => {
        const url = notification?.data?.url;
        if (url && Alpine.store('app').signedIn) Alpine.store('app').go(url);
    });
    messaging.addListener('notificationReceived', () => {
        Object.keys(fetchedAt).forEach((name) => delete fetchedAt[name]);
        Alpine.store('app').refreshProfile();
        window.dispatchEvent(new Event('sm-refresh'));
    });
});

function screen(name, path) {
    return {
        cacheName: name,
        path,
        data: null,
        shownJson: null,
        failed: false,
        missing: false,
        async init() {
            // Een pushmelding terwijl dit scherm open is: meteen vers ophalen.
            this.onRefresh = () => this.load({ force: true });
            window.addEventListener('sm-refresh', this.onRefresh);
            this.present(cache.read(this.cacheName));
            await this.load();
        },
        destroy() {
            window.removeEventListener('sm-refresh', this.onRefresh);
        },
        // Ander filter: eerst wat er van die weergave bewaard is, dan vers.
        show(cacheName, path) {
            this.cacheName = cacheName;
            this.path = path;
            this.data = null;
            this.shownJson = null;
            this.present(cache.read(cacheName));
            return this.load();
        },
        // Alleen bijwerken als het echt anders is dan wat er staat, anders blijft alles rustig staan.
        present(entry) {
            if (!entry) return;
            Alpine.store('app').shownAt = entry.at;
            if (entry.json === this.shownJson) return;
            this.data = entry.data;
            this.shownJson = entry.json;
        },
        async load({ force = false } = {}) {
            const name = this.cacheName;
            this.failed = false;
            try {
                const entry = await fetchScreen(name, this.path, { force });
                if (name === this.cacheName) this.present(entry);
            } catch (error) {
                if (name !== this.cacheName || error.status === 401) return;
                this.missing = error.status === 404;
                this.failed = this.data === null && !this.missing;
            }
        },
        // Een actie gaf nieuwe gegevens terug: meteen tonen en bewaren.
        apply(fresh) {
            const json = JSON.stringify(fresh);
            cache.write(this.cacheName, fresh, json);
            fetchedAt[this.cacheName] = Date.now();
            this.present(cache.read(this.cacheName));
        },
    };
}

Alpine.data('login', () => ({
    email: '',
    password: '',
    busy: false,
    error: null,
    // De foutmelding komt van de server in de taal van dat moment: bij een andere taal weg.
    init() {
        this.$watch('$store.app.locale', () => { this.error = null; });
    },
    async submit() {
        const app = Alpine.store('app');
        this.busy = true;
        this.error = null;
        try {
            const { token, ...profile } = await api('inloggen', { method: 'POST', body: { email: this.email, password: this.password, device: 'StudioMatch-app' } });
            app.remember(token, profile);
            this.password = '';
        } catch (error) {
            this.error = error.data?.errors?.email?.[0] ?? error.data?.message ?? app.t('login.no_connection');
        }
        this.busy = false;
    },
}));

Alpine.data('bookings', () => ({ ...screen('boekingen', 'boekingen'), tab: 'upcoming' }));

Alpine.data('booking', (id) => ({
    ...screen(`boeking:${id}`, `boekingen/${id}`),
    busy: false,
    // Een eigen scherm in de app (screen), annuleren hier (cancel), anders de serverpagina (path).
    async run(action) {
        const app = Alpine.store('app');
        if (action.screen) {
            location.hash = `#/${action.screen}`;
            return;
        }
        if (!action.cancel) {
            app.openWeb(action.path);
            return;
        }
        if (!(await app.ask(action.cancel, action.label))) return;
        this.busy = true;
        try {
            const result = await api(`boekingen/${id}/annuleren`, { method: 'POST' });
            this.apply(result.booking);
            app.toast(result.message);
            bookingsChanged();
        } catch (error) {
            app.toast(errorText(error));
        }
        this.busy = false;
    },
    openStudio() {
        if (this.data.studio_id) location.hash = `#/studio/${this.data.studio_id}`;
        else Alpine.store('app').openWeb(this.data.studio_path);
    },
}));

// Na boeken, betalen, annuleren of verzetten: Mijn boekingen en de tellers bij openen opnieuw ophalen.
function bookingsChanged() {
    delete fetchedAt.boekingen;
    Alpine.store('app').refreshProfile();
}

// ===== Boeken =====
// "2026-11-10"; de dagen zelf rekenen we zonder tijdzone, zoals de server ze geeft.
const dayKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const parseDay = (key) => new Date(`${key}T00:00:00`);
// 25 is 01:00 de volgende ochtend (zoals Hours op de server).
const clock = (hour) => `${String(hour === 24 ? 24 : hour % 24).padStart(2, '0')}:00`;
const intlLocale = () => (Alpine.store('app').locale === 'nl' ? 'nl-NL' : 'en-GB');

// De kalender met vrije dagen en de starttijden: voor boeken en voor verzetten. free = { dag: [vrije uren] }.
function calendar() {
    return {
        view: null,
        selectedDate: null,
        selectedStart: null,
        // Boven 24 is de volgende ochtend; alleen als alle uren van de sessie vrij zijn.
        startsFor(key, duration) {
            const free = this.free()?.[key] ?? [];
            const set = new Set(free);
            return free.filter((hour) => {
                for (let i = 1; i < duration; i++) if (!set.has(hour + i)) return false;
                return true;
            });
        },
        firstMonth() {
            const keys = Object.keys(this.free() ?? {});
            const first = keys.find((key) => this.dayEnabled(key)) ?? keys[0];
            if (!first) return null;
            const date = parseDay(first);
            return new Date(date.getFullYear(), date.getMonth(), 1);
        },
        lastMonth() {
            const keys = Object.keys(this.free() ?? {});
            if (!keys.length) return null;
            const date = parseDay(keys[keys.length - 1]);
            return new Date(date.getFullYear(), date.getMonth(), 1);
        },
        ensureView() {
            if (!this.view && this.free()) this.view = this.firstMonth();
        },
        monthLabel() {
            return this.view ? new Intl.DateTimeFormat(intlLocale(), { month: 'long', year: 'numeric' }).format(this.view) : '';
        },
        canPrev() {
            return this.view && this.firstMonth() && this.view > this.firstMonth();
        },
        canNext() {
            return this.view && this.lastMonth() && this.view < this.lastMonth();
        },
        shiftMonth(step) {
            this.view = new Date(this.view.getFullYear(), this.view.getMonth() + step, 1);
        },
        weekdays() {
            const format = new Intl.DateTimeFormat(intlLocale(), { weekday: 'short' });
            // 1 januari 2024 was een maandag.
            return [0, 1, 2, 3, 4, 5, 6].map((i) => format.format(new Date(2024, 0, i + 1)).replace('.', '').slice(0, 2));
        },
        // Lege vakjes tot de eerste dag van de maand, daarna elke dag. Methodes en geen getters, want dit
        // object wordt in een scherm uitgespreid en dan rekent een getter maar een keer.
        cells() {
            if (!this.view) return [];
            const offset = (new Date(this.view.getFullYear(), this.view.getMonth(), 1).getDay() + 6) % 7;
            const count = new Date(this.view.getFullYear(), this.view.getMonth() + 1, 0).getDate();
            const cells = Array.from({ length: offset }, (_, i) => ({ key: `leeg-${i}`, blank: true }));
            for (let day = 1; day <= count; day++) {
                const key = dayKey(new Date(this.view.getFullYear(), this.view.getMonth(), day));
                cells.push({ key, day, enabled: this.dayEnabled(key) });
            }
            return cells;
        },
        nextDayName(key) {
            const next = parseDay(key);
            next.setDate(next.getDate() + 1);
            return new Intl.DateTimeFormat(intlLocale(), { weekday: 'short' }).format(next).replace(/\.$/, '');
        },
        startLabel(hour) {
            return hour >= 24 ? `${clock(hour)} (${this.nextDayName(this.selectedDate)})` : clock(hour);
        },
        dateLabel(key) {
            return key ? new Intl.DateTimeFormat(intlLocale(), { weekday: 'long', day: 'numeric', month: 'long' }).format(parseDay(key)) : '';
        },
    };
}

const readDraft = () => readJson(DRAFT_KEY);

// Kies een moment: per uur (datum, duur, starttijd) of per hele dag (eerste en laatste dag), eventueel met
// engineer. De prijs rekent de app voor zoals de server (BookRoom::prices); de server rekent hem na.
Alpine.data('picker', (id) => ({
    ...screen(`studio:${id}`, `studios/${id}`),
    ...calendar(),
    mode: 'hours',
    hours: 1,
    rangeStart: null,
    rangeEnd: null,
    extending: false,
    engineer: false,
    async init() {
        // Terug van afrekenen: hetzelfde moment staat er weer.
        const draft = readDraft();
        if (draft?.roomId === Number(id)) {
            this.mode = draft.end_date ? 'days' : 'hours';
            this.engineer = Boolean(draft.engineer);
            if (draft.end_date) {
                this.rangeStart = draft.date;
                this.rangeEnd = draft.end_date;
            } else {
                this.selectedDate = draft.date;
                this.selectedStart = draft.start;
                this.hours = draft.hours;
            }
        }
        this.$watch('data', () => this.prepare());
        this.onRefresh = () => this.load({ force: true });
        window.addEventListener('sm-refresh', this.onRefresh);
        this.present(cache.read(this.cacheName));
        this.prepare();
        // De vrije momenten altijd vers: een ander kan net iets geboekt hebben.
        await this.load({ force: true });
    },
    prepare() {
        if (!this.data) return;
        const booking = this.data.booking;
        this.hours = Math.min(booking.max_hours, Math.max(booking.min_hours, this.hours || booking.min_hours));
        if (this.selectedDate && !this.startsFor(this.selectedDate, this.hours).includes(this.selectedStart)) this.selectedStart = null;
        if (this.selectedDate && !this.dayEnabled(this.selectedDate)) this.selectedDate = null;
        if (this.view) return;
        const chosen = this.mode === 'days' ? this.rangeStart : this.selectedDate;
        this.view = chosen ? new Date(parseDay(chosen).getFullYear(), parseDay(chosen).getMonth(), 1) : this.firstMonth();
    },
    free() {
        return this.data?.booking.free_hours ?? null;
    },
    dayEnabled(key) {
        if (this.mode === 'days') return (this.data?.booking.free_days ?? []).includes(key);
        return this.startsFor(key, this.hours).length > 0;
    },
    setMode(mode) {
        if (mode === this.mode) return;
        this.mode = mode;
        this.selectedDate = this.selectedStart = this.rangeStart = this.rangeEnd = null;
        this.extending = false;
        this.view = this.firstMonth();
    },
    setHours(value) {
        const booking = this.data.booking;
        this.hours = Math.min(booking.max_hours, Math.max(booking.min_hours, value));
        if (this.selectedDate && !this.startsFor(this.selectedDate, this.hours).includes(this.selectedStart)) this.selectedStart = null;
        if (this.selectedDate && !this.dayEnabled(this.selectedDate)) this.selectedDate = null;
    },
    // Eerste tik kiest één dag, een latere dag daarna verlengt de periode (als alle dagen ertussen vrij zijn).
    pick(cell) {
        if (!cell.enabled) return;
        if (this.mode === 'hours') {
            this.selectedDate = cell.key;
            this.selectedStart = null;
            return;
        }
        if (this.extending && cell.key > this.rangeStart && this.rangeFree(this.rangeStart, cell.key)) {
            this.rangeEnd = cell.key;
            this.extending = false;
            return;
        }
        this.rangeStart = this.rangeEnd = cell.key;
        this.extending = true;
    },
    rangeFree(from, until) {
        const free = new Set(this.data.booking.free_days);
        for (const cursor = parseDay(from); cursor <= parseDay(until); cursor.setDate(cursor.getDate() + 1)) {
            if (!free.has(dayKey(cursor))) return false;
        }
        return true;
    },
    cellClass(cell) {
        if (this.mode === 'days') {
            if (cell.key === this.rangeStart || cell.key === this.rangeEnd) return 'bg-ruby-red font-bold text-white';
            if (this.rangeStart && this.rangeEnd && cell.key > this.rangeStart && cell.key < this.rangeEnd) return 'bg-ruby-red/15 font-semibold';
        } else if (cell.key === this.selectedDate) {
            return 'bg-ruby-red font-bold text-white';
        }
        return cell.enabled ? 'font-semibold active:bg-ruby-red/10' : 'text-prussian-blue/25';
    },
    get dayCount() {
        return this.rangeStart && this.rangeEnd ? Math.round((parseDay(this.rangeEnd) - parseDay(this.rangeStart)) / 86_400_000) + 1 : 0;
    },
    get rentCents() {
        const booking = this.data?.booking;
        if (!booking) return 0;
        if (this.mode === 'hours') return (booking.hourly_cents + (this.engineer ? booking.engineer_hour_cents : 0)) * this.hours;
        if (!this.dayCount) return 0;
        let rent = 0;
        for (const cursor = parseDay(this.rangeStart); cursor <= parseDay(this.rangeEnd); cursor.setDate(cursor.getDate() + 1)) {
            const weekday = cursor.getDay() === 0 ? 7 : cursor.getDay();
            rent += (booking.day_rates[weekday] ?? booking.day_cents) + (this.engineer ? booking.engineer_day_cents : 0);
        }
        return rent;
    },
    // Huur, dan servicekosten over de huur, dan btw over de servicekosten: zoals de server.
    get totalCents() {
        const booking = this.data?.booking;
        if (!booking) return 0;
        const fee = Math.round((this.rentCents * booking.fee_percent) / 100);
        return this.rentCents + fee + Math.round((fee * booking.vat_percent) / 100);
    },
    get ready() {
        if (this.mode === 'hours') return Boolean(this.selectedDate && this.selectedStart !== null);
        return this.dayCount >= this.data.booking.min_days && this.dayCount <= this.data.booking.max_days;
    },
    get rangeLabel() {
        const app = Alpine.store('app');
        if (!this.rangeStart) return app.t('book.pick_range');
        const format = new Intl.DateTimeFormat(intlLocale(), { day: 'numeric', month: 'short' });
        const days = this.dayCount === 1 ? app.t('book.days_one') : app.t('book.days_many', { count: this.dayCount });
        return `${format.format(parseDay(this.rangeStart))} - ${format.format(parseDay(this.rangeEnd))}, ${days}`;
    },
    next() {
        if (!this.ready) return;
        const draft = this.mode === 'days'
            ? { roomId: Number(id), date: this.rangeStart, end_date: this.rangeEnd, engineer: this.engineer }
            : { roomId: Number(id), date: this.selectedDate, start: this.selectedStart, hours: this.hours, engineer: this.engineer };
        storage.set(DRAFT_KEY, JSON.stringify(draft));
        location.hash = `#/afrekenen/${id}`;
    },
}));

const draftQuery = (draft) => new URLSearchParams(Object.entries({
    date: draft.date, end_date: draft.end_date, start: draft.start, hours: draft.hours, engineer: draft.engineer ? 1 : '',
}).filter(([, value]) => value !== undefined && value !== null && value !== '')).toString();

// Controleren: het moment en de prijs van de server, gegevens voor de factuur, de huisregels en akkoord.
// Daarna houdt de server het tijdslot vast (wacht op betaling) en gaat het naar betalen.
Alpine.data('checkout', (id) => ({
    data: null,
    failed: false,
    // Het moment is niet (meer) te boeken: de melding van de server.
    problem: null,
    busy: false,
    errors: {},
    form: { buyer_type: 'particulier', buyer_company: '', buyer_vat_number: '', street: '', postal_code: '', city: '', terms: false },
    async init() {
        const draft = readDraft();
        if (draft?.roomId !== Number(id)) {
            location.replace(`#/boeken/${id}`);
            return;
        }
        await this.load();
    },
    async load() {
        this.failed = false;
        try {
            this.data = await api(`studios/${id}/afrekenen?${draftQuery(readDraft())}`);
            this.form.street ||= this.data.address.street ?? '';
            this.form.postal_code ||= this.data.address.postal_code ?? '';
            this.form.city ||= this.data.address.city ?? '';
        } catch (error) {
            if (error.status === 422) this.problem = errorText(error);
            else if (error.status === 404) this.problem = Alpine.store('app').t('studio.not_found');
            else this.failed = true;
        }
    },
    async submit() {
        const app = Alpine.store('app');
        this.errors = {};
        this.busy = true;
        try {
            const draft = readDraft();
            const booking = await api(`studios/${id}/boeken`, {
                method: 'POST',
                body: { ...Object.fromEntries(new URLSearchParams(draftQuery(draft))), ...this.form, terms: this.form.terms ? 1 : 0 },
            });
            // Het betaalscherm en de boeking tonen meteen wat de server teruggaf.
            const json = JSON.stringify(booking);
            cache.write(`boeking:${booking.id}`, booking, json);
            fetchedAt[`boeking:${booking.id}`] = Date.now();
            storage.remove(DRAFT_KEY);
            delete fetchedAt[`studio:${id}`];
            bookingsChanged();
            location.replace(`#/betalen/${booking.id}`);
        } catch (error) {
            const errors = error.data?.errors ?? {};
            if (errors.slot) this.problem = errors.slot[0];
            else if (error.status === 422) this.errors = Object.fromEntries(Object.entries(errors).map(([key, list]) => [key, list[0]]));
            else app.toast(errorText(error));
        }
        this.busy = false;
    },
}));

// Betalen: hoe lang het tijdslot nog vaststaat, en de knop. Met Stripe op de beveiligde betaalpagina (een
// serverpagina die na betalen terug naar de app gaat), zonder Stripe rondt de app de demo-betaling af.
Alpine.data('payment', (id) => ({
    ...screen(`boeking:${id}`, `boekingen/${id}`),
    now: Date.now(),
    busy: false,
    async init() {
        this.timer = setInterval(() => { this.now = Date.now(); }, 1000);
        this.onRefresh = () => this.load({ force: true });
        window.addEventListener('sm-refresh', this.onRefresh);
        this.present(cache.read(this.cacheName));
        await this.load();
    },
    destroy() {
        clearInterval(this.timer);
        window.removeEventListener('sm-refresh', this.onRefresh);
    },
    get secondsLeft() {
        if (!this.data?.payment) return 0;
        return Math.max(0, Math.floor((new Date(this.data.payment.expires_at) - this.now) / 1000));
    },
    get timeLeft() {
        const seconds = this.secondsLeft;
        return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
    },
    get expired() {
        return this.data?.status === 'expired' || (this.data?.payment && this.secondsLeft === 0);
    },
    async pay() {
        const app = Alpine.store('app');
        if (this.data.payment.stripe) {
            app.openWeb(this.data.actions.find((action) => action.primary)?.path ?? `/boekingen/${id}/betalen`);
            return;
        }
        this.busy = true;
        try {
            const result = await api(`boekingen/${id}/betalen`, { method: 'POST' });
            this.apply(result.booking);
            bookingsChanged();
            app.toast(result.message, { sticky: true });
            location.replace(`#/boeking/${id}`);
        } catch (error) {
            if (error.data?.booking) this.apply(error.data.booking);
            app.toast(errorText(error));
        }
        this.busy = false;
    },
}));

// Verzetten: een nieuw moment met dezelfde duur, één keer.
Alpine.data('reschedule', (id) => ({
    ...screen(`verzetten:${id}`, `boekingen/${id}/verzetten`),
    ...calendar(),
    busy: false,
    problem: null,
    async init() {
        this.$watch('data', () => this.ensureView());
        this.onRefresh = () => this.load({ force: true });
        window.addEventListener('sm-refresh', this.onRefresh);
        this.present(cache.read(this.cacheName));
        this.ensureView();
        await this.load({ force: true });
    },
    free() {
        return this.data?.free_hours ?? null;
    },
    dayEnabled(key) {
        return this.startsFor(key, this.data?.hours ?? 1).length > 0;
    },
    pick(cell) {
        if (!cell.enabled) return;
        this.selectedDate = cell.key;
        this.selectedStart = null;
    },
    cellClass(cell) {
        if (cell.key === this.selectedDate) return 'bg-ruby-red font-bold text-white';
        return cell.enabled ? 'font-semibold active:bg-ruby-red/10' : 'text-prussian-blue/25';
    },
    async submit() {
        const app = Alpine.store('app');
        this.busy = true;
        this.problem = null;
        try {
            const result = await api(`boekingen/${id}/verzetten`, { method: 'POST', body: { date: this.selectedDate, start: this.selectedStart } });
            const json = JSON.stringify(result.booking);
            cache.write(`boeking:${id}`, result.booking, json);
            fetchedAt[`boeking:${id}`] = Date.now();
            bookingsChanged();
            app.toast(result.message, { sticky: true });
            location.replace(`#/boeking/${id}`);
        } catch (error) {
            if (error.status === 422) this.problem = errorText(error);
            else app.toast(errorText(error));
            this.load({ force: true });
        }
        this.busy = false;
    },
}));

// De studio: foto's, wat er is en de prijs; "Boek nu" opent het kiezen van een moment.
Alpine.data('studio', (id) => ({
    ...screen(`studio:${id}`, `studios/${id}`),
    photo: 0,
    onScroll(event) {
        const el = event.target;
        this.photo = Math.round(el.scrollLeft / Math.max(1, el.clientWidth));
    },
}));

Alpine.data('studios', () => ({
    ...screen('studios', 'studios'),
    q: '',
    type: '',
    timer: null,
    search() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            const query = new URLSearchParams(Object.entries({ q: this.q.trim(), type: this.type }).filter(([, value]) => value)).toString();
            this.show(query ? `studios:${query}` : 'studios', query ? `studios?${query}` : 'studios');
        }, 300);
    },
    countLabel() {
        const count = this.data?.studios.length ?? 0;
        return count === 1 ? Alpine.store('app').t('studios.count_one') : Alpine.store('app').t('studios.count_many', { count });
    },
}));

Alpine.data('inbox', () => ({
    ...screen('inbox', 'inbox'),
    busy: null,
    async respond(booking, action) {
        const app = Alpine.store('app');
        if (action === 'weigeren' && !(await app.ask(app.t('inbox.decline_confirm'), app.t('inbox.decline')))) return;
        this.busy = booking.id;
        try {
            const result = await api(`inbox/${booking.id}/${action}`, { method: 'POST' });
            this.apply(result.inbox);
            // Kort houden: de lange tekst van de website bedekt anders drie regels van de lijst.
            app.toast(app.t(action === 'accepteren' ? 'inbox.accepted' : 'inbox.declined'));
        } catch (error) {
            // Al afgehandeld of verlopen: de melding van de server, en de bijgewerkte inbox.
            if (error.data?.inbox) this.apply(error.data.inbox);
            app.toast(error.data?.message ?? app.t('common.failed'));
        }
        this.busy = null;
        delete fetchedAt.agenda; // de agenda bij openen opnieuw
        app.refreshProfile();
    },
}));

Alpine.data('agenda', () => ({ ...screen('agenda', 'agenda') }));

// Beheerder: overzicht van het platform en de tickets. Afhandelen gebeurt op de website (openWeb), op de
// plek van dat ticket.
Alpine.data('overzicht', () => ({
    ...screen('overzicht', 'beheer/overzicht'),
    get openTickets() {
        return (this.data?.open_disputes ?? 0) + (this.data?.open_damages ?? 0);
    },
    // plural('tickets_open', 'tickets_none', 3): "3 open tickets wachten op jou"
    plural(key, noneKey, count) {
        const app = Alpine.store('app');
        return count === 0 ? app.t(`admin.${noneKey}`) : app.t(`admin.${key}_${count === 1 ? 'one' : 'many'}`, { count });
    },
}));

// Een venster onderin met een formulier (ticket afhandelen, afwijzen, status wijzigen). De terugknop van
// Android sluit het (smBack via app.sheetClose), net als een ander scherm openen.
const sheet = () => ({
    sheet: null,
    busy: false,
    error: null,
    openSheet(state) {
        this.sheet = state;
        this.error = null;
        Alpine.store('app').sheetClose = () => this.closeSheet();
    },
    closeSheet() {
        this.sheet = null;
        this.busy = false;
        Alpine.store('app').sheetClose = null;
    },
});

// De eerste validatiefout van de server ("Toelichting moet minimaal 10 tekens zijn."), anders de melding.
const errorText = (error) => (error.data?.errors ? Object.values(error.data.errors)[0][0] : (error.data?.message ?? Alpine.store('app').t('common.failed')));

// Na een actie van de beheerder: schermen met andere cijfers bij openen opnieuw ophalen, tellers bijwerken.
function adminChanged(...screens) {
    Object.keys(fetchedAt).filter((name) => screens.some((screen) => name === screen || name.startsWith(`${screen}:`))).forEach((name) => delete fetchedAt[name]);
    Alpine.store('app').refreshProfile();
}

Alpine.data('tickets', () => ({
    ...screen('tickets', 'beheer/tickets'),
    ...sheet(),
    photos(count) {
        return Alpine.store('app').t(count === 1 ? 'admin.photos_one' : 'admin.photos_many', { count });
    },
    handle(ticket) {
        this.openSheet({ ticket, percent: 100, note: '', response: '' });
    },
    // Een gemeld probleem: terugbetaling en toelichting (eerst bevestigen, want het geld gaat meteen terug).
    // Een melding van een verhuurder: een reactie.
    async submit() {
        const app = Alpine.store('app');
        const { ticket } = this.sheet;
        const dispute = ticket.kind === 'dispute';
        if (dispute && !(await app.ask(app.t('admin.resolve_confirm', { percent: this.sheet.percent }), app.t('admin.handle')))) return;
        this.busy = true;
        this.error = null;
        try {
            const result = dispute
                ? await api(`beheer/tickets/${ticket.id}/afhandelen`, { method: 'POST', body: { refund_percent: Number(this.sheet.percent), resolution_note: this.sheet.note } })
                : await api(`beheer/tickets/${ticket.id}/reageren`, { method: 'POST', body: { damage_response: this.sheet.response } });
            this.apply(result.tickets);
            this.closeSheet();
            app.toast(result.message);
            adminChanged('overzicht', 'admin-boekingen');
        } catch (error) {
            // Al afgehandeld (door iemand anders): de melding en de bijgewerkte lijst.
            if (error.data?.tickets) {
                this.apply(error.data.tickets);
                this.closeSheet();
                app.toast(error.data.message);
            } else {
                this.error = errorText(error);
            }
        }
        this.busy = false;
    },
}));

Alpine.data('wachtrij', () => ({ ...screen('wachtrij', 'beheer/wachtrij') }));

// Een ruimte beoordelen: live zetten (eerst bevestigen) of afwijzen met een reden.
Alpine.data('ruimte', (id) => ({
    ...screen(`ruimte:${id}`, `beheer/wachtrij/${id}`),
    ...sheet(),
    async approve() {
        const app = Alpine.store('app');
        if (!(await app.ask(app.t('admin.approve_confirm', { room: this.data.title }), app.t('admin.approve')))) return;
        await this.decide(`beheer/wachtrij/${id}/goedkeuren`, {});
    },
    reject() {
        this.openSheet({ reason: '' });
    },
    sendReject() {
        return this.decide(`beheer/wachtrij/${id}/afwijzen`, { rejection_reason: this.sheet.reason });
    },
    async decide(path, body) {
        const app = Alpine.store('app');
        this.busy = true;
        this.error = null;
        try {
            const result = await api(path, { method: 'POST', body });
            this.closeSheet();
            app.toast(result.message);
            adminChanged('wachtrij', 'overzicht', `ruimte:${id}`);
            app.back();
        } catch (error) {
            // Al beoordeeld of Stripe nog niet klaar: de melding, en de ruimte opnieuw ophalen.
            if (error.status === 409) {
                this.closeSheet();
                app.toast(error.data?.message ?? app.t('common.failed'));
                this.load({ force: true });
            } else {
                this.error = errorText(error);
            }
        }
        this.busy = false;
    },
}));

// Alle boekingen: filteren op status en de status handmatig wijzigen (vangnet voor randgevallen).
Alpine.data('adminBookings', () => ({
    ...screen('admin-boekingen', 'beheer/boekingen'),
    ...sheet(),
    status: '',
    filter(status) {
        this.status = status;
        this.show(status ? `admin-boekingen:${status}` : 'admin-boekingen', status ? `beheer/boekingen?status=${status}` : 'beheer/boekingen');
    },
    change(booking) {
        this.openSheet({ booking, status: booking.status });
    },
    async saveStatus() {
        const app = Alpine.store('app');
        const { booking, status } = this.sheet;
        if (status === booking.status) return this.closeSheet();
        const label = this.data.statuses.find((option) => option.value === status)?.label ?? status;
        if (!(await app.ask(app.t('admin.change_confirm', { status: label }), app.t('admin.save')))) return;
        this.busy = true;
        this.error = null;
        try {
            const result = await api(`beheer/boekingen/${booking.id}/status`, { method: 'POST', body: { status } });
            this.apply({ ...this.data, bookings: this.data.bookings.map((item) => (item.id === booking.id ? result.booking : item)) });
            this.closeSheet();
            app.toast(result.message);
            adminChanged('admin-boekingen', 'overzicht', 'tickets', 'omzet');
        } catch (error) {
            this.error = errorText(error);
        }
        this.busy = false;
    },
}));

Alpine.data('gebruikers', () => ({
    ...screen('gebruikers', 'beheer/gebruikers'),
    role: '',
    filter(role) {
        this.role = role;
        this.show(role ? `gebruikers:${role}` : 'gebruikers', role ? `beheer/gebruikers?rol=${role}` : 'beheer/gebruikers');
    },
    // "3 boekingen, lid sinds 9 nov. 2026"; bij een verhuurder de studio's, en boekingen alleen als die er zijn.
    line(user) {
        const app = Alpine.store('app');
        const host = user.role === 'verhuurder';
        return [
            !host || user.bookings > 0 ? app.plural('admin.bookings', user.bookings) : null,
            host ? app.plural('admin.studios', user.studios) : null,
            app.t('admin.member_since', { date: format.date(user.since) }),
        ].filter(Boolean).join(', ');
    },
}));

Alpine.data('omzet', () => ({ ...screen('omzet', 'beheer/omzet') }));

// De blog: de lijst in de app; schrijven en bewerken in de editor van de website.
Alpine.data('blog', () => ({
    ...screen('blog', 'beheer/blog'),
    status: '',
    filter(status) {
        this.status = status;
        this.show(status ? `blog:${status}` : 'blog', status ? `beheer/blog?status=${status}` : 'beheer/blog');
    },
    line(post) {
        const app = Alpine.store('app');
        return [app.plural('admin.sections', post.sections), post.author, format.date(post.updated_at)].filter(Boolean).join(', ');
    },
    async remove(post) {
        const app = Alpine.store('app');
        if (!(await app.ask(app.t('admin.delete_confirm', { title: post.title }), app.t('admin.delete')))) return;
        try {
            const result = await api(`beheer/blog/${post.id}`, { method: 'DELETE' });
            this.apply({ ...this.data, posts: this.data.posts.filter((item) => item.id !== post.id) });
            app.toast(result.message);
            adminChanged('blog');
        } catch (error) {
            app.toast(errorText(error));
        }
    },
}));

// <span x-icon="'fa-inbox'" class="h-5 w-5"></span>: dezelfde Font Awesome-iconen als de website.
Alpine.directive('icon', (el, { expression }, { evaluateLater, effect }) => {
    const name = evaluateLater(expression);
    el.classList.add('sm-icon');
    effect(() => name((value) => { el.innerHTML = iconSvg(value); }));
});

// De terugknop van Android (MainActivity) vraagt eerst of de app zelf iets terug doet: het venster onderin
// sluiten, of op een scherm met een scherm erboven (een boeking, een ruimte, Gebruikers) hetzelfde als de
// terugknop in de kop. De terugstap van de webview zelf slaat stappen over die zonder tik zijn ontstaan.
window.smBack = () => {
    const app = Alpine.store('app');
    if (app.question) {
        app.answer(false);
        return true;
    }
    if (app.sheetClose) {
        app.sheetClose();
        return true;
    }
    if (app.backTo) {
        app.back();
        return true;
    }
    return false;
};

window.Alpine = Alpine;
Alpine.start();
