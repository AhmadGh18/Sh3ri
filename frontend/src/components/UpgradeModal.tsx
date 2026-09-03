"use client";

import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import Link from "next/link";
import { api, type Plan } from "@/lib/api";
import { session } from "@/lib/session";

/**
 * Shown when a play attempt returns 402 (audio_quota_exceeded). Compact
 * three-tier layout (or two if signed-out with just guest+free+paid),
 * plus a "sign in" CTA for guests since signing up is the fastest upgrade.
 */
export default function UpgradeModal({ open, onClose, reason }: {
  open: boolean;
  onClose: () => void;
  reason?: string;
}) {
  const [mounted, setMounted] = useState(false);
  const [plans, setPlans] = useState<Plan[] | null>(null);
  useEffect(() => { setMounted(true); }, []);

  useEffect(() => {
    if (!open || plans) return;
    api.listPlans().then(r => setPlans(r.data)).catch(() => setPlans([]));
  }, [open, plans]);

  useEffect(() => {
    if (!open) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    function onKey(e: KeyboardEvent) { if (e.key === "Escape") onClose(); }
    document.addEventListener("keydown", onKey);
    return () => { document.body.style.overflow = prev; document.removeEventListener("keydown", onKey); };
  }, [open, onClose]);

  if (!mounted || !open) return null;

  const signedIn = typeof window !== "undefined" && session.isSignedIn();

  return createPortal(
    <div
      className="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
      onClick={onClose}
    >
      <div
        className="w-full max-w-3xl rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl overflow-hidden animate-[modal-in_.18s_ease]"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="p-6 md:p-8 border-b border-border text-center">
          <div className="text-[color:var(--gold)] text-2xl mb-1">♪</div>
          <h2 className="text-xl md:text-2xl text-ink font-bold" style={{ fontFamily: "var(--font-reem)" }}>
            استنفدت حصّة الاستماع اليومية
          </h2>
          <p className="mt-2 text-sm text-ink-muted">
            {reason || "طوّر خطتك لمواصلة الاستماع بلا انقطاع."}
          </p>
          {!signedIn && (
            <p className="mt-3 text-[13px]">
              <Link href="/auth/login" className="text-[color:var(--wine)] font-semibold border-b border-dotted border-gold">
                سجّل الدخول
              </Link>{" "}
              للحصول على ٢٠ تشغيلًا يوميًا مجانًا.
            </p>
          )}
        </div>

        <div className="p-4 md:p-6">
          {!plans && <div className="text-center py-8 text-ink-dim">تحميل الخطط…</div>}
          {plans && plans.length > 0 && (
            <div className="grid gap-3 md:grid-cols-3">
              {plans.map((p, idx) => <PlanCard key={p.code} plan={p} highlighted={idx === plans.length - 2} />)}
            </div>
          )}
          {plans && plans.length === 0 && (
            <div className="text-center py-8 text-ink-dim">لا خطط متاحة حاليًا.</div>
          )}
        </div>

        <div className="border-t border-border px-6 py-3 text-center">
          <button onClick={onClose} className="text-sm text-ink-muted hover:text-ink">
            إغلاق
          </button>
        </div>
      </div>

      <style>{`
        @keyframes modal-in {
          from { opacity: 0; transform: translateY(10px) scale(.98); }
          to   { opacity: 1; transform: translateY(0) scale(1); }
        }
      `}</style>
    </div>,
    document.body,
  );
}

function PlanCard({ plan, highlighted }: { plan: Plan; highlighted?: boolean }) {
  const price = plan.is_free
    ? "مجانًا"
    : `$${(plan.price_cents / 100).toFixed(2)}`;
  const quota = plan.is_unlimited
    ? "غير محدود"
    : `${plan.daily_audio_plays} تشغيل / يوم`;

  return (
    <div className={`rounded-xl p-4 border-2 transition ${highlighted
      ? "border-[color:var(--gold)] bg-gold-soft/40"
      : "border-border bg-parchment"}`}>
      {highlighted && (
        <div className="text-[10px] text-[color:var(--wine)] font-bold mb-1 tracking-widest">الأكثر شيوعًا</div>
      )}
      <div className="text-lg font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>{plan.name_ar}</div>
      <div className="text-[11px] text-ink-muted mt-0.5 min-h-[16px]">{plan.tagline_ar}</div>
      <div className="mt-3 text-2xl text-[color:var(--wine)] font-bold">
        {price}
        {!plan.is_free && <span className="text-xs text-ink-muted ms-1">/ شهر</span>}
      </div>
      <div className="mt-2 text-xs text-ink-muted">
        <span className="text-[color:var(--gold)]">♪</span> {quota}
        {plan.allow_download && <div className="mt-0.5">⬇︎ تحميل الأبيات صوتيًا</div>}
      </div>
      <Link
        href="/plans"
        className={`mt-4 block text-center rounded-full py-1.5 text-sm font-semibold transition no-underline
          ${plan.is_free
            ? "bg-parchment-soft text-ink-muted border border-border hover:border-border-strong"
            : "bg-[color:var(--wine)] text-white hover:brightness-90"}`}
      >
        {plan.is_free ? "الخطة الحالية" : "اختر هذه الخطة"}
      </Link>
    </div>
  );
}
