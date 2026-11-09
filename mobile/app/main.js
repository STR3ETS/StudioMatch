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
// Serverpaden die in de app een eigen scherm hebben (AppMenu::SCREENS op de server).
const SCREEN_PATHS = { '/dashboard/artiest': 'boekingen', '/studios': 'studios', '/dashboard/verhuurder/boekingen': 'inbox', '/dashboard/verhuurder/agenda': 'agenda' };
// Schermen per rol; 'boeking' is het detailscherm van de artiest, 'account' heeft iedereen.
const ROLE_SCREENS = { artiest: ['boekingen', 'boeking', 'studios', 'account'], verhuurder: ['inbox', 'agenda', 'account'], admin: ['account'] };

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
    question: null,
    leaving: false,
    chosenLocale: null,

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

    init() {
        window.addEventListener('hashchange', () => this.navigate());
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.token) this.refreshProfile().then(() => preload(this.role));
        });
        // Elke minuut de tellers; zonder verbinding elke 15 seconden kijken of de server er weer is.
        setInterval(() => document.visibilityState === 'visible' && !this.offline && this.refreshProfile(), 60_000);
        setInterval(() => this.offline && document.visibilityState === 'visible' && this.refreshProfile().then(() => !this.offline && preload(this.role)), 15_000);
        capacitor().App?.addListener('backButton', () => this.back());
        capacitor().StatusBar?.setStyle({ style: 'DARK' }).catch(() => {});
        capacitor().StatusBar?.setBackgroundColor({ color: '#101529' }).catch(() => {});
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
        if (this.signedIn && (!route || !this.allowed(route) || (route === 'boeking' && !param))) {
            history.replaceState(null, '', `#/${this.home}`);
            this.route = this.home;
            this.param = null;
        } else {
            this.route = route;
            this.param = param;
        }
        window.scrollTo(0, 0);
    },

    back() {
        if (this.question) {
            this.answer(false);
        } else if (this.route !== this.home && this.signedIn) {
            history.length > 1 ? history.back() : (location.hash = `#/${this.home}`);
        } else {
            capacitor().App?.minimizeApp();
        }
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
            await api('uitloggen', { method: 'POST' });
        } catch { /* offline: het token vervalt op de telefoon toch */ }
        this.forget();
    },

    async setLocale(locale) {
        if (!this.signedIn) {
            this.chosenLocale = locale;
            this.loadTexts(locale);
            return;
        }
        try {
            this.setProfile(await api('taal', { method: 'PATCH', body: { locale } }));
            // De schermen bevatten teksten van de server: in de nieuwe taal opnieuw ophalen.
            cache.clear();
            Object.keys(fetchedAt).forEach((name) => delete fetchedAt[name]);
            preload(this.role);
        } catch {
            this.toast(this.t('common.failed'));
        }
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
    openGuestPage(path) {
        this.leaving = true;
        location.href = `${API}${path}`;
    },

    toast(text) {
        this.toastText = text;
        setTimeout(() => { if (this.toastText === text) this.toastText = null; }, 3500);
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
    const screens = { artiest: [['boekingen', 'boekingen'], ['studios', 'studios']], verhuurder: [['inbox', 'inbox'], ['agenda', 'agenda']] }[role] ?? [];
    for (const [name, path] of screens) {
        try {
            await fetchScreen(name, path);
        } catch { /* offline: het scherm probeert het zelf weer bij openen */ }
    }
}

function screen(name, path) {
    return {
        cacheName: name,
        path,
        data: null,
        shownJson: null,
        failed: false,
        missing: false,
        async init() {
            this.present(cache.read(this.cacheName));
            await this.load();
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

Alpine.data('booking', (id) => ({ ...screen(`boeking:${id}`, `boekingen/${id}`) }));

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
            app.toast(result.message);
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

// <span x-icon="'fa-inbox'" class="h-5 w-5"></span>: dezelfde Font Awesome-iconen als de website.
Alpine.directive('icon', (el, { expression }, { evaluateLater, effect }) => {
    const name = evaluateLater(expression);
    el.classList.add('sm-icon');
    effect(() => name((value) => { el.innerHTML = iconSvg(value); }));
});

window.Alpine = Alpine;
Alpine.start();
