import Link from "next/link";
import type { Poem } from "@/lib/api";

/**
 * Poem card with a subtle gold accent bar on the inline-start edge that
 * fills in on hover, a gentle lift, and a soft wine glow at the bottom
 * on hover. Tight padding + Amiri title for density.
 */
export default function PoemCard({ poem }: { poem: Poem }) {
  return (
    <Link
      href={`/poems/${poem.slug}`}
      className="group relative block rounded-xl border border-border bg-parchment-elev
                 px-4 py-3.5 transition-all duration-200
                 hover:-translate-y-0.5 hover:border-[color:var(--gold)]
                 hover:shadow-[0_8px_24px_-12px_rgba(118,22,41,0.25)]
                 overflow-hidden"
    >
      {/* Gold edge accent — faint by default, full on hover */}
      <span
        aria-hidden
        className="absolute inset-y-2 start-0 w-[3px] rounded-full
                   bg-gradient-to-b from-[color:var(--gold)] via-[color:var(--wine)] to-[color:var(--gold)]
                   opacity-30 group-hover:opacity-100 transition-opacity"
      />
      {/* Faint arabesque glyph in the corner */}
      <span
        aria-hidden
        className="absolute -top-3 end-1 text-[48px] leading-none text-[color:var(--gold)] opacity-[0.07]
                   group-hover:opacity-[0.14] transition pointer-events-none"
        style={{ fontFamily: "var(--font-amiri)" }}
      >﴾</span>

      <h3
        className="text-[16px] font-bold text-ink leading-snug mb-1.5 line-clamp-2 pe-6
                   group-hover:text-[color:var(--wine)] transition-colors"
        style={{ fontFamily: "var(--font-amiri)" }}
      >
        {poem.title_ar}
      </h3>
      <p className="text-[12px] text-ink-muted truncate flex items-center gap-1.5 mb-2.5">
        <span className="text-[color:var(--gold)] text-[9px]">◆</span>
        <span>{poem.poet?.name_ar ?? "—"}</span>
      </p>
      <div className="flex flex-wrap gap-1.5 items-center text-[11px]">
        {poem.era && (
          <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5 font-medium">
            {poem.era.name_ar}
          </span>
        )}
        {poem.category && (
          <span className="rounded-full bg-parchment-soft px-2 py-0.5 text-ink-muted">
            {poem.category.name_ar}
          </span>
        )}
        <span className="text-ink-dim ms-auto">{poem.verse_count} بيت</span>
      </div>
    </Link>
  );
}
