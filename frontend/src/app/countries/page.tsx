import Link from "next/link";
import { api } from "@/lib/api";
import SectionTitle from "@/components/SectionTitle";
import Breadcrumbs from "@/components/Breadcrumbs";

export const dynamic = "force-dynamic";

export default async function CountriesPage() {
  const countries = (await api.listCountries()).data;
  return (
    <main className="max-w-5xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "البلدان" }]} />
      <SectionTitle glyph="⁂">البلدان</SectionTitle>
      <ul className="grid gap-2.5 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        {countries.map(c => (
          <li key={c.slug}>
            <Link href={`/poets?country=${c.slug}`} className="block rounded-lg border border-border bg-parchment-elev p-3 hover:-translate-y-0.5 hover:border-gold hover:shadow-md transition">
              <h3 className="text-[15px] font-bold text-ink mb-0.5" style={{ fontFamily: "var(--font-amiri)" }}>{c.name_ar}</h3>
              <div className="text-[11px] text-ink-dim">{c.iso_code || "—"} · {c.name_en || ""}</div>
            </Link>
          </li>
        ))}
      </ul>
    </main>
  );
}
