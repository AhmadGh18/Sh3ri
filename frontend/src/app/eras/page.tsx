import Link from "next/link";
import { api } from "@/lib/api";
import SectionTitle from "@/components/SectionTitle";
import Breadcrumbs from "@/components/Breadcrumbs";

export const dynamic = "force-dynamic";

export default async function ErasPage() {
  const eras = (await api.listEras()).data;
  return (
    <main className="max-w-5xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "العصور" }]} />
      <SectionTitle glyph="❦">العصور الأدبية</SectionTitle>
      <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        {eras.map(e => (
          <li key={e.slug}>
            <Link href={`/poems?era=${e.slug}`} className="block rounded-lg border border-border bg-parchment-elev p-3 hover:-translate-y-0.5 hover:border-gold hover:shadow-md transition">
              <h3 className="text-[15px] font-bold text-ink mb-1" style={{ fontFamily: "var(--font-amiri)" }}>{e.name_ar}</h3>
              <div className="text-xs text-ink-muted">{e.name_en || ""}</div>
              <div className="text-[11px] text-ink-dim mt-1">{e.start_year ?? "؟"} → {e.end_year ?? "اليوم"}</div>
            </Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
