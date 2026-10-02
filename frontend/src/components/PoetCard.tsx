import Link from "next/link";
import type { Poet } from "@/lib/api";

/**
 * Poet card. Gold initial-letter medallion on the inline-start side,
 * same hover vocabulary as PoemCard so the two feel like siblings.
 */
export default function PoetCard({ poet }: { poet: Poet }) {
  const initial = (poet.name_ar || "؟").trim().charAt(0);

  return (
    <Link
      href={`/poets/${poet.slug}`}
      className="group relative flex items-start gap-3 rounded-xl border border-border bg-parchment-elev
                 px-3.5 py-3 transition-all duration-200
                 hover:-translate-y-0.5 hover:border-[color:var(--gold)]
                 hover:shadow-[0_8px_24px_-12px_rgba(118,22,41,0.25)]
                 overflow-hidden"
    >
      {/* Initial letter medallion */}
      <span
        aria-hidden
        className="shrink-0 w-11 h-11 rounded-full grid place-items-center text-xl font-bold
                   bg-gradient-to-br from-gold-soft via-parchment-soft to-wine-soft
                   border border-[color:var(--gold)]/40 text-[color:var(--wine)]
                   group-hover:border-[color:var(--gold)] group-hover:shadow-[0_0_0_3px_rgba(197,164,98,0.15)]
                   transition"
        style={{ fontFamily: "var(--font-reem)" }}
      >{initial}</span>

      <div className="flex-1 min-w-0">
        <h3
          className="text-[16px] font-bold text-ink mb-1 truncate group-hover:text-[color:var(--wine)] transition-colors"
          style={{ fontFamily: "var(--font-amiri)" }}
        >
          {poet.name_ar}
        </h3>
        <div className="flex flex-wrap gap-1.5 items-center text-[11px]">
          {poet.era && (
            <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5 font-medium">
              {poet.era.name_ar}
            </span>
          )}
          {poet.country && (
            <span className="rounded-full bg-wine-soft text-[color:var(--wine)] px-2 py-0.5 font-medium">
              {poet.country.name_ar}
            </span>
          )}
          {poet.poem_count != null && (
            <span className="text-ink-dim ms-auto">{poet.poem_count} قصيدة</span>
          )}
        </div>
      </div>
    </Link>
  );
}
