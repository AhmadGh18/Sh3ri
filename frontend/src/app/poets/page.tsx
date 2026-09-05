import { api, qs } from "@/lib/api";
import PoetCard from "@/components/PoetCard";
import SectionTitle from "@/components/SectionTitle";
import Breadcrumbs from "@/components/Breadcrumbs";
import Pager from "@/components/Pager";
import FilterBar from "@/components/FilterBar";

export const dynamic = "force-dynamic";

type Search = { country?: string; era?: string; cursor?: string };

export default async function PoetsPage({ searchParams }: { searchParams: Promise<Search> }) {
  const sp = await searchParams;
  const filter = { country: sp.country, era: sp.era };
  const [poets, eras, countries] = await Promise.all([
    api.listPoets({ per_page: 30, cursor: sp.cursor, filter }),
    api.listEras(),
    api.listCountries(),
  ]);

  return (
    <main className="max-w-6xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "الشعراء" }]} />
      <SectionTitle glyph="◆">الشعراء</SectionTitle>

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
            key: "country",
            label: "البلد",
            icon: "⁂",
            anyLabel: "كل البلدان",
            options: countries.data.map(c => ({ value: c.slug, label: c.name_ar })),
          },
        ]}
      />

      {poets.data.length === 0 ? (
        <div className="text-center py-10 text-ink-dim">لا يوجد شعراء بهذه المرشّحات.</div>
      ) : (
        <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          {poets.data.map(p => <li key={p.uuid}><PoetCard poet={p} /></li>)}
        </ul>
      )}

      <Pager nextHref={poets.meta.next_cursor ? `/poets${qs({ ...filter, cursor: poets.meta.next_cursor })}` : null} />
    </main>
  );
}
