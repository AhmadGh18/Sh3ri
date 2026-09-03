"use client";

import Link from "next/link";
import { useAudioUsage } from "@/lib/audioUsage";

/**
 * Little pill showing "N / M تشغيل اليوم" or "غير محدود" for Pro users.
 * Hidden until we've fetched at least once, so it doesn't flash "0 / 0".
 */
export default function AudioQuotaBadge({ compact = false }: { compact?: boolean }) {
  const { usage, loaded } = useAudioUsage();
  if (!loaded || !usage) return null;

  const isUnlimited = usage.plan.is_unlimited || usage.remaining === null;
  const remaining = usage.remaining ?? 0;
  const limit = usage.plan.daily_limit ?? 0;

  const low = !isUnlimited && limit > 0 && remaining <= Math.max(1, Math.floor(limit * 0.15));
  const empty = !isUnlimited && remaining <= 0;

  return (
    <Link
      href="/plans"
      className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium border transition no-underline
        ${empty
          ? "bg-[color:var(--wine)] text-white border-[color:var(--wine)] animate-pulse"
          : low
            ? "bg-gold-soft border-[color:var(--gold)] text-[color:var(--wine)]"
            : "bg-parchment-soft border-border text-ink-muted hover:border-border-strong"}`}
      title={isUnlimited ? "خطة غير محدودة" : "تشغيلات اليوم — اضغط للترقية"}
    >
      <span className="text-[color:var(--gold)]">♪</span>
      {isUnlimited ? (
        <span>{compact ? "∞" : "غير محدود"}</span>
      ) : (
        <span>
          <span className="text-ink">{remaining}</span>
          <span className="opacity-60"> / {limit}</span>
          {!compact && <span className="opacity-60 me-1"> اليوم</span>}
        </span>
      )}
    </Link>
  );
}
