import { api, qs } from "@/lib/api";
import PoemCard from "@/components/PoemCard";
import SectionTitle from "@/components/SectionTitle";
import Breadcrumbs from "@/components/Breadcrumbs";
import Pager from "@/components/Pager";
import FilterBar from "@/components/FilterBar";

export const dynamic = "force-dynamic";

type Search = { era?: string; category?: string; cursor?: string };

export default async function PoemsPage({ searchParams }: { searchParams: Promise<Search> }) {
  const sp = await searchParams;
  const filter = { era: sp.era, category: sp.category };

  const [poems, eras, cats] = await Promise.all([
    api.listPoems({ per_page: 24, cursor: sp.cursor, filter }),
    api.listEras(),
    api.listCategories(),
  ]);

  const activeEra = eras.data.find(e => e.slug === sp.era);
  const activeCat = cats.data.find(c => c.slug === sp.category);
  const nextCursor = poems.meta.next_cursor;

  return (
    <main className="max-w-6xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "القصائد" }]} />
      <SectionTitle glyph="﴿" hint={[activeEra?.name_ar, activeCat?.name_ar].filter(Boolean).join(" · ") || null}>
        القصائد
      </SectionTitle>

      <FilterBar
        filters={[
          {
            key: "era",
            label: "العصر",
            icon: "◆",
            anyLabel: "كل العصور",
            options: eras.data.map(e => ({ value: e.slug, label: e.name_ar })),
          },
          {
            key: "category",
            label: "التصنيف",
            icon: "❦",
            anyLabel: "كل التصنيفات",
            options: cats.data.map(c => ({ value: c.slug, label: c.name_ar })),
          },
        ]}
      />

      {poems.data.length === 0 ? (
        <div className="text-center py-10 text-ink-dim">لا توجد قصائد بهذه المرشّحات.</div>
      ) : (
        <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          {poems.data.map(p => <li key={p.uuid}><PoemCard poem={p} /></li>)}
        </ul>
      )}

      <Pager nextHref={nextCursor ? `/poems${qs({ ...filter, cursor: nextCursor })}` : null} />
    </main>
  );
}
