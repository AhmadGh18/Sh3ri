import Link from "next/link";
import { notFound } from "next/navigation";
import { api } from "@/lib/api";
import Breadcrumbs from "@/components/Breadcrumbs";
import PoemReader from "@/components/PoemReader";
import FavoriteButton from "@/components/FavoriteButton";

export default async function PoemPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  let poem;
  try { poem = (await api.getPoem(slug)).data; }
  catch { return notFound(); }

  return (
    <main className="max-w-3xl mx-auto px-4 py-6">
      <Breadcrumbs items={[
        { href: "/", label: "الرئيسية" },
        { href: "/poems", label: "القصائد" },
        { label: poem.title_ar },
      ]} />

      <article className="rounded-2xl border border-border bg-parchment-elev p-8 md:p-10 shadow-sm">
        <header className="text-center mb-4 relative">
          <div className="absolute top-0 end-0">
            <FavoriteButton kind="poem" id={poem.slug} title="احفظ القصيدة" />
          </div>
          <h1 className="text-2xl md:text-3xl font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>
            {poem.title_ar}
          </h1>
          <p className="mt-1.5 text-sm text-ink-muted">
            للشاعر{" "}
            {poem.poet
              ? <Link href={`/poets/${poem.poet.slug}`} className="text-wine font-semibold">{poem.poet.name_ar}</Link>
              : "—"}
          </p>
          <div className="mt-2 flex justify-center gap-1.5 flex-wrap text-[11px]">
            {poem.era      && <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5">{poem.era.name_ar}</span>}
            {poem.category && <span className="rounded-full bg-parchment-soft text-ink-muted px-2 py-0.5">{poem.category.name_ar}</span>}
            {poem.meter    && <span className="rounded-full bg-parchment-soft text-ink-muted px-2 py-0.5">بحر {poem.meter.name_ar}</span>}
            <span className="text-ink-dim">{poem.verse_count} بيت</span>
          </div>
        </header>

        <PoemReader poem={poem} />
      </article>
    </main>
  );
}
