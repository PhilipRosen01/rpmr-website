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
