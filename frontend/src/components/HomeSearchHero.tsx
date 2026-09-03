"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

/**
 * Big call-to-action on the home page that leads to the dedicated
 * /search page. Rotating hint suggests what users can search for.
 */
const HINTS = ["ابحث عن شاعر: المتنبي", "ابحث عن قصيدة: قفا نبك", "ابحث عن بيت: قدر أهل العزم", "ابحث عن كلمة: الحب"];

export default function HomeSearchHero() {
  const [hintIdx, setHintIdx] = useState(0);
  const [isMac, setIsMac] = useState(false);

  useEffect(() => {
    setIsMac(typeof navigator !== "undefined" && /Mac|iPhone|iPad/.test(navigator.platform));
    const t = setInterval(() => setHintIdx((i) => (i + 1) % HINTS.length), 3500);
    return () => clearInterval(t);
  }, []);

  return (
    <div className="mb-8">
      <Link
        href="/search"
        className="group w-full flex items-center gap-3 rounded-full bg-parchment-elev border-2 border-border hover:border-[color:var(--gold)] focus-visible:border-[color:var(--gold)] focus-visible:ring-4 focus-visible:ring-gold-soft ps-5 pe-2 py-3 md:py-4 transition text-start shadow-sm hover:shadow-md no-underline"
        aria-label="فتح صفحة البحث"
      >
        <SearchGlyph />
        <span key={hintIdx} className="flex-1 text-ink-muted group-hover:text-ink transition text-base md:text-lg animate-[hint-in_.4s_ease]"
              style={{ fontFamily: "var(--font-amiri)" }}>
          {HINTS[hintIdx]}
        </span>
        <kbd className="font-mono text-[10px] md:text-xs border border-border rounded-md px-2 py-1 text-ink-dim bg-parchment select-none hidden sm:inline">
          {isMac ? "⌘K" : "Ctrl K"}
        </kbd>
      </Link>
      <p className="mt-2 text-center text-[11px] text-ink-dim">
        ابحث في أكثر من ٧٤٠ ألف بيت شعري — فيها الحكمة، الغزل، المدح، الرثاء، وأكثر.
      </p>

      <style>{`
        @keyframes hint-in {
          from { opacity: 0; transform: translateY(4px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </div>
  );
}

function SearchGlyph() {
  return (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-[color:var(--gold)] flex-shrink-0" aria-hidden>
      <circle cx="11" cy="11" r="7" />
      <line x1="16" y1="16" x2="21" y2="21" />
    </svg>
  );
}
