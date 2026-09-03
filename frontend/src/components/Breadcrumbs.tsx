import Link from "next/link";

type Crumb = { href: string; label: string } | { label: string };

export default function Breadcrumbs({ items }: { items: Crumb[] }) {
  return (
    <nav className="text-xs text-ink-muted mb-3 flex gap-2 items-center flex-wrap">
      {items.map((c, i) => (
        <span key={i} className="flex items-center gap-2">
          {"href" in c ? (
            <Link href={c.href} className="hover:text-wine">{c.label}</Link>
          ) : (
            <span className="text-ink">{c.label}</span>
          )}
          {i < items.length - 1 && <span className="text-ink-dim">›</span>}
        </span>
      ))}
    </nav>
  );
}
