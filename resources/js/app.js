import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const initStudioMap = () => {
    const mapEl = document.getElementById('studio-map');
    if (!mapEl || mapEl.dataset.mapReady) return;
    mapEl.dataset.mapReady = '1';
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

    if (mapEl.dataset.approx) {
        studios.forEach((studio) => {
            L.circle([studio.lat, studio.lng], {
                radius: 400,
                color: '#AD0924',
                weight: 2,
                fillColor: '#AD0924',
                fillOpacity: 0.15,
            }).addTo(map);
        });
        return;
    }

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
};

initStudioMap();

const revealObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' })
    : null;

const initReveals = () => {
    if (!revealObserver) return;
    document.querySelectorAll('section, footer, [data-reveal]').forEach((el) => {
        if (el.classList.contains('reveal') || el.classList.contains('reveal-visible')) return;
        el.classList.add('reveal');
        revealObserver.observe(el);
    });
};

initReveals();

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

const initLoadMore = () => {
    document.querySelectorAll('[data-loadmore]').forEach((container) => {
        if (container.dataset.loadmoreReady) return;
        container.dataset.loadmoreReady = '1';

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
};

initLoadMore();

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
        const suffix = stepper.dataset.stepperSuffix || '';
        label.textContent = value === 0 ? (stepper.dataset.stepperAny || '0') : String(value) + suffix;
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

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    setTimeout(() => {
        event.target.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-60', 'pointer-events-none');
        });
    }, 0);
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[type="submit"]:disabled').forEach((button) => {
        button.disabled = false;
        button.classList.remove('opacity-60', 'pointer-events-none');
    });
});

const floatPanel = (toggle, panel, width) => {
    const rect = toggle.getBoundingClientRect();
    let left = rect.left + window.scrollX + rect.width / 2 - width / 2;
    left = Math.max(8, Math.min(left, window.scrollX + document.documentElement.clientWidth - width - 8));
    panel.style.position = 'absolute';
    panel.style.top = rect.bottom + window.scrollY + 8 + 'px';
    panel.style.left = left + 'px';
};

document.querySelectorAll('[data-datepicker]').forEach((wrap) => {
    const input = wrap.querySelector('input[type="hidden"]');
    const toggle = wrap.querySelector('[data-datepicker-toggle]');
    const label = wrap.querySelector('[data-datepicker-label]');
    const panel = wrap.querySelector('[data-datepicker-panel]');
    if (!input || !toggle || !label || !panel) return;

    const isFloat = panel.dataset.float !== undefined;
    if (isFloat) document.body.appendChild(panel);

    const locale = document.documentElement.lang || 'nl';
    const min = new Date((wrap.dataset.min || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
    const max = new Date(min);
    max.setDate(max.getDate() + 365);
    const monthFmt = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' });
    const dayFmt = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric' });
    const weekdayFmt = new Intl.DateTimeFormat(locale, { weekday: 'short' });
    const dateKey = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

    let view = input.value ? new Date(input.value + 'T00:00:00') : new Date(min);
    view = new Date(view.getFullYear(), view.getMonth(), 1);

    const render = () => {
        let html = '<div class="flex items-center justify-between">'
            + '<button type="button" data-dp-prev class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-prussian-blue/60 transition hover:bg-prussian-blue/5"><i class="fa-solid fa-chevron-left fa-xs"></i></button>'
            + `<span class="text-sm font-semibold capitalize text-prussian-blue">${monthFmt.format(view)}</span>`
            + '<button type="button" data-dp-next class="flex h-7 w-7 cursor-pointer items-center justify-center rounded-full text-prussian-blue/60 transition hover:bg-prussian-blue/5"><i class="fa-solid fa-chevron-right fa-xs"></i></button>'
            + '</div><div class="mt-2 grid grid-cols-7 gap-1">';

        for (let i = 0; i < 7; i++) {
            html += `<span class="py-1 text-center text-[10px] font-bold uppercase text-prussian-blue/40">${weekdayFmt.format(new Date(2024, 0, i + 1)).slice(0, 2)}</span>`;
        }

        const offset = (new Date(view.getFullYear(), view.getMonth(), 1).getDay() + 6) % 7;
        html += '<span></span>'.repeat(offset);

        const days = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        for (let d = 1; d <= days; d++) {
            const date = new Date(view.getFullYear(), view.getMonth(), d);
            const key = dateKey(date);
            const disabled = date < min || date > max;
            const selected = input.value === key;
            const classes = selected
                ? 'bg-ruby-red font-bold text-white'
                : disabled
                    ? 'text-prussian-blue/25'
                    : 'cursor-pointer font-semibold text-prussian-blue transition hover:bg-ruby-red/10';
            html += `<button type="button" data-dp-day="${key}" ${disabled ? 'disabled' : ''} class="h-8 rounded-lg text-center text-sm ${classes}">${d}</button>`;
        }

        panel.innerHTML = html + '</div>';

        panel.querySelector('[data-dp-prev]').addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() - 1, 1); render(); });
        panel.querySelector('[data-dp-next]').addEventListener('click', () => { view = new Date(view.getFullYear(), view.getMonth() + 1, 1); render(); });
        panel.querySelectorAll('[data-dp-day]').forEach((day) => day.addEventListener('click', () => {
            input.value = day.dataset.dpDay;
            label.textContent = dayFmt.format(new Date(day.dataset.dpDay + 'T00:00:00'));
            label.classList.remove('text-prussian-blue/40');
            panel.classList.add('hidden');
            input.dispatchEvent(new Event('change', { bubbles: true }));
            if (wrap.dataset.submit) input.form?.requestSubmit();
        }));
    };

    toggle.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            if (isFloat) floatPanel(toggle, panel, 288);
            render();
        }
    });

    document.addEventListener('click', (event) => {
        if (!wrap.contains(event.target) && !panel.contains(event.target)) panel.classList.add('hidden');
    });
});

document.querySelectorAll('[data-select]').forEach((wrap) => {
    const input = wrap.querySelector('input[type="hidden"]');
    const toggle = wrap.querySelector('[data-select-toggle]');
    const label = wrap.querySelector('[data-select-label]');
    const panel = wrap.querySelector('[data-select-panel]');
    if (!input || !toggle || !label || !panel) return;

    const isFloat = panel.dataset.float !== undefined;
    if (isFloat) document.body.appendChild(panel);

    toggle.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden') && isFloat) floatPanel(toggle, panel, 224);
    });

    panel.querySelectorAll('[data-select-option]').forEach((option) => {
        option.addEventListener('click', () => {
            input.value = option.dataset.value;
            label.textContent = option.textContent.trim();
            panel.querySelectorAll('[data-select-option]').forEach((item) => {
                item.classList.toggle('bg-prussian-blue/5', item === option);
            });
            panel.classList.add('hidden');
        });
    });

    document.addEventListener('click', (event) => {
        if (!wrap.contains(event.target) && !panel.contains(event.target)) panel.classList.add('hidden');
    });
});

document.addEventListener('sm:results-updated', () => {
    initStudioMap();
    initLoadMore();
    initReveals();
    document.querySelectorAll('[data-carousel]').forEach(initCarousel);
});

document.querySelectorAll('[data-photo-input]').forEach((input) => {
    const scope = input.closest('form') || document;
    const preview = scope.querySelector('[data-photo-preview]');
    const count = input.closest('label')?.querySelector('[data-file-count]');
    const submits = scope.querySelectorAll('button[type="submit"]');
    const selectedLabel = input.dataset.selectedLabel || '';
    if (!preview || !count) return;

    const MAX_DIM = 1800;
    let files = [];

    const shrink = (file) => new Promise((resolve) => {
        if (!file.type.startsWith('image/') || file.size < 700 * 1024) return resolve(file);
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            const scale = Math.min(1, MAX_DIM / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                URL.revokeObjectURL(url);
                resolve(blob && blob.size < file.size
                    ? new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' })
                    : file);
            }, 'image/jpeg', 0.85);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });

    const syncInput = () => {
        const transfer = new DataTransfer();
        files.forEach((file) => transfer.items.add(file));
        try { input.files = transfer.files; } catch (error) {}
    };

    const controlButton = (icon, onClick) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-white/90 text-[10px] text-prussian-blue shadow transition hover:bg-white';
        button.innerHTML = `<i class="fa-solid ${icon}"></i>`;
        button.addEventListener('click', onClick);
        return button;
    };

    const render = () => {
        preview.innerHTML = '';
        files.forEach((file, index) => {
            const wrap = document.createElement('div');
            wrap.className = 'relative';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'aspect-[4/3] w-full rounded-xl object-cover';
            wrap.appendChild(img);

            const controls = document.createElement('div');
            controls.className = 'absolute inset-x-1 top-1 flex items-center justify-between gap-1';
            const left = controlButton('fa-chevron-left', () => {
                if (index === 0) return;
                [files[index - 1], files[index]] = [files[index], files[index - 1]];
                syncInput();
                render();
            });
            const remove = controlButton('fa-trash', () => {
                files.splice(index, 1);
                syncInput();
                render();
            });
            remove.classList.remove('text-prussian-blue');
            remove.classList.add('text-ruby-red');
            const right = controlButton('fa-chevron-right', () => {
                if (index === files.length - 1) return;
                [files[index + 1], files[index]] = [files[index], files[index + 1]];
                syncInput();
                render();
            });
            controls.append(left, remove, right);
            wrap.appendChild(controls);
            preview.appendChild(wrap);
        });
        preview.classList.toggle('hidden', files.length === 0);
        preview.classList.toggle('grid', files.length > 0);
        count.textContent = files.length ? files.length + ' ' + selectedLabel : '';
    };

    input.addEventListener('change', async () => {
        if (!input.files.length) return;
        submits.forEach((button) => button.disabled = true);
        count.textContent = '…';

        const shrunk = await Promise.all([...input.files].map(shrink));
        files = files.concat(shrunk);
        syncInput();
        render();
        submits.forEach((button) => button.disabled = false);
    });
});
