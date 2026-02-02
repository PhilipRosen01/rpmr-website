(() => {
  const nav = document.getElementById("rpmr-navlinks");
  const burger = document.getElementById("rpmr-burger");
  const menu = document.getElementById("rpmr-mobile-menu");

  if (!nav || !burger || !menu) return;

  const isMobile = () => window.matchMedia("(max-width: 760px)").matches;

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
