import Link from "next/link";
import type { Poet } from "@/lib/api";

export default function PoetCard({ poet }: { poet: Poet }) {
  return (
    <Link
      href={`/poets/${poet.slug}`}
      className="block rounded-lg border border-border bg-parchment-elev p-3 transition
                 hover:-translate-y-0.5 hover:border-border-strong hover:shadow-md"
    >
      <h3
        className="text-[15px] font-bold text-ink mb-1 truncate"
        style={{ fontFamily: "var(--font-amiri)" }}
      >
        {poet.name_ar}
      </h3>
      <div className="flex flex-wrap gap-1.5 items-center text-[11px]">
        {poet.era && <span className="rounded-full bg-gold-soft text-[color:var(--gold)] px-2 py-0.5">{poet.era.name_ar}</span>}
        {poet.country && <span className="rounded-full bg-wine-soft text-[color:var(--wine)] px-2 py-0.5">{poet.country.name_ar}</span>}
        {poet.poem_count != null && <span className="text-ink-dim">{poet.poem_count} قصيدة</span>}
      </div>
    </Link>
  );
}
