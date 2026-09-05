import { notFound } from "next/navigation";
import { api } from "@/lib/api";
import PoemCard from "@/components/PoemCard";
import SectionTitle from "@/components/SectionTitle";
import Breadcrumbs from "@/components/Breadcrumbs";

export const dynamic = "force-dynamic";

export default async function PoetPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  let poet, poems;
  try {
    poet = (await api.getPoet(slug)).data;
    poems = (await api.getPoetPoems(slug, { per_page: 60 })).data;
  } catch {
    return notFound();
  }

  return (
    <main className="max-w-5xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { href: "/poets", label: "الشعراء" }, { label: poet.name_ar }]} />

      <section className="rounded-2xl border border-border bg-parchment-elev p-6 mb-6 text-center">
        <h1 className="text-2xl font-bold text-ink mb-1" style={{ fontFamily: "var(--font-reem)" }}>{poet.name_ar}</h1>
        <div className="text-sm text-ink-muted flex flex-wrap gap-x-4 gap-y-1 justify-center">
          {poet.era && <span>{poet.era.name_ar}</span>}
          {poet.country && <span className="before:content-['•'] before:mx-2 before:text-gold">{poet.country.name_ar}</span>}
          {poet.poem_count != null && (
            <span className="before:content-['•'] before:mx-2 before:text-gold">{poet.poem_count} قصيدة</span>
          )}
        </div>
        {poet.bio_ar && (
          <p className="max-w-2xl mx-auto mt-3 text-ink-muted text-sm leading-loose">{poet.bio_ar}</p>
        )}
      </section>

      <SectionTitle glyph="❦" hint={`${poems.length}`}>قصائد الشاعر</SectionTitle>
      {poems.length === 0 ? (
        <div className="text-center py-10 text-ink-dim">لا توجد قصائد.</div>
      ) : (
        <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          {poems.map(p => <li key={p.uuid}><PoemCard poem={p} /></li>)}
        </ul>
      )}
    </main>
  );
}
