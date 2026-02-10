(() => {
  const nav = document.getElementById("rpmr-navlinks");
  const burger = document.getElementById("rpmr-burger");
  const menu = document.getElementById("rpmr-mobile-menu");

  if (!nav || !burger || !menu) return;

  const isMobile = () => window.matchMedia("(max-width: 898px)").matches;

  const setOpen = (open) => {
    nav.classList.toggle("rpmr-menu-open", open);
    burger.setAttribute("aria-expanded", open ? "true" : "false");
  };
  const isOpen = () => nav.classList.contains("rpmr-menu-open");

  // Ensure consistent initial state.
  setOpen(false);

  burger.addEventListener("click", () => {
    if (!isMobile()) return;
    setOpen(!isOpen());
  });

  // Close after choosing a menu item.
  menu.addEventListener("click", (e) => {
    const link = e.target.closest("a");
    if (!link) return;
    if (!isMobile()) return;
    setOpen(false);
  });

  // Close if you tap outside.
  document.addEventListener("click", (e) => {
    if (!isMobile()) return;
    if (!isOpen()) return;
    if (nav.contains(e.target)) return;
    setOpen(false);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    if (!isMobile()) return;
    setOpen(false);
  });

  window.addEventListener("resize", () => {
    if (!isMobile()) {
      setOpen(false);
      return;
    }
  });
})();

// Insert literal separators between mobile menu links: Services | Projects
(() => {
  const buildSeparators = () => {
    const menus = document.querySelectorAll('.rpmr-mobile-menu');
    if (!menus.length) return;

    const shouldHaveSeparators = window.matchMedia(
      '(max-width: 898px) and (min-width: 391px)'
    ).matches;

    menus.forEach((menu) => {
      // Idempotent: remove previously inserted separators
      menu.querySelectorAll('.rpmr-menu-sep').forEach((sep) => sep.remove());

      if (!shouldHaveSeparators) return;

      // Only place separators between direct child links
      const links = Array.from(menu.children).filter(
        (el) => el.nodeType === 1 && el.matches('a')
      );
      if (links.length < 2) return;

      links.forEach((link, index) => {
        if (index === links.length - 1) return;
        const sep = document.createElement('span');
        sep.className = 'rpmr-menu-sep';
        sep.setAttribute('aria-hidden', 'true');
        sep.textContent = '|';
        link.insertAdjacentElement('afterend', sep);
      });
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildSeparators);
  } else {
    buildSeparators();
  }

  // Rebuild on resize in case markup changes across breakpoints.
  let resizeTimer = null;
  window.addEventListener('resize', () => {
    if (resizeTimer) window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(buildSeparators, 120);
  });
})();

// Touch-hover simulation: adds `rpmr-touch-hover` to elements on touch so mobile
// users see the same vibrant visuals as desktop hover. Respects prefers-reduced-motion.
(() => {
  const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReducedMotion()) return;
  if (!('ontouchstart' in window)) return;

  const interactiveSelector = '.rpmr-btn, .rpmr-inline, .rpmr-chip, .rpmr-metric, .rpmr-card, .rpmr-project, .rpmr-stack-item, .rpmr-footlinks a, .rpmr-pill, .rpmr-side-cta a, .rpmr-form input, .rpmr-form select, .rpmr-form textarea';

  let activeElement = null;
  let activeRow = null;
  let clearTimer = null;

  const clearHover = () => {
    if (activeElement) activeElement.classList.remove('rpmr-touch-hover');
    if (activeRow) activeRow.classList.remove('rpmr-touch-hover');
    activeElement = null;
    activeRow = null;
    if (clearTimer) { clearTimeout(clearTimer); clearTimer = null; }
  };

  const onTouchStart = (event) => {
    const target = event.target.closest(interactiveSelector);
    if (!target) return;

    // Apply class to the touched element
    activeElement = target;
    activeElement.classList.add('rpmr-touch-hover');

    // If it's inside a project-row, also add to the row to mimic group hover
    const row = target.closest('.rpmr-project-row');
    if (row) { activeRow = row; activeRow.classList.add('rpmr-touch-hover'); }

    if (clearTimer) { clearTimeout(clearTimer); clearTimer = null; }
    // Keep the visual for a short duration; remove shortly after touchend
    clearTimer = setTimeout(clearHover, 900);
  };

  const onTouchEnd = () => {
    if (clearTimer) { clearTimeout(clearTimer); }
    clearTimer = setTimeout(clearHover, 140);
  };

  document.addEventListener('touchstart', onTouchStart, { passive: true });
  document.addEventListener('touchend', onTouchEnd, { passive: true });
  document.addEventListener('touchcancel', clearHover, { passive: true });
  let scrollTicking = false;
  window.addEventListener(
    'scroll',
    () => {
      if (scrollTicking) return;
      scrollTicking = true;
      window.requestAnimationFrame(() => {
        clearHover();
        scrollTicking = false;
      });
    },
    { passive: true }
  );
})();

// Smooth-scroll for in-page anchors (more reliable than CSS alone)
(() => {
  const prefersReducedMotion = () =>
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const header = document.querySelector(".rpmr-header");
  const getHeaderOffset = () =>
    header ? Math.ceil(header.getBoundingClientRect().height) + 8 : 0;

  const scrollToTarget = (target) => {
    const behavior = prefersReducedMotion() ? "auto" : "smooth";

    // Prefer scrollIntoView (broad support), then correct for sticky header.
    try {
      target.scrollIntoView({ behavior, block: "start" });
    } catch {
      target.scrollIntoView(true);
    }

    const offset = getHeaderOffset();
    if (!offset) return;

    // Apply header offset after the browser performs the initial scroll.
    setTimeout(() => {
      try {
        window.scrollBy({ top: -offset, left: 0, behavior: "auto" });
      } catch {
        window.scrollBy(0, -offset);
      }
    }, 0);
  };

  document.addEventListener("click", (e) => {
    const link = e.target.closest('a[href^="#"]');
    if (!link) return;

    const href = link.getAttribute("href");
    if (!href || href === "#") return;

    const id = href.slice(1);
    const target = document.getElementById(id);
    if (!target) return;

    e.preventDefault();

    // Update the URL hash without breaking older browsers (or file:// origins).
    try {
      history.pushState(null, "", href);
    } catch {
      window.location.hash = href;
    }

    scrollToTarget(target);
  });
})();

// Creative animated cursor effect
(() => {
  const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Only render the cursor on real desktops/laptops.
  // On touch devices this can cause unnecessary paints and jank.
  if (prefersReducedMotion()) return;
  if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;

  // Create the custom cursor element
  const cursor = document.createElement('div');
  cursor.id = 'rpmr-cursor';
  document.body.appendChild(cursor);

  // Style the cursor via JS for flexibility
  Object.assign(cursor.style, {
    position: 'fixed',
    top: '0',
    left: '0',
    width: '36px',
    height: '36px',
    borderRadius: '50%',
    pointerEvents: 'none',
    zIndex: '9999',
    background: 'radial-gradient(circle at 30% 30%, #7c3aed88 60%, #22d3ee33 100%)',
    boxShadow: '0 0 24px 8px #7c3aed55, 0 0 0 2px #22d3ee55',
    mixBlendMode: 'lighten',
    opacity: '0.7',
    transition: 'transform 0.18s cubic-bezier(.4,2,.6,1), opacity 0.18s',
    transform: 'translate(-18px, -18px) scale(1)', // Center the cursor
    willChange: 'transform, opacity',
  });

  let lastX = window.innerWidth / 2;
  let lastY = window.innerHeight / 2;
  let ticking = false;
  let lastMove = Date.now();

  function animateCursor(x, y) {
    cursor.style.transform = `translate(${x - 18}px, ${y - 18}px) scale(1.08)`;
    cursor.style.opacity = '1';
    cursor.style.filter = `blur(${Math.min(8, Math.abs(x - lastX) / 8 + Math.abs(y - lastY) / 8)}px)`;
    lastX = x;
    lastY = y;
    lastMove = Date.now();
  }

  document.addEventListener('mousemove', (e) => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        animateCursor(e.clientX, e.clientY);
        ticking = false;
      });
      ticking = true;
    }
  });

  // Animate a pulse when mouse is still
  setInterval(() => {
    if (Date.now() - lastMove > 400) {
      cursor.style.transform = `translate(${lastX - 18}px, ${lastY - 18}px) scale(1.18)`;
      cursor.style.opacity = '0.5';
      cursor.style.filter = 'blur(10px)';
    }
  }, 120);

  // Hide the default cursor
  document.body.style.cursor = 'none';
})();
