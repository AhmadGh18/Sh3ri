"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useSession } from "@/lib/session";
import { useFavorites, favorites } from "@/lib/favorites";
import { apiClient } from "@/lib/apiClient";
import Breadcrumbs from "@/components/Breadcrumbs";
import SectionTitle from "@/components/SectionTitle";
import AuthModal from "@/components/AuthModal";

/**
 * Live list of everything the signed-in user has saved. Uses the same
 * client store as the heart buttons so unfavoriting from this page updates
 * the heart on any open poem tab immediately.
 */
export default function MyFavoritesPage() {
  const { token } = useSession();
  useFavorites(); // subscribe so unfavorite from another tab updates counts
  const [items, setItems] = useState<Item[] | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [askAuth, setAskAuth] = useState(false);

  useEffect(() => {
    if (!token) { setAskAuth(true); return; }
    let cancelled = false;
    (async () => {
      try {
        const res = await apiClient<{ data: Item[] }>("/me/favorites?per_page=50");
        if (!cancelled) setItems(res.data);
        // keep the client-side store in sync
        favorites.refresh();
      } catch (e) {
        if (!cancelled) setErr(e instanceof Error ? e.message : String(e));
      }
    })();
    return () => { cancelled = true; };
  }, [token]);

  if (!token) {
    return (
      <>
        <main className="max-w-4xl mx-auto px-4 py-16 text-center">
          <p className="mb-4 text-ink-muted">يجب تسجيل الدخول للوصول إلى المفضلة.</p>
          <button
            onClick={() => setAskAuth(true)}
            className="rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white px-5 py-2 text-sm font-medium"
          >دخول</button>
        </main>
        {askAuth && <AuthModal onClose={() => setAskAuth(false)} />}
      </>
    );
  }

  const poems  = items?.filter(i => i.type === "poem"  && i.poem)  ?? [];
  const verses = items?.filter(i => i.type === "verse" && i.verse) ?? [];

  return (
    <main className="max-w-5xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "❤ المفضلة" }]} />

      {err && (
        <div className="rounded-lg border border-[color:var(--wine)] bg-wine-soft text-[color:var(--wine)] px-4 py-3 text-sm">
          خطأ: {err}
        </div>
      )}

      {items === null && !err && (
        <div className="text-ink-dim text-center py-16">جاري التحميل…</div>
      )}

      {items && items.length === 0 && (
        <div className="text-center py-16 text-ink-muted">
          لم تحفظ شيئًا بعد.
          <div className="text-xs mt-2 text-ink-dim">اضغط ♡ بجانب أيّ قصيدة أو بيت لإضافته هنا.</div>
        </div>
      )}

      {poems.length > 0 && (
        <>
          <SectionTitle glyph="﴿" hint={`${poems.length}`}>قصائد محفوظة</SectionTitle>
          <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 mb-8">
            {poems.map((f) => (
              <li key={"p-" + f.poem!.uuid}>
                <Link
                  href={`/poems/${f.poem!.slug}`}
                  className="block rounded-lg border border-border bg-parchment-elev p-3 hover:-translate-y-0.5 hover:border-border-strong hover:shadow-md transition"
                >
                  <h3 className="text-[15px] font-bold text-ink mb-1 line-clamp-2" style={{ fontFamily: "var(--font-amiri)" }}>
                    {f.poem!.title_ar}
                  </h3>
                  <p className="text-xs text-ink-muted truncate">{f.poem!.poet?.name_ar ?? "—"}</p>
                </Link>
              </li>
            ))}
          </ul>
        </>
      )}

      {verses.length > 0 && (
        <>
          <SectionTitle glyph="❦" hint={`${verses.length}`}>أبيات محفوظة</SectionTitle>
          <ul className="space-y-2">
            {verses.map((f) => (
              <li key={"v-" + f.verse!.uuid}
                  className="bg-parchment-elev border border-border rounded-lg overflow-hidden hover:border-border-strong transition">
                <Link
                  href={f.verse!.poem ? `/poems/${f.verse!.poem.slug}#verse-${f.verse!.position}` : "#"}
                  className="block px-4 pt-3 pb-2 no-underline"
                >
                  <div
                    className="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-baseline gap-4"
                    style={{ fontFamily: "var(--font-amiri)", fontSize: "18px", lineHeight: 1.9 }}
                  >
                    <span className="text-end md:pe-1 text-ink">{f.verse!.hemistich_a}</span>
                    {f.verse!.hemistich_b && <span className="text-gold text-xs justify-self-center">◆</span>}
                    {f.verse!.hemistich_b && <span className="text-start md:ps-1 text-ink/85">{f.verse!.hemistich_b}</span>}
                  </div>
                </Link>
                {f.verse!.poem && (
                  <div className="px-4 pb-2.5 pt-1 border-t border-border/50 flex flex-wrap gap-x-3 gap-y-1 items-center text-[11px] text-ink-muted">
                    <span className="text-gold">—</span>
                    {f.verse!.poem.poet && (
                      <Link
                        href={`/poets/${f.verse!.poem.poet.slug}`}
                        className="inline-flex items-center gap-1 rounded-full bg-wine-soft text-[color:var(--wine)] px-2.5 py-0.5 font-semibold no-underline"
                      >◆ {f.verse!.poem.poet.name_ar}</Link>
                    )}
                    <Link href={`/poems/${f.verse!.poem.slug}`} className="text-ink-muted no-underline">
                      من «<span className="text-ink">{f.verse!.poem.title_ar}</span>»
                    </Link>
                  </div>
                )}
              </li>
            ))}
          </ul>
        </>
      )}
    </main>
  );
}

interface Item {
  type: "poem" | "verse";
  poem?:  { uuid: string; slug: string; title_ar: string; poet?: { slug: string; name_ar: string } | null } | null;
  verse?: {
    uuid: string;
    position: number;
    hemistich_a: string;
    hemistich_b: string | null;
    poem?: { slug: string; title_ar: string; poet?: { slug: string; name_ar: string } | null } | null;
  } | null;
  created_at: string;
}
