import Link from "next/link";

export default function Pager({ nextHref, prevHref }: { nextHref?: string | null; prevHref?: string | null }) {
  const base = "px-4 py-1.5 rounded-full border text-sm transition";
  const enabled = "bg-parchment-elev text-ink border-border hover:border-wine hover:text-wine";
  const disabled = "text-ink-dim border-border cursor-not-allowed pointer-events-none";
  return (
    <div className="flex justify-between items-center mt-6">
      {prevHref
        ? <Link href={prevHref} className={`${base} ${enabled}`}>→ السابق</Link>
        : <span className={`${base} ${disabled}`}>→ السابق</span>}
      {nextHref
        ? <Link href={nextHref} className={`${base} ${enabled}`}>التالي ←</Link>
        : <span className={`${base} ${disabled}`}>التالي ←</span>}
    </div>
  );
}
