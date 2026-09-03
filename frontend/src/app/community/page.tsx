import Link from "next/link";
import { api, qs, type CommunityPoem } from "@/lib/api";
import Breadcrumbs from "@/components/Breadcrumbs";
import SectionTitle from "@/components/SectionTitle";
import Pager from "@/components/Pager";
import UpvoteButton from "@/components/UpvoteButton";
import FilterBar from "@/components/FilterBar";

export const dynamic = "force-dynamic";

export default async function CommunityPage({
  searchParams,
}: {
  searchParams: Promise<{ cursor?: string; category?: string }>;
}) {
  const sp = await searchParams;
  const filter = { category: sp.category };

  const [list, cats] = await Promise.all([
    api.listCommunityPoems({ per_page: 24, cursor: sp.cursor, filter, sort: "top" }),
    api.listCategories(),
  ]);

  const activeCat = cats.data.find(c => c.slug === sp.category);

  return (
    <main className="max-w-6xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "المجتمع" }]} />
      <SectionTitle glyph="✎" hint={activeCat?.name_ar ?? `${list.data.length}`}>قصائد الأعضاء</SectionTitle>

      <p className="text-xs text-ink-dim mb-4">
        قصائد كتبها أعضاء الموقع ونشروها للعامة. صوّت ▲ لتظهر في الأعلى، وشاركهم بالتعليق.
        شارك قصيدتك من قائمة{" "}
        <Link href="/me/user-poems" className="text-wine underline">قصائدي</Link>.
      </p>

      <FilterBar
        filters={[
          {
            key: "category",
            label: "التصنيف",
            icon: "❦",
            anyLabel: "كل التصنيفات",
            options: cats.data.map(c => ({ value: c.slug, label: c.name_ar })),
          },
        ]}
      />

      {list.data.length === 0 ? (
        <div className="text-center py-16 border border-dashed border-border rounded-2xl bg-parchment-elev/50">
          <div className="text-ink-muted mb-2">
            {activeCat ? `لا توجد قصائد مجتمعية في تصنيف «${activeCat.name_ar}» بعد.` : "لا توجد قصائد مجتمعية منشورة بعد."}
          </div>
          <Link href="/me/user-poems" className="text-sm text-[color:var(--wine)] underline hover:no-underline">
            {activeCat ? "شارك قصيدتك في هذا التصنيف ↩" : "كن أوّل من يشارك ↩"}
          </Link>
        </div>
      ) : (
        <ul className="grid gap-2.5 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
          {list.data.map(p => <li key={p.uuid}><CommunityCard poem={p} /></li>)}
        </ul>
      )}

      <Pager nextHref={list.meta.next_cursor ? `/community${qs({ ...filter, cursor: list.meta.next_cursor })}` : null} />
    </main>
  );
}

function CommunityCard({ poem }: { poem: CommunityPoem }) {
  return (
    <div className="group relative rounded-xl border border-border bg-parchment-elev px-4 py-3.5 transition
                    hover:-translate-y-0.5 hover:border-border-strong hover:shadow-md">
      {/* Upvote sits absolute so title has full width to breathe. */}
      <div className="absolute top-3 end-3 flex items-center gap-2">
        {typeof poem.comment_count === "number" && poem.comment_count > 0 && (
          <span className="text-[10px] text-ink-dim inline-flex items-center gap-0.5" title="عدد التعليقات">
            💬 {poem.comment_count}
          </span>
        )}
        <UpvoteButton uuid={poem.uuid} initialCount={poem.upvote_count ?? 0} initialUpvoted={!!poem.upvoted_by_me} />
      </div>

      <Link href={`/community/${poem.uuid}`} className="block no-underline pe-16">
        <h3
          className="text-[17px] font-bold text-ink leading-snug line-clamp-2 group-hover:text-[color:var(--wine)] transition"
          style={{ fontFamily: "var(--font-amiri)" }}
        >
          {poem.title_ar}
        </h3>
        <div className="mt-2.5 flex items-center gap-2 text-[11px] text-ink-muted flex-wrap">
          {poem.author && (
            <span className="inline-flex items-center gap-1 text-[color:var(--wine)] font-semibold">
              ✎ {poem.author.name}
            </span>
          )}
          {poem.category && (
            <span className="rounded-full bg-parchment-soft px-2 py-0.5">
              {poem.category.name_ar}
            </span>
          )}
          {poem.published_at && (
            <span className="text-ink-dim ms-auto">
              {new Date(poem.published_at).toLocaleDateString("ar-EG")}
            </span>
          )}
        </div>
      </Link>
    </div>
  );
}
