"use client";

import { useCallback, useEffect, useState } from "react";
import { api, type Plan } from "@/lib/api";
import { useAudioUsage } from "@/lib/audioUsage";
import { session } from "@/lib/session";

/**
 * Loads plans client-side so a transient backend outage never turns into
 * a cached empty page. Retries on demand.
 */
export default function PlansGrid() {
  const { usage } = useAudioUsage();
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState<Plan | null>(null);
  const [attempt, setAttempt] = useState(0);

  const load = useCallback(async () => {
    setError(null);
    try {
      const r = await api.listPlans();
      setPlans(r.data);
    } catch {
      setPlans([]);
      setError("تعذّر الاتصال بالخادم. تأكّد من تشغيله ثم أعِد المحاولة.");
    }
  }, []);

  useEffect(() => { void load(); }, [load, attempt]);

  if (plans === null) {
    return (
      <div className="grid gap-4 md:grid-cols-3">
        {[0, 1, 2].map(i => (
          <div key={i} className="rounded-2xl border-2 border-border bg-parchment-elev h-72 animate-pulse" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-[color:var(--wine)]/40 bg-wine-soft/40 p-8 text-center">
        <div className="text-[color:var(--wine)] text-2xl mb-2">⚠</div>
        <p className="text-[color:var(--wine)] font-semibold">{error}</p>
        <button
          onClick={() => setAttempt(a => a + 1)}
          className="mt-4 rounded-full bg-[color:var(--wine)] text-white px-5 py-1.5 text-sm font-semibold hover:brightness-90"
        >
          حاول مجددًا
        </button>
      </div>
    );
  }

  if (plans.length === 0) {
    return <div className="text-center py-10 text-ink-dim">لا خطط متاحة حاليًا.</div>;
  }

  const currentCode = usage?.plan.code;

  return (
    <>
      <div className="grid gap-4 md:grid-cols-3">
        {plans.map((p, idx) => (
          <PlanCard
            key={p.code}
            plan={p}
            highlight={idx === plans.length - 2}
            current={currentCode === p.code}
            onChoose={() => setPending(p)}
          />
        ))}
      </div>
      {pending && <CheckoutModal plan={pending} onClose={() => setPending(null)} />}
    </>
  );
}

function PlanCard({
  plan, highlight, current, onChoose,
}: {
  plan: Plan; highlight?: boolean; current?: boolean; onChoose: () => void;
}) {
  const price = plan.is_free ? "مجانًا" : `$${(plan.price_cents / 100).toFixed(2)}`;
  const perMonth = plan.is_free ? "" : " / شهر";
  const quota = plan.is_unlimited ? "غير محدود" : `${plan.daily_audio_plays} تشغيل يوميًا`;

  const cta = current
    ? "خطتك الحالية"
    : plan.is_free
      ? "الخطة الافتراضية"
      : "اشترك الآن";

  const ctaClass = current
    ? "bg-gold-soft text-[color:var(--wine)] border-2 border-[color:var(--gold)] cursor-default"
    : plan.is_free
      ? "bg-parchment-soft text-ink-muted border border-border"
      : "bg-[color:var(--wine)] text-white hover:brightness-90";

  return (
    <div className={`relative rounded-2xl p-6 flex flex-col border-2 transition
      ${highlight ? "border-[color:var(--gold)] bg-gold-soft/30 md:scale-105 shadow-lg" : "border-border bg-parchment-elev"}`}>
      {highlight && (
        <div className="absolute -top-3 start-1/2 -translate-x-1/2 text-[10px] font-bold tracking-widest text-white bg-[color:var(--wine)] rounded-full px-3 py-1">
          الأكثر شيوعًا
        </div>
      )}
      {current && (
        <div className="absolute top-3 end-3 text-[10px] font-bold text-[color:var(--wine)] bg-white/70 border border-[color:var(--wine)]/40 rounded-full px-2 py-0.5">
          حاليًا
        </div>
      )}

      <div className="text-xl font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>{plan.name_ar}</div>
      {plan.tagline_ar && <div className="mt-1 text-[12px] text-ink-muted min-h-[32px]">{plan.tagline_ar}</div>}

      <div className="mt-5 flex items-baseline gap-1">
        <span className="text-4xl font-bold text-[color:var(--wine)]">{price}</span>
        <span className="text-sm text-ink-muted">{perMonth}</span>
      </div>

      <ul className="mt-5 space-y-2 text-sm text-ink flex-1">
        <Feat included>{quota}</Feat>
        <Feat included>الاستماع لكل الأبيات والقصائد</Feat>
        <Feat included>الحفظ في المفضّلة</Feat>
        <Feat included>المشاركة في المجتمع</Feat>
        <Feat included={plan.allow_download}>تحميل الأبيات بصيغة MP3</Feat>
      </ul>

      <button
        onClick={current || plan.is_free ? undefined : onChoose}
        disabled={current || plan.is_free}
        className={`mt-6 rounded-full py-2.5 text-sm font-semibold transition ${ctaClass}`}
      >
        {cta}
      </button>
    </div>
  );
}

function Feat({ children, included }: { children: React.ReactNode; included?: boolean }) {
  return (
    <li className={`flex items-start gap-2 ${included ? "text-ink" : "text-ink-dim line-through decoration-ink-dim/30"}`}>
      <span className={included ? "text-[color:var(--gold)]" : "text-ink-dim"}>{included ? "✓" : "—"}</span>
      <span>{children}</span>
    </li>
  );
}

function CheckoutModal({ plan, onClose }: { plan: Plan; onClose: () => void }) {
  const signedIn = typeof window !== "undefined" && session.isSignedIn();

  return (
    <div
      className="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
      onClick={onClose}
    >
      <div
        className="w-full max-w-md rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl p-6 text-center"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="text-[color:var(--gold)] text-3xl mb-2">◆</div>
        <h3 className="text-lg font-bold text-ink" style={{ fontFamily: "var(--font-reem)" }}>
          الاشتراك في «{plan.name_ar}»
        </h3>
        <p className="mt-2 text-sm text-ink-muted">
          سنُطلق المدفوعات قريبًا. ابقَ على اطّلاع.
        </p>
        {!signedIn && (
          <p className="mt-3 text-[12px] text-[color:var(--wine)]">
            سجّل الدخول أولًا لنستطيع ربط الخطة بحسابك عند الإطلاق.
          </p>
        )}
        <div className="mt-5 flex flex-col gap-2">
          <button onClick={onClose} className="rounded-full py-2 bg-parchment-soft border border-border text-sm text-ink hover:border-border-strong">
            إغلاق
          </button>
        </div>
      </div>
    </div>
  );
}
