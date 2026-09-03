import Link from "next/link";
import { notFound } from "next/navigation";
import { api } from "@/lib/api";
import Breadcrumbs from "@/components/Breadcrumbs";
import UpvoteButton from "@/components/UpvoteButton";
import CommentSection from "@/components/CommentSection";

export const dynamic = "force-dynamic";

export default async function CommunityPoemPage({ params }: { params: Promise<{ uuid: string }> }) {
  const { uuid } = await params;
  let poem;
  try { poem = (await api.getCommunityPoem(uuid)).data; }
  catch { return notFound(); }

  const lines = poem.raw_text.split(/\r?\n/);

  return (
    <main className="max-w-3xl mx-auto px-4 py-6">
      <Breadcrumbs items={[
        { href: "/", label: "الرئيسية" },
        { href: "/community", label: "المجتمع" },
        { label: poem.title_ar },
      ]} />

      <article className="rounded-2xl border border-border bg-parchment-elev p-8 md:p-10 shadow-sm">
        <header className="text-center mb-4 relative">
          <div className="absolute top-0 end-0">
            <UpvoteButton
              uuid={poem.uuid}
              initialCount={poem.upvote_count ?? 0}
              initialUpvoted={!!poem.upvoted_by_me}
            />
          </div>
          <h1 className="text-2xl md:text-3xl font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>
            {poem.title_ar}
          </h1>
          <p className="mt-1.5 text-sm text-ink-muted">
            بقلم{" "}
            {poem.author ? <span className="text-wine font-semibold">{poem.author.name}</span> : "—"}
          </p>
          <div className="mt-2 flex justify-center gap-1.5 flex-wrap text-[11px] items-center">
            {poem.category && (
              <span className="rounded-full bg-parchment-soft text-ink-muted px-2 py-0.5">
                {poem.category.name_ar}
              </span>
            )}
            {poem.published_at && (
              <span className="text-ink-dim">
                نُشرت {new Date(poem.published_at).toLocaleDateString("ar-EG")}
              </span>
            )}
          </div>
        </header>

        <div className="flex items-center justify-center gap-3 text-gold text-xs my-5 tracking-widest">
          <span className="flex-1 h-px bg-border-strong max-w-24" />
          ◆ ❦ ◆
          <span className="flex-1 h-px bg-border-strong max-w-24" />
        </div>

        <div
          className="text-lg leading-loose text-ink text-center whitespace-pre-line"
          style={{ fontFamily: "var(--font-amiri)" }}
        >
          {lines.map((line, i) => (
            <div key={i} className={line.trim() === "" ? "h-4" : "py-1"}>{line}</div>
          ))}
        </div>

        <CommentSection uuid={poem.uuid} initialCount={poem.comment_count ?? 0} />

        <div className="mt-8 pt-5 border-t border-border flex justify-center">
          <Link href="/community" className="text-sm text-ink-muted hover:text-wine">
            ← كلّ قصائد المجتمع
          </Link>
        </div>
      </article>
    </main>
  );
}
