"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { api, type SearchResult } from "@/lib/api";
import PoemCard from "@/components/PoemCard";
import PoetCard from "@/components/PoetCard";

/**
 * Full-page live search. Debounced fetch, deep-linkable URL, tab filters.
 * Autofocus + keyboard: `/` re-focuses input; Esc clears.
 */

type Tab = "all" | "poem" | "poet" | "verse";
const TABS: { key: Tab; label: string }[] = [
  { key: "all",   label: "الكل" },
  { key: "poem",  label: "القصائد" },
  { key: "poet",  label: "الشعراء" },
  { key: "verse", label: "الأبيات" },
];
const HINTS = ["المتنبي", "قفا نبك", "الحب", "قدر أهل العزم", "أبو الطيب"];
const DEBOUNCE_MS = 260;

export default function SearchClient() {
  const router = useRouter();
  const sp = useSearchParams();
  const initialQ = sp.get("q") ?? "";
  const initialTab = (sp.get("type") as Tab) || "all";

  const [q, setQ] = useState(initialQ);
  const [tab, setTab] = useState<Tab>(initialTab);
  const [res, setRes] = useState<SearchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [errored, setErrored] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const abortRef = useRef<AbortController | null>(null);

  // URL <-> state sync: rewrite the URL after each debounced search so users
  // can bookmark / share. We use replace, not push, so the back button skips
  // every keystroke.
  const syncUrl = useCallback((nextQ: string, nextTab: Tab) => {
    const params = new URLSearchParams();
    if (nextQ.trim()) params.set("q", nextQ.trim());
    if (nextTab !== "all") params.set("type", nextTab);
    const qs = params.toString();
    router.replace(qs ? `/search?${qs}` : `/search`, { scroll: false });
  }, [router]);

  // Debounced search.
  useEffect(() => {
    const trimmed = q.trim();
    if (trimmed.length < 2) {
      setRes(null); setLoading(false); setErrored(false);
      if (trimmed !== initialQ) syncUrl(trimmed, tab);
      return;
    }
    setLoading(true);
    setErrored(false);
    const handle = setTimeout(async () => {
      abortRef.current?.abort();
      const ctl = new AbortController();
      abortRef.current = ctl;
      try {
        const r = await api.search(trimmed, tab);
        if (!ctl.signal.aborted) { setRes(r); syncUrl(trimmed, tab); }
      } catch {
        if (!ctl.signal.aborted) { setRes(null); setErrored(true); }
      } finally {
        if (!ctl.signal.aborted) setLoading(false);
      }
    }, DEBOUNCE_MS);
    return () => clearTimeout(handle);
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [q, tab]);

  // Autofocus on mount + keyboard shortcuts (/, Esc).
  useEffect(() => {
    inputRef.current?.focus();
    inputRef.current?.select();
    function onKey(e: KeyboardEvent) {
      const t = e.target as HTMLElement | null;
      const inField = t && /input|textarea/i.test(t.tagName);
      if (e.key === "/" && !inField) { e.preventDefault(); inputRef.current?.focus(); return; }
      if (e.key === "Escape" && document.activeElement === inputRef.current) {
        setQ(""); syncUrl("", tab);
      }
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [tab, syncUrl]);

  const counts = useMemo(() => ({
    all:   (res?.data.poems.length ?? 0) + (res?.data.poets.length ?? 0) + (res?.data.verses.length ?? 0),
    poem:  res?.data.poems.length ?? 0,
    poet:  res?.data.poets.length ?? 0,
    verse: res?.data.verses.length ?? 0,
  }), [res]);

  const total = counts.all;
  const trimmed = q.trim();
  const showEmpty = trimmed.length < 2;
  const showNoResults = !showEmpty && !loading && !errored && total === 0;

  return (
    <main className="max-w-5xl mx-auto px-4 py-6 md:py-10">
      <div className="text-center mb-4">
        <h1 className="text-3xl md:text-4xl text-ink" style={{ fontFamily: "var(--font-reem)" }}>البحث في الشعر</h1>
        <p className="mt-2 text-sm text-ink-muted">شاعرًا، قصيدةً، أو بيتًا واحدًا</p>
      </div>

      {/* Big input */}
      <div className={`relative rounded-2xl bg-parchment-elev border-2 transition shadow-sm
                       ${loading ? "border-[color:var(--gold)]" : "border-border focus-within:border-[color:var(--gold)] focus-within:ring-4 focus-within:ring-gold-soft"}`}>
        <SearchGlyph className="absolute top-1/2 -translate-y-1/2 start-4 text-[color:var(--gold)]" />
        <input
          ref={inputRef}
          type="search"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="اكتب اسم شاعر، عنوان قصيدة، أو بيتًا…"
          className="w-full bg-transparent text-ink placeholder-ink-dim text-lg md:text-xl ps-14 pe-14 py-4 md:py-5 focus:outline-none"
          style={{ fontFamily: "var(--font-amiri)" }}
          autoComplete="off"
          spellCheck={false}
          aria-label="بحث"
        />
        {q.length > 0 && (
          <button
            type="button"
            onClick={() => { setQ(""); inputRef.current?.focus(); }}
            className="absolute top-1/2 -translate-y-1/2 end-3 w-8 h-8 grid place-items-center rounded-full text-ink-dim hover:bg-parchment-soft hover:text-ink"
            aria-label="مسح"
          >✕</button>
        )}
      </div>

      {/* Tabs */}
      <div className="mt-4 flex items-center flex-wrap gap-1.5 justify-center">
        {TABS.map(t => {
          const c = counts[t.key];
          const active = tab === t.key;
          return (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={`px-3.5 py-1.5 rounded-full text-[13px] font-medium transition inline-flex items-center gap-2
                ${active
                  ? "bg-[color:var(--wine)] text-white shadow-sm"
                  : "bg-parchment-elev border border-border text-ink-muted hover:border-border-strong hover:text-ink"}`}
            >
              {t.label}
              {res && !showEmpty && (
                <span className={`text-[11px] rounded-full px-1.5 min-w-[18px] text-center
                  ${active ? "bg-white/20" : "bg-parchment-soft text-ink-dim"}`}>
                  {c}
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* States */}
      <div className="mt-8">
        {showEmpty && <EmptyState onPick={(v) => setQ(v)} />}
        {!showEmpty && loading && !res && <LoadingState />}
        {errored && <div className="text-center py-16 text-red-500">تعذّر البحث. حاول مجددًا.</div>}
        {showNoResults && (
          <div className="text-center py-20 text-ink-dim">
            <div className="text-5xl mb-3 text-[color:var(--gold)] opacity-40">﴿</div>
            <p>لا نتائج لـ <span className="text-ink">«{trimmed}»</span></p>
            <p className="mt-1 text-[12px]">جرّب كلمة أقصر أو تهجئة مختلفة.</p>
          </div>
        )}

        {res && total > 0 && (
          <Results res={res} tab={tab} query={trimmed} />
        )}
      </div>
    </main>
  );
}

// ---------------- pieces ----------------

function EmptyState({ onPick }: { onPick: (v: string) => void }) {
  return (
    <div className="text-center py-14">
      <p className="text-sm text-ink-muted mb-4">أمثلة على ما يمكنك البحث عنه:</p>
      <div className="flex flex-wrap justify-center gap-2">
        {HINTS.map(h => (
          <button
            key={h}
            onClick={() => onPick(h)}
            className="rounded-full bg-parchment-elev border border-border hover:border-[color:var(--gold)] px-4 py-1.5 text-sm text-ink transition"
            style={{ fontFamily: "var(--font-amiri)" }}
          >
            {h}
          </button>
        ))}
      </div>
      <div className="mt-8 flex justify-center gap-3 text-[11px] text-ink-dim">
        <Kbd>/</Kbd><span>للتركيز على البحث</span>
        <Kbd>Esc</Kbd><span>لمسح النص</span>
      </div>
    </div>
  );
}

function LoadingState() {
  return (
    <div className="grid gap-3">
      {[...Array(4)].map((_, i) => (
        <div key={i} className="h-16 rounded-lg bg-parchment-soft animate-pulse" />
      ))}
    </div>
  );
}

function Results({ res, tab, query }: { res: SearchResult; tab: Tab; query: string }) {
  const showPoems  = (tab === "all" || tab === "poem")  && res.data.poems.length  > 0;
  const showPoets  = (tab === "all" || tab === "poet")  && res.data.poets.length  > 0;
  const showVerses = (tab === "all" || tab === "verse") && res.data.verses.length > 0;

  return (
    <div className="space-y-8">
      {showPoems && (
        <section>
          <SectionHead glyph="﴿" title="قصائد" count={res.data.poems.length} />
          <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {res.data.poems.map(p => <li key={p.uuid}><PoemCard poem={p} /></li>)}
          </ul>
        </section>
      )}

      {showPoets && (
        <section>
          <SectionHead glyph="◆" title="شعراء" count={res.data.poets.length} />
          <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {res.data.poets.map(p => <li key={p.uuid}><PoetCard poet={p} /></li>)}
          </ul>
        </section>
      )}

      {showVerses && (
        <section>
          <SectionHead glyph="❦" title="أبيات" count={res.data.verses.length} />
          <ul className="space-y-2">
            {res.data.verses.map(v => (
              <li key={v.uuid} className="bg-parchment-elev border border-border rounded-lg overflow-hidden hover:border-border-strong transition">
                <Link href={v.poem ? `/poems/${v.poem.slug}` : "#"} className="block px-4 pt-3 pb-2 no-underline">
                  <div
                    className="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-baseline gap-4"
                    style={{ fontFamily: "var(--font-amiri)", fontSize: "18px", lineHeight: 1.9 }}
                  >
                    <span className="text-end md:pe-1 text-ink">{highlight(v.hemistich_a, query)}</span>
                    {v.hemistich_b && <span className="text-gold text-xs justify-self-center">◆</span>}
                    {v.hemistich_b && <span className="text-start md:ps-1 text-ink/85">{highlight(v.hemistich_b, query)}</span>}
                  </div>
                </Link>
                {v.poem && (
                  <div className="px-4 pb-2.5 pt-1 border-t border-border/50 flex flex-wrap gap-x-3 gap-y-1 items-center text-[11px] text-ink-muted">
                    <span className="text-gold">—</span>
                    {v.poem.poet && (
                      <Link href={`/poets/${v.poem.poet.slug}`}
                            className="inline-flex items-center gap-1 rounded-full bg-wine-soft text-[color:var(--wine)] px-2.5 py-0.5 font-semibold hover:brightness-95 no-underline">
                        ◆ {v.poem.poet.name_ar}
                      </Link>
                    )}
                    <Link href={`/poems/${v.poem.slug}`} className="text-ink-muted hover:text-wine no-underline">
                      من «<span className="text-ink">{v.poem.title_ar}</span>»
                    </Link>
                    {v.poem.era && (
                      <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5">
                        {v.poem.era.name_ar}
                      </span>
                    )}
                  </div>
                )}
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}

function SectionHead({ glyph, title, count }: { glyph: string; title: string; count: number }) {
  return (
    <div className="flex items-center gap-3 mb-3">
      <span className="text-[color:var(--gold)] text-lg">{glyph}</span>
      <h2 className="text-lg text-ink font-semibold" style={{ fontFamily: "var(--font-reem)" }}>{title}</h2>
      <span className="text-[11px] text-ink-dim">({count})</span>
      <span className="flex-1 h-px bg-border" />
    </div>
  );
}

function Kbd({ children }: { children: React.ReactNode }) {
  return <kbd className="font-mono border border-border rounded px-1.5 py-px text-[10px] text-ink-muted bg-parchment-elev">{children}</kbd>;
}

function SearchGlyph({ className }: { className?: string }) {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden>
      <circle cx="11" cy="11" r="7" />
      <line x1="16" y1="16" x2="21" y2="21" />
    </svg>
  );
}

/**
 * Highlight query occurrences in a verse.  Arabic text may include the
 * shadda/hamza forms the user didn't type — the API's own normalisation
 * handles matching; here we just underline literal substring matches.
 */
function highlight(text: string, q: string): React.ReactNode {
  if (!q || q.length < 2) return text;
  const idx = text.indexOf(q);
  if (idx === -1) return text;
  return (
    <>
      {text.slice(0, idx)}
      <mark className="bg-gold-soft text-[color:var(--wine)] rounded px-0.5" style={{ background: "var(--gold-soft, #f5e6c4)" }}>
        {text.slice(idx, idx + q.length)}
      </mark>
      {text.slice(idx + q.length)}
    </>
  );
}
