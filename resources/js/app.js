// Scroll-reveal: all sections, the footer and [data-reveal] blocks fade/slide
// in when they enter the viewport (above-the-fold reveals right on load).
// Progressive enhancement: without JS no .reveal class is added, so content
// stays visible.
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

// Shrink the header padding once the page is scrolled past 20px.
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

// "Load more" grids: [data-loadmore] wraps a [data-loadmore-grid] and a
// [data-loadmore-btn]. Shows data-loadmore-initial items, reveals
// data-loadmore-step more per click, hides the button when all are shown.
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

// Mobile menu: toggle the fullscreen [data-mobile-menu] via [data-menu-toggle].
// One icon is swapped between bars/xmark (two stacked icons fight with the
// async-loaded Font Awesome CSS), and body scroll is locked while open.
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

// Mobile search modal: open via [data-search-open], close via [data-search-close]
// or Escape. Locks body scroll while open.
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

// Close any open <details data-dropdown> when clicking outside of it.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((dropdown) => {
        if (!dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });
});

// Close open dropdowns on Escape.
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('details[data-dropdown][open]').forEach((dropdown) => {
            dropdown.removeAttribute('open');
        });
    }
});

// Horizontal sliders: [data-slider] wrapping a [data-slider-track] plus
// optional [data-slider-prev] / [data-slider-next] buttons. Each arrow
// scrolls exactly one visible page; buttons disable at the track ends.
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
