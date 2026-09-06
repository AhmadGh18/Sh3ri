"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import UserMenu from "./UserMenu";

const NAV = [
  { href: "/",          label: "الرئيسية" },
  { href: "/poems",     label: "القصائد" },
  { href: "/poets",     label: "الشعراء" },
  { href: "/eras",      label: "العصور" },
  { href: "/countries", label: "البلدان" },
  { href: "/community", label: "المجتمع" },
  { href: "/plans",     label: "الخطط" },
];

type Theme = "auto" | "light" | "dark";

export default function Header() {
  const pathname = usePathname();
  const router = useRouter();
  const [theme, setTheme] = useState<Theme>("auto");
  const [isMac, setIsMac] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  useEffect(() => {
    try {
      const saved = (localStorage.getItem("sh3ri.theme") as Theme) || "auto";
      applyTheme(saved);
      setTheme(saved);
    } catch { /* SSR / private mode */ }
    setIsMac(typeof navigator !== "undefined" && /Mac|iPhone|iPad/.test(navigator.platform));
  }, []);

  useEffect(() => { setMenuOpen(false); }, [pathname]);

  useEffect(() => {
    if (!menuOpen) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = prev; };
  }, [menuOpen]);

  function applyTheme(t: Theme) {
    if (t === "auto") document.documentElement.removeAttribute("data-theme");
    else document.documentElement.setAttribute("data-theme", t);
  }

  function cycleTheme() {
    const next: Theme = theme === "auto" ? "light" : theme === "light" ? "dark" : "auto";
    setTheme(next);
    applyTheme(next);
    try { localStorage.setItem("sh3ri.theme", next); } catch {}
  }

  useEffect(() => {
    function handler(e: KeyboardEvent) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        router.push("/search");
      }
      if (e.key === "Escape" && menuOpen) setMenuOpen(false);
    }
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [router, menuOpen]);

  const isActive = (href: string) => href === "/" ? pathname === "/" : pathname.startsWith(href);

  return (
    <>
      <header className="sticky top-0 z-30 backdrop-blur bg-[color:var(--parchment)]/85 border-b border-border">
        <div className="max-w-6xl mx-auto px-3 md:px-4 py-2.5 flex items-center gap-2 md:gap-3">
          <Link href="/" className="flex items-center gap-2 no-underline shrink-0">
            <Image src="/logo.png" alt="شِعْري" width={40} height={40} priority
                   className="h-7 w-7 md:h-9 md:w-9 object-contain" />
            <span className="text-lg md:text-xl font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>
              شِعْري
            </span>
          </Link>

          {/* Desktop nav — only visible from lg+ */}
          <nav className="hidden lg:flex gap-0.5">
            {NAV.map(n => (
              <Link
                key={n.href}
                href={n.href}
                className={`px-2.5 py-1 rounded-full text-[13px] font-medium transition
                  ${isActive(n.href)
                    ? "bg-wine-soft text-[color:var(--wine)]"
                    : "text-ink-muted hover:bg-parchment-soft hover:text-ink"}`}
              >
                {n.label}
              </Link>
            ))}
          </nav>

          {/* Search — icon-only circle on mobile, full pill on md+ */}
          <Link
            href="/search"
            className={`ms-auto inline-flex items-center justify-center border rounded-full transition no-underline
              w-9 h-9
              md:w-auto md:h-auto md:justify-start md:ps-3 md:pe-2 md:py-1.5 md:min-w-[220px] lg:min-w-[260px]
              ${isActive("/search")
                ? "bg-wine-soft border-[color:var(--wine)] text-[color:var(--wine)]"
                : "bg-parchment-elev border-border hover:border-border-strong text-ink-muted"}`}
            title="فتح صفحة البحث"
            aria-label="بحث"
          >
            <SearchGlyph />
            <span className="hidden md:inline flex-1 text-start text-[13px] ms-2">ابحث في الشعر…</span>
            <kbd className="hidden md:inline font-mono text-[10px] border border-border rounded px-1 py-px text-ink-dim bg-parchment select-none">
              {isMac ? "⌘K" : "Ctrl K"}
            </kbd>
          </Link>

          <button
            onClick={cycleTheme}
            className="w-9 h-9 rounded-full border border-border text-ink-muted hover:text-wine hover:border-border-strong grid place-items-center shrink-0"
            title={`المظهر: ${theme}`}
            aria-label="Toggle theme"
          >
            {theme === "dark" ? "☾" : theme === "light" ? "☀" : "◐"}
          </button>

          <div className="hidden md:block"><UserMenu /></div>

          <button
            onClick={() => setMenuOpen(true)}
            className="lg:hidden w-9 h-9 rounded-full border border-border text-ink-muted hover:text-ink hover:border-border-strong grid place-items-center shrink-0"
            aria-label="القائمة"
          >
            <BurgerGlyph />
          </button>
        </div>
      </header>

      {/* Mobile drawer — rendered OUTSIDE the header so backdrop-blur
          containing-block doesn't trap it. Uses left-0 explicitly
          (works the same in LTR and RTL). */}
      {menuOpen && (
        <div className="lg:hidden fixed inset-0 z-[100]" role="dialog" aria-modal="true">
          {/* Backdrop */}
          <div
            onClick={() => setMenuOpen(false)}
            className="absolute inset-0 bg-black/60"
          />
          {/* Drawer panel */}
          <div
            className="absolute top-0 left-0 h-full w-[85vw] max-w-[320px] bg-parchment-elev border-e border-border shadow-2xl overflow-y-auto flex flex-col"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-4 py-3 border-b border-border">
              <span className="font-bold text-ink text-lg" style={{ fontFamily: "var(--font-reem)" }}>القائمة</span>
              <button
                onClick={() => setMenuOpen(false)}
                aria-label="إغلاق"
                className="w-9 h-9 rounded-full grid place-items-center text-ink-muted hover:text-wine hover:bg-parchment-soft text-2xl leading-none"
              >×</button>
            </div>

            <nav className="p-2 flex-1">
              {NAV.map(n => (
                <Link
                  key={n.href}
                  href={n.href}
                  onClick={() => setMenuOpen(false)}
                  className={`block px-3 py-3 rounded-lg text-[16px] font-medium mb-0.5 transition
                    ${isActive(n.href)
                      ? "bg-wine-soft text-[color:var(--wine)]"
                      : "text-ink hover:bg-parchment-soft"}`}
                >
                  {n.label}
                </Link>
              ))}
            </nav>

            <div className="border-t border-border p-3 md:hidden">
              <UserMenu />
            </div>
          </div>
        </div>
      )}
    </>
  );
}

function SearchGlyph() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className="text-[color:var(--gold)] shrink-0" aria-hidden>
      <circle cx="11" cy="11" r="7" />
      <line x1="16" y1="16" x2="21" y2="21" />
    </svg>
  );
}

function BurgerGlyph() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden>
      <line x1="4" y1="7" x2="20" y2="7" />
      <line x1="4" y1="12" x2="20" y2="12" />
      <line x1="4" y1="17" x2="20" y2="17" />
    </svg>
  );
}
