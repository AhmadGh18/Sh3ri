import PlansGrid from "./PlansGrid";

export const metadata = {
  title: "الخطط والاشتراكات — شِعْري",
  description: "اختر الخطة التي تناسبك للاستماع لأكبر مكتبة شعر عربية.",
};

/**
 * Static shell. The plans list itself is fetched client-side inside
 * PlansGrid — that way a transient backend outage at SSR time doesn't
 * bake an empty response into Next's route cache for 5 minutes.
 */
export default function PlansPage() {
  return (
    <main className="max-w-5xl mx-auto px-4 py-10 md:py-14">
      <div className="text-center mb-10">
        <h1 className="text-3xl md:text-4xl text-ink font-bold" style={{ fontFamily: "var(--font-reem)" }}>
          خطط الاستماع
        </h1>
        <p className="mt-3 text-ink-muted max-w-xl mx-auto">
          كل الميزات مجانية للاستخدام — الخطط تحدّد فقط عدد التشغيلات الصوتية اليومية.
          ألغِ في أي وقت.
        </p>
      </div>

      <PlansGrid />

      <section className="mt-14 grid gap-4 md:grid-cols-3 text-center">
        <TrustBlock title="ادفع بأمان">
          مدفوعات مشفّرة عبر مزوّد مرخّص. لا نخزّن بيانات بطاقتك.
        </TrustBlock>
        <TrustBlock title="ألغِ في أي وقت">
          لا التزامات طويلة الأمد — أنهِ اشتراكك من إعداداتك في أي وقت.
        </TrustBlock>
        <TrustBlock title="بيانات نظيفة">
          نستخدم عائدات الاشتراك لتحسين جودة النصوص والصوت، لا للإعلانات.
        </TrustBlock>
      </section>
    </main>
  );
}

function TrustBlock({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-xl bg-parchment-elev border border-border p-4">
      <div className="text-[color:var(--gold)] mb-1 text-lg">◆</div>
      <div className="font-bold text-ink text-sm" style={{ fontFamily: "var(--font-reem)" }}>{title}</div>
      <div className="text-[12px] text-ink-muted mt-1 leading-relaxed">{children}</div>
    </div>
  );
}
