import Link from "next/link";
import type { Poem } from "@/lib/api";

/**
 * Tight, dense poem card — smaller padding + font than the previous
 * spacious version. Meant to fit 4-per-row on desktop.
 */
export default function PoemCard({ poem }: { poem: Poem }) {
  return (
    <Link
      href={`/poems/${poem.slug}`}
      className="group block rounded-lg border border-border bg-parchment-elev p-3 transition
                 hover:-translate-y-0.5 hover:border-border-strong hover:shadow-md
                 relative overflow-hidden"
    >
      <span className="absolute inset-y-3 start-0 w-0.5 rounded bg-gold opacity-0 group-hover:opacity-100 transition" />
      <h3
        className="text-[15px] font-bold text-ink leading-snug mb-1 line-clamp-2"
        style={{ fontFamily: "var(--font-amiri)" }}
      >
        {poem.title_ar}
      </h3>
      <p className="text-xs text-ink-muted truncate">{poem.poet?.name_ar ?? "—"}</p>
      <div className="mt-2 flex flex-wrap gap-1.5 items-center text-[11px]">
        {poem.era && <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5">{poem.era.name_ar}</span>}
        {poem.category && <span className="rounded-full bg-parchment-soft px-2 py-0.5 text-ink-muted">{poem.category.name_ar}</span>}
        <span className="text-ink-dim">{poem.verse_count} بيت</span>
      </div>
    </Link>
  );
}
