/**
 * Kevyet animaatiot ilman ulkoisia kirjastoja.
 * - [data-reveal]: ilmestyy näkyviin vieritettäessä
 * - [data-count]: numerolaskuri
 * - [data-progress]: lukemisen edistymispalkki
 * - header[data-header]: kutistuu vieritettäessä
 * - [data-menu-toggle]: mobiilivalikko
 */
(function () {
"use strict";


const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const root = document.documentElement;

// --- Reveal -------------------------------------------------------------
const revealEls = document.querySelectorAll("[data-reveal]");
if ("IntersectionObserver" in window && !reduceMotion) {
  root.classList.add("reveal-ready");
  const io = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        entry.target.classList.add("is-visible");
        io.unobserve(entry.target);
      }
    },
    { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
  );
  revealEls.forEach((el) => io.observe(el));
}

// --- Counters -----------------------------------------------------------
const counters = document.querySelectorAll("[data-count]");
const runCounter = (el) => {
  const target = Number(el.dataset.count);
  if (!Number.isFinite(target)) return;
  if (reduceMotion) {
    el.textContent = String(target);
    return;
  }
  const start = performance.now();
  const duration = 1400;
  const tick = (now) => {
    const p = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = String(Math.round(target * eased));
    if (p < 1) requestAnimationFrame(tick);
  };
  requestAnimationFrame(tick);
};
if ("IntersectionObserver" in window) {
  const cio = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        runCounter(entry.target);
        cio.unobserve(entry.target);
      }
    },
    { threshold: 0.6 },
  );
  counters.forEach((el) => cio.observe(el));
}

// --- Header + reading progress -----------------------------------------
const header = document.querySelector("[data-header]");
const progress = document.querySelector("[data-progress]");
let ticking = false;
const onScroll = () => {
  const y = window.scrollY;
  header?.classList.toggle("is-scrolled", y > 24);
  if (progress) {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    progress.style.setProperty("--progress", String(max > 0 ? Math.min(y / max, 1) : 0));
  }
  ticking = false;
};
window.addEventListener(
  "scroll",
  () => {
    if (!ticking) {
      requestAnimationFrame(onScroll);
      ticking = true;
    }
  },
  { passive: true },
);
onScroll();

// --- Mobile menu --------------------------------------------------------
const toggle = document.querySelector("[data-menu-toggle]");
const menu = document.getElementById("mobile-menu");
if (toggle && menu) {
  const setOpen = (open) => {
    toggle.setAttribute("aria-expanded", String(open));
    menu.hidden = !open;
    document.body.classList.toggle("overflow-hidden", open);
  };
  toggle.addEventListener("click", () => setOpen(toggle.getAttribute("aria-expanded") !== "true"));
  menu.addEventListener("click", (e) => {
    if ((e.target).closest("a")) setOpen(false);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
      setOpen(false);
      toggle.focus();
    }
  });
  window.matchMedia("(min-width: 1024px)").addEventListener("change", (e) => e.matches && setOpen(false));
}
})();
