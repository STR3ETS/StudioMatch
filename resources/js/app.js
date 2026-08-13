import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapEl = document.getElementById('studio-map');
if (mapEl) {
    const studios = JSON.parse(mapEl.dataset.studios || '[]');
    const perHour = mapEl.dataset.perHour || '/uur';

    const zoom = parseInt(mapEl.dataset.zoom || '7', 10);
    const center = zoom > 7 && studios.length ? [studios[0].lat, studios[0].lng] : [52.2, 5.4];

    const map = L.map(mapEl, {
        zoomControl: false,
        attributionControl: false,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        boxZoom: false,
        keyboard: false,
        minZoom: 7,
        maxZoom: 18,
        maxBounds: [[50.4, 2.6], [54.1, 7.8]],
        maxBoundsViscosity: 1.0,
    }).setView(center, zoom);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
    }).addTo(map);

    const nlFallback = [
        [3.38, 51.37], [3.44, 51.53], [4.12, 51.98], [4.28, 52.10], [4.60, 52.46],
        [4.75, 52.96], [5.42, 53.17], [6.20, 53.40], [6.83, 53.45], [7.20, 53.33],
        [7.06, 52.90], [6.70, 52.60], [7.05, 52.40], [6.98, 52.22], [6.40, 51.90],
        [6.20, 51.50], [6.17, 51.37], [6.00, 51.06], [5.70, 50.85], [5.65, 50.75],
        [5.56, 51.22], [5.24, 51.26], [5.00, 51.44], [4.65, 51.42], [4.25, 51.37],
        [3.90, 51.20], [3.55, 51.29], [3.38, 51.37],
    ];

    const applyHighlight = (rings, outline = false) => {
        const world = [[-30, 30], [30, 30], [30, 65], [-30, 65], [-30, 30]];
        L.geoJSON({ type: 'Polygon', coordinates: [world, ...rings] }, {
            interactive: false,
            style: { stroke: false, fillColor: '#05070f', fillOpacity: 0.55 },
        }).addTo(map);
        L.geoJSON({ type: 'MultiPolygon', coordinates: rings.map((ring) => [ring]) }, {
            interactive: false,
            style: outline
                ? { color: 'rgba(255,255,255,0.35)', weight: 1.5, fillColor: '#ffffff', fillOpacity: 0.06 }
                : { stroke: false, fillColor: '#ffffff', fillOpacity: 0.06 },
        }).addTo(map);
    };

    fetch('https://cartomap.github.io/nl/wgs84/landsdeel_2023.geojson')
        .then((response) => response.json())
        .then((geo) => {
            const rings = geo.features.flatMap((feature) => feature.geometry.type === 'MultiPolygon'
                ? feature.geometry.coordinates.map((polygon) => polygon[0])
                : [feature.geometry.coordinates[0]]);
            applyHighlight(rings);
        })
        .catch(() => applyHighlight([nlFallback], true));

    studios.forEach((studio) => {
        const icon = L.divIcon({ className: 'map-pin-wrap', html: `<span class="map-pin">&euro;${studio.price}</span>`, iconSize: null });
        const marker = L.marker([studio.lat, studio.lng], { icon }).addTo(map);
        const photos = studio.photos || [];
        const photosHtml = photos.map((photo, i) => `<span class="h-full w-full shrink-0 snap-start overflow-hidden"><img src="${photo}" alt="${studio.name}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"${i ? ' loading="lazy"' : ''}></span>`).join('');
        const dotsHtml = photos.map((photo, i) => `<span data-carousel-dot class="h-1.5 w-1.5 rounded-full shadow transition ${i ? 'bg-white/50' : 'bg-white'}"></span>`).join('');
        const arrowsHtml = photos.length > 1
            ? `<button type="button" data-carousel-prev class="absolute left-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/90 text-prussian-blue opacity-0 shadow transition hover:bg-white group-hover:opacity-100 sm:flex"><i class="fa-solid fa-chevron-left text-xs"></i></button>
               <button type="button" data-carousel-next class="absolute right-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/90 text-prussian-blue opacity-0 shadow transition hover:bg-white group-hover:opacity-100 sm:flex"><i class="fa-solid fa-chevron-right text-xs"></i></button>
               <span class="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center gap-1">${dotsHtml}</span>`
            : '';

        const popup = marker.bindPopup(
            `<a href="${studio.url}" class="map-card group">
                <span class="map-card-media" data-carousel>
                    <span data-carousel-track class="flex h-full snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">${photosHtml}</span>
                    ${arrowsHtml}
                </span>
                <span class="map-card-body">
                    <span class="map-card-row"><span class="map-card-title">${studio.name}</span>${studio.rating ? `<span class="map-card-rating">&#9733; ${studio.rating}</span>` : ''}</span>
                    <span class="map-card-city">${studio.city}</span>
                    <span class="map-card-price">&euro;${studio.price} <span>${perHour}</span></span>
                </span>
            </a>`,
            { closeButton: false, offset: [0, -16], maxWidth: 200 }
        ).getPopup();

        let pinned = false;
        let closeTimer = null;
        const cancelClose = () => clearTimeout(closeTimer);
        const scheduleClose = () => {
            if (pinned) return;
            cancelClose();
            closeTimer = setTimeout(() => marker.closePopup(), 250);
        };

        marker.on('mouseover', () => { cancelClose(); marker.openPopup(); });
        marker.on('mouseout', scheduleClose);
        marker.on('click', () => { pinned = true; marker.openPopup(); });
        marker.on('popupopen', () => {
            marker.getElement()?.classList.add('is-active');
            const el = popup.getElement();
            el.addEventListener('mouseenter', cancelClose);
            el.addEventListener('mouseleave', scheduleClose);
            el.querySelectorAll('[data-carousel]').forEach(initCarousel);
        });
        marker.on('popupclose', () => {
            pinned = false;
            cancelClose();
            marker.getElement()?.classList.remove('is-active');
        });
    });
}

const revealEls = document.querySelectorAll('section, footer, [data-reveal]');
if (revealEls.length && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach((el) => {
        el.classList.add('reveal');
        revealObserver.observe(el);
    });
}

const header = document.querySelector('[data-header]');
if (header) {
    const onHeaderScroll = () => {
        const scrolled = window.scrollY > 20;
        header.classList.toggle('py-2', scrolled);
        header.classList.toggle('py-5', !scrolled);
    };
    window.addEventListener('scroll', onHeaderScroll, { passive: true });
    onHeaderScroll();
}

document.querySelectorAll('[data-loadmore]').forEach((container) => {
    const grid = container.querySelector('[data-loadmore-grid]');
    const btn = container.querySelector('[data-loadmore-btn]');
    if (!grid || !btn) return;

    const step = parseInt(container.dataset.loadmoreStep || '16', 10);
    let visible = parseInt(container.dataset.loadmoreInitial || String(step), 10);

    const apply = () => {
        const items = Array.from(grid.children);
        items.forEach((item, i) => item.classList.toggle('hidden', i >= visible));
        btn.classList.toggle('hidden', visible >= items.length);
    };

    btn.addEventListener('click', () => {
        visible += step;
        apply();
    });

    apply();
});

const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
if (menuToggle && mobileMenu) {
    const menuIcon = menuToggle.querySelector('[data-menu-icon]');
    menuToggle.addEventListener('click', () => {
        const open = !mobileMenu.classList.toggle('hidden');
        menuToggle.setAttribute('aria-expanded', String(open));
        menuIcon.classList.toggle('fa-bars', !open);
        menuIcon.classList.toggle('fa-xmark', open);
        document.body.classList.toggle('overflow-hidden', open);
    });
}

const searchModal = document.querySelector('[data-search-modal]');
if (searchModal) {
    const setSearchModal = (open) => {
        searchModal.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
    };
    document.querySelectorAll('[data-search-open]').forEach((btn) => {
        btn.addEventListener('click', () => setSearchModal(true));
    });
    searchModal.querySelectorAll('[data-search-close]').forEach((btn) => {
        btn.addEventListener('click', () => setSearchModal(false));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !searchModal.classList.contains('hidden')) {
            setSearchModal(false);
        }
    });
}

const initCarousel = (carousel) => {
    if (carousel.dataset.carouselReady) return;
    carousel.dataset.carouselReady = '1';
    const track = carousel.querySelector('[data-carousel-track]');
    if (!track) return;
    const dots = carousel.querySelectorAll('[data-carousel-dot]');
    const update = () => {
        const index = Math.round(track.scrollLeft / track.clientWidth);
        dots.forEach((dot, i) => {
            dot.classList.toggle('bg-white', i === index);
            dot.classList.toggle('bg-white/50', i !== index);
        });
    };
    const page = (dir) => (event) => {
        event.preventDefault();
        event.stopPropagation();
        track.scrollBy({ left: dir * track.clientWidth, behavior: 'smooth' });
    };
    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', page(-1));
    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', page(1));
    track.addEventListener('scroll', update, { passive: true });
};

document.querySelectorAll('[data-carousel]').forEach(initCarousel);

document.querySelectorAll('[data-stepper]').forEach((stepper) => {
    const input = stepper.querySelector('input');
    const label = stepper.querySelector('[data-stepper-label]');
    const minus = stepper.querySelector('[data-stepper-minus]');
    const plus = stepper.querySelector('[data-stepper-plus]');
    if (!input || !label || !minus || !plus) return;
    const max = 16;
    const render = () => {
        const value = parseInt(input.value || '0', 10);
        label.textContent = value === 0 ? (stepper.dataset.stepperAny || '0') : String(value);
        minus.disabled = value <= 0;
        plus.disabled = value >= max;
    };
    minus.addEventListener('click', () => { input.value = String(Math.max(0, parseInt(input.value, 10) - 1)); render(); });
    plus.addEventListener('click', () => { input.value = String(Math.min(max, parseInt(input.value, 10) + 1)); render(); });
    render();
});

const lightbox = document.querySelector('[data-lightbox]');
if (lightbox) {
    const imgEl = lightbox.querySelector('img');
    const sources = Array.from(document.querySelectorAll('[data-lightbox-src]'));
    let current = 0;
    const show = (i) => {
        current = (i + sources.length) % sources.length;
        imgEl.src = sources[current].dataset.lightboxSrc;
    };
    const setOpen = (open) => {
        lightbox.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
    };
    sources.forEach((el, i) => el.addEventListener('click', () => { show(i); setOpen(true); }));
    lightbox.querySelector('[data-lightbox-close]')?.addEventListener('click', () => setOpen(false));
    lightbox.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => show(current - 1));
    lightbox.querySelector('[data-lightbox-next]')?.addEventListener('click', () => show(current + 1));
    document.addEventListener('keydown', (event) => {
        if (lightbox.classList.contains('hidden')) return;
        if (event.key === 'Escape') setOpen(false);
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });
}

document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((dropdown) => {
        if (!dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('details[data-dropdown][open]').forEach((dropdown) => {
            dropdown.removeAttribute('open');
        });
    }
});

document.querySelectorAll('[data-slider]').forEach((slider) => {
    const track = slider.querySelector('[data-slider-track]');
    if (!track) return;

    const prev = slider.querySelector('[data-slider-prev]');
    const next = slider.querySelector('[data-slider-next]');

    const update = () => {
        const max = track.scrollWidth - track.clientWidth - 1;
        if (prev) prev.disabled = track.scrollLeft <= 0;
        if (next) next.disabled = track.scrollLeft >= max;
    };

    prev?.addEventListener('click', () => track.scrollBy({ left: -track.clientWidth, behavior: 'smooth' }));
    next?.addEventListener('click', () => track.scrollBy({ left: track.clientWidth, behavior: 'smooth' }));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
});
