"use client";

import { useRouter, usePathname, useSearchParams } from "next/navigation";
import { useTransition } from "react";
import PopoverSelect from "./PopoverSelect";

export interface FilterOption { value: string; label: string; hint?: string }

export interface FilterDef {
  /** query-string key, e.g. "era" */
  key: string;
  /** UI label */
  label: string;
  /** Small glyph before the label */
  icon?: string;
  /** Options — first is treated as "any" */
  options: FilterOption[];
  /** Placeholder for the "any" option, e.g. "كل العصور" */
  anyLabel: string;
}

/**
 * URL-driven filter bar built on top of the custom PopoverSelect (no native
 * <select>). Auto-navigates on selection change via useRouter — no Apply
 * button. Preserves unrelated query params, resets cursor pagination on
 * every filter change.
 */
export default function FilterBar({ filters }: { filters: FilterDef[] }) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [pending, startTransition] = useTransition();

  const current = (k: string) => searchParams.get(k) ?? "";
  const activeCount = filters.filter(f => current(f.key)).length;

  function updateFilter(key: string, value: string) {
    const params = new URLSearchParams(searchParams.toString());
    if (value) params.set(key, value);
    else params.delete(key);
    params.delete("cursor");
    const qs = params.toString();
    startTransition(() => router.push(qs ? `${pathname}?${qs}` : pathname));
  }

  function clearAll() {
    const params = new URLSearchParams(searchParams.toString());
    filters.forEach(f => params.delete(f.key));
    params.delete("cursor");
    const qs = params.toString();
    startTransition(() => router.push(qs ? `${pathname}?${qs}` : pathname));
  }

  return (
    <div
      className={`flex flex-wrap items-stretch gap-2 mb-4 transition-opacity ${pending ? "opacity-70" : ""}`}
      role="group"
      aria-label="مرشّحات"
    >
      {filters.map(f => (
        <PopoverSelect
          key={f.key}
          value={current(f.key)}
          onChange={(v) => updateFilter(f.key, v)}
          options={f.options}
          anyLabel={f.anyLabel}
          icon={f.icon}
          label={f.label}
          active={!!current(f.key)}
        />
      ))}

      {activeCount > 0 && (
        <button
          type="button"
          onClick={clearAll}
          className="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[12px] text-[color:var(--ink-muted)] hover:text-[color:var(--wine)] hover:bg-[color:var(--parchment-soft)] transition"
        >
          <span>×</span>
          <span>إزالة المرشّحات</span>
          <span className="rounded-full bg-[color:var(--wine)] text-white text-[10px] px-1.5 py-px min-w-[16px] text-center">{activeCount}</span>
        </button>
      )}
    </div>
  );
}
