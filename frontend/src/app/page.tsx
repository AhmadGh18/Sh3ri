import Link from "next/link";
import { api } from "@/lib/api";
import SectionTitle from "@/components/SectionTitle";
import HomeSearchHero from "@/components/HomeSearchHero";

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
        <section className="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-br from-parchment-soft to-parchment-elev p-8 mb-8">
          <span
            className="absolute -top-8 end-0 text-[color:var(--gold)] opacity-10 text-[180px] leading-none pointer-events-none"
            style={{ fontFamily: "var(--font-amiri)" }}
          >﴿</span>
          <p
            className="text-xs tracking-[.3em] uppercase text-gold mb-2 text-center"
            style={{ fontFamily: "var(--font-reem)" }}
          >بيت اليوم</p>
          <Link href={`/poems/${featured.poem.slug}`} className="block text-center">
            <div style={{ fontFamily: "var(--font-amiri)" }} className="text-xl md:text-2xl leading-loose text-ink">
              <span>{featured.verse.hemistich_a}</span>
              {featured.verse.hemistich_b && (
                <>
                  <span className="mx-4 text-gold opacity-70 align-middle" aria-hidden="true">◈</span>
                  <span>{featured.verse.hemistich_b}</span>
                </>
              )}
            </div>
          </Link>
          <p className="text-center mt-3 text-sm text-ink-muted">
            من قصيدة{" "}
            <Link href={`/poems/${featured.poem.slug}`} className="text-wine border-b border-dotted border-gold">
              «{featured.poem.title_ar}»
            </Link>
            {" — "}
            {featured.poem.poet && (
              <Link href={`/poets/${featured.poem.poet.slug}`} className="text-wine border-b border-dotted border-gold">
                {featured.poem.poet.name_ar}
              </Link>
            )}
          </p>
        </section>
      )}

      <SectionTitle glyph="❦">تصفّح المكتبة</SectionTitle>
      <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        {tiles.map(t => (
          <li key={t.href}>
            <Link
              href={t.href}
              className="block text-center rounded-lg border border-border bg-parchment-elev p-4 hover:-translate-y-0.5 hover:border-gold hover:shadow-md transition"
            >
              <span className="block text-2xl text-wine mb-1" style={{ fontFamily: "var(--font-reem)" }}>{t.glyph}</span>
              <div className="text-sm font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>{t.name}</div>
              <div className="text-[11px] text-ink-muted mt-0.5">{t.desc}</div>
            </Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
