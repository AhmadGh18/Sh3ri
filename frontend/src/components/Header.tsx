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

  useEffect(() => {
    try {
      const saved = (localStorage.getItem("sh3ri.theme") as Theme) || "auto";
      applyTheme(saved);
      setTheme(saved);
    } catch { /* SSR / private mode */ }
    setIsMac(typeof navigator !== "undefined" && /Mac|iPhone|iPad/.test(navigator.platform));
  }, []);

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

  // Cmd/Ctrl+K sends the user to the full-page search.
  useEffect(() => {
    function handler(e: KeyboardEvent) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        router.push("/search");
      }
    }
    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [router]);

  const isActive = (href: string) => href === "/" ? pathname === "/" : pathname.startsWith(href);

  return (
    <header className="sticky top-0 z-20 backdrop-blur bg-[color:var(--parchment)]/85 border-b border-border">
      <div className="max-w-6xl mx-auto px-4 py-2.5 flex items-center gap-3 flex-wrap">
        <Link href="/" className="flex items-center gap-2 no-underline">
          <Image src="/logo.png" alt="شِعْري" width={40} height={40} priority
                 className="h-8 w-8 md:h-9 md:w-9 object-contain" />
          <span className="text-xl font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>شِعْري</span>
        </Link>

        <nav className="flex gap-0.5 flex-wrap">
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

        {/* Search entry — navigates to the full-page search. */}
        <Link
          href="/search"
          className={`ms-auto inline-flex items-center gap-2 border rounded-full ps-3 pe-2 py-1.5 text-[13px] transition min-w-[200px] md:min-w-[280px] no-underline
            ${isActive("/search")
              ? "bg-wine-soft border-[color:var(--wine)] text-[color:var(--wine)]"
              : "bg-parchment-elev border-border hover:border-border-strong text-ink-muted"}`}
          title="فتح صفحة البحث (Ctrl+K)"
        >
          <SearchGlyph />
          <span className="flex-1 text-start">ابحث في الشعر…</span>
          <kbd className="font-mono text-[10px] border border-border rounded px-1 py-px text-ink-dim bg-parchment select-none">
            {isMac ? "⌘K" : "Ctrl K"}
          </kbd>
        </Link>

        <button
          onClick={cycleTheme}
          className="w-8 h-8 rounded-full border border-border text-ink-muted hover:text-wine hover:border-border-strong grid place-items-center"
          title={`المظهر: ${theme}`}
          aria-label="Toggle theme"
        >
          {theme === "dark" ? "☾" : theme === "light" ? "☀" : "◐"}
        </button>

        <UserMenu />
      </div>
    </header>
  );
}

function SearchGlyph() {
  return (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" className="text-[color:var(--gold)]" aria-hidden>
      <circle cx="11" cy="11" r="7" />
      <line x1="16" y1="16" x2="21" y2="21" />
    </svg>
  );
}
