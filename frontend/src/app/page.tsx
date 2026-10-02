import Link from "next/link";
import { api } from "@/lib/api";
import SectionTitle from "@/components/SectionTitle";
import HomeSearchHero from "@/components/HomeSearchHero";

// Render at request time — Vercel builds must not fetch the API to prerender.
export const dynamic = "force-dynamic";

/**
 * Home. Just a hero (featured verse) + the four browse tiles.
 * Latest-poems strip was removed by request — users go into /poems for that.
 */
export default async function Home() {
  // Fetch a small page to find the first poem with verses for the hero.
  const seed = await api.listPoems({ per_page: 5 }).catch(() => null);

  let featured: null | {
    poem: Awaited<ReturnType<typeof api.getPoem>>["data"];
    verse: { hemistich_a: string; hemistich_b: string | null; position: number };
  } = null;
  if (seed?.data.length) {
    for (const p of seed.data) {
      const detail = await api.getPoem(p.slug).catch(() => null);
      if (detail?.data.verses.length) {
        const idx = Math.min(detail.data.verses.length - 1, Math.max(0, Math.floor(detail.data.verses.length / 3)));
        featured = { poem: detail.data, verse: detail.data.verses[idx] };
        break;
      }
    }
  }

  const tiles = [
    { glyph: "﴿", name: "القصائد", desc: "تصفّح آلاف القصائد",             href: "/poems"     },
    { glyph: "◆", name: "الشعراء", desc: "٥٣٨ شاعرًا من مختلف العصور",    href: "/poets"     },
    { glyph: "❦", name: "العصور",  desc: "من الجاهلية إلى الحداثة",          href: "/eras"      },
    { glyph: "⁂", name: "البلدان", desc: "شعر العرب من كلّ الأقطار",         href: "/countries" },
    { glyph: "✎", name: "المجتمع", desc: "قصائد كتبها الأعضاء",              href: "/community" },
  ];

  return (
    <main className="max-w-5xl mx-auto px-4 py-8">
      <HomeSearchHero />

      {featured && (
        <section className="relative overflow-hidden rounded-2xl border-2 border-border bg-gradient-to-br from-parchment-soft via-parchment-elev to-parchment-soft p-8 md:p-12 mb-10 shadow-[0_8px_32px_-16px_rgba(118,22,41,0.2)]">
          {/* Decorative glyphs — two giant bracket ornaments */}
          <span
            aria-hidden
            className="absolute -top-10 end-2 text-[color:var(--gold)] opacity-[0.08] text-[200px] md:text-[260px] leading-none pointer-events-none select-none"
            style={{ fontFamily: "var(--font-amiri)" }}
          >﴿</span>
          <span
            aria-hidden
            className="absolute -bottom-14 start-2 text-[color:var(--wine)] opacity-[0.06] text-[200px] md:text-[260px] leading-none pointer-events-none select-none"
            style={{ fontFamily: "var(--font-amiri)" }}
          >﴾</span>

          {/* Eyebrow */}
          <div className="relative text-center mb-5">
            <span className="inline-flex items-center gap-2 text-[10px] md:text-[11px] tracking-[.4em] uppercase text-[color:var(--gold)] font-semibold"
                  style={{ fontFamily: "var(--font-reem)" }}>
              <span aria-hidden>◆</span>
              <span>بيت اليوم</span>
              <span aria-hidden>◆</span>
            </span>
          </div>

          <Link href={`/poems/${featured.poem.slug}`} className="relative block text-center no-underline">
            <div style={{ fontFamily: "var(--font-amiri)" }}
                 className="text-2xl md:text-3xl lg:text-[32px] leading-[2] text-ink
                            group hover:text-[color:var(--wine)] transition-colors">
              <span>{featured.verse.hemistich_a}</span>
              {featured.verse.hemistich_b && (
                <>
                  <span className="mx-5 text-[color:var(--gold)] opacity-60 align-middle text-xl" aria-hidden="true">◈</span>
                  <span>{featured.verse.hemistich_b}</span>
                </>
              )}
            </div>
          </Link>

          {/* Divider */}
          <div className="relative flex items-center justify-center gap-3 text-[color:var(--gold)] text-xs mt-6 mb-3 tracking-widest">
            <span className="h-px bg-border-strong w-20 md:w-28" />
            <span aria-hidden>◆ ❦ ◆</span>
            <span className="h-px bg-border-strong w-20 md:w-28" />
          </div>

          <p className="relative text-center text-sm text-ink-muted">
            من قصيدة{" "}
            <Link href={`/poems/${featured.poem.slug}`} className="text-[color:var(--wine)] font-semibold border-b border-dotted border-[color:var(--gold)] hover:brightness-110">
              «{featured.poem.title_ar}»
            </Link>
            {featured.poem.poet && (
              <>
                {" — "}
                <Link href={`/poets/${featured.poem.poet.slug}`} className="text-[color:var(--wine)] font-semibold border-b border-dotted border-[color:var(--gold)] hover:brightness-110">
                  {featured.poem.poet.name_ar}
                </Link>
              </>
            )}
          </p>
        </section>
      )}

      <SectionTitle glyph="❦">تصفّح المكتبة</SectionTitle>
      <ul className="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        {tiles.map(t => (
          <li key={t.href}>
            <Link
              href={t.href}
              className="group relative block text-center rounded-xl border border-border bg-parchment-elev
                         p-5 overflow-hidden
                         hover:-translate-y-1 hover:border-[color:var(--gold)]
                         hover:shadow-[0_10px_28px_-14px_rgba(118,22,41,0.35)]
                         transition-all duration-200"
            >
              {/* Soft background wash on hover */}
              <span
                aria-hidden
                className="absolute inset-0 bg-gradient-to-br from-gold-soft/0 to-wine-soft/0
                           group-hover:from-gold-soft/40 group-hover:to-wine-soft/20 transition"
              />
              <span
                className="relative block text-3xl text-[color:var(--wine)] mb-1.5 group-hover:scale-110 transition-transform"
                style={{ fontFamily: "var(--font-reem)" }}
              >{t.glyph}</span>
              <div className="relative text-sm font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>{t.name}</div>
              <div className="relative text-[11px] text-ink-muted mt-1">{t.desc}</div>
            </Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
