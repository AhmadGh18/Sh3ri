"use client";

import { useCallback, useEffect, useState } from "react";
import { useSession } from "@/lib/session";
import { apiClient, ApiError } from "@/lib/apiClient";
import Breadcrumbs from "@/components/Breadcrumbs";
import SectionTitle from "@/components/SectionTitle";
import AuthModal from "@/components/AuthModal";

interface Submission {
  uuid: string;
  type: "poem" | "poet" | "correction" | "metadata";
  target_type: string | null;
  target_id: number | null;
  status: "pending" | "approved" | "rejected" | "changes_requested";
  payload: { title_ar?: string; text?: string; name_ar?: string; note?: string; [k: string]: unknown };
  review_notes: string | null;
  reviewed_at: string | null;
  created_at: string;
}

export default function MySubmissionsPage() {
  const { token } = useSession();
  const [items, setItems] = useState<Submission[] | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [askAuth, setAskAuth] = useState(false);
  const [composing, setComposing] = useState(false);

  const load = useCallback(async () => {
    if (!token) return;
    try {
      const res = await apiClient<{ data: Submission[] }>("/submissions?per_page=50");
      setItems(res.data);
      setErr(null);
    } catch (e) {
      setErr(e instanceof Error ? e.message : String(e));
    }
  }, [token]);

  useEffect(() => {
    if (!token) { setAskAuth(true); return; }
    void load();
  }, [token, load]);

  if (!token) {
    return (
      <>
        <main className="max-w-4xl mx-auto px-4 py-16 text-center">
          <p className="mb-4 text-ink-muted">يجب تسجيل الدخول للوصول إلى مقترحاتك.</p>
          <button
            onClick={() => setAskAuth(true)}
            className="rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white px-5 py-2 text-sm font-medium"
          >دخول</button>
        </main>
        {askAuth && <AuthModal onClose={() => setAskAuth(false)} />}
      </>
    );
  }

  return (
    <main className="max-w-4xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "📝 مقترحاتي" }]} />

      <div className="flex items-center gap-3 mb-4">
        <SectionTitle glyph="📝" hint={items ? String(items.length) : undefined}>مقترحاتي</SectionTitle>
        <button
          onClick={() => setComposing(true)}
          className="ms-auto rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white px-4 py-1.5 text-sm font-medium"
        >+ اقترح قصيدة</button>
      </div>

      <p className="text-xs text-ink-dim mb-4">
        اقترح قصائد أو تصحيحات ليراجعها المحرّرون. يظهر التغيير على الموقع بعد الموافقة.
      </p>

      {err && (
        <div className="mb-4 rounded-lg border border-[color:var(--wine)] bg-wine-soft text-[color:var(--wine)] px-4 py-3 text-sm">
          {err}
        </div>
      )}

      {items === null && !err && (
        <div className="text-ink-dim text-center py-12">جاري التحميل…</div>
      )}

      {items && items.length === 0 && (
        <div className="text-center py-16 border border-dashed border-border rounded-2xl bg-parchment-elev/50">
          <div className="text-ink-muted mb-2">لم تقدّم أيّ مقترح بعد.</div>
          <button
            onClick={() => setComposing(true)}
            className="text-sm text-[color:var(--wine)] underline hover:no-underline"
          >قدّم أوّل مقترح ↩</button>
        </div>
      )}

      {items && items.length > 0 && (
        <ul className="space-y-3">
          {items.map(s => (
            <li key={s.uuid} className="rounded-xl border border-border bg-parchment-elev p-4">
              <div className="flex items-start gap-2 mb-1.5 flex-wrap">
                <StatusPill status={s.status} />
                <span className="text-[11px] text-ink-muted">
                  {s.type === "poem" ? "قصيدة جديدة" :
                   s.type === "poet" ? "شاعر جديد" :
                   s.type === "correction" ? "تصحيح" : "تعديل بيانات"}
                </span>
                <span className="text-[11px] text-ink-dim ms-auto">
                  {new Date(s.created_at).toLocaleDateString("ar-EG")}
                </span>
              </div>
              {s.payload.title_ar && (
                <h3 className="text-base font-bold text-ink mb-1" style={{ fontFamily: "var(--font-amiri)" }}>
                  {s.payload.title_ar}
                </h3>
              )}
              {s.payload.text && (
                <p className="text-sm text-ink/85 whitespace-pre-line line-clamp-3 leading-loose"
                   style={{ fontFamily: "var(--font-amiri)" }}>{s.payload.text}</p>
              )}
              {s.payload.note && (
                <p className="text-xs text-ink-muted mt-2">
                  <span className="text-gold">ملاحظة:</span> {s.payload.note}
                </p>
              )}
              {s.review_notes && (
                <p className="text-xs mt-2 rounded bg-parchment-soft px-3 py-2">
                  <span className="text-[color:var(--wine)] font-semibold">ملاحظة المراجع:</span>{" "}
                  {s.review_notes}
                </p>
              )}
            </li>
          ))}
        </ul>
      )}

      {composing && (
        <SubmitModal
          onClose={() => setComposing(false)}
          onSaved={async () => { await load(); setComposing(false); }}
        />
      )}
    </main>
  );
}

function StatusPill({ status }: { status: Submission["status"] }) {
  const map: Record<Submission["status"], { label: string; cls: string }> = {
    pending:            { label: "قيد المراجعة",   cls: "bg-gold-soft text-[color:var(--gold)]" },
    approved:           { label: "مقبول",          cls: "bg-parchment-soft text-ink" },
    rejected:           { label: "مرفوض",          cls: "bg-wine-soft text-[color:var(--wine)]" },
    changes_requested:  { label: "بحاجة لتعديل",   cls: "bg-parchment-soft text-ink-muted" },
  };
  const s = map[status];
  return <span className={`rounded-full px-2.5 py-0.5 text-[11px] ${s.cls}`}>{s.label}</span>;
}

function SubmitModal({ onClose, onSaved }: { onClose: () => void; onSaved: () => void }) {
  const [title, setTitle] = useState("");
  const [text, setText] = useState("");
  const [note, setNote] = useState("");
  const [err, setErr] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = prev; };
  }, []);
  useEffect(() => {
    const h = (e: KeyboardEvent) => { if (e.key === "Escape") onClose(); };
    document.addEventListener("keydown", h);
    return () => document.removeEventListener("keydown", h);
  }, [onClose]);

  async function save(e: React.FormEvent) {
    e.preventDefault();
    setErr(null); setSaving(true);
    try {
      await apiClient(`/submissions`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          type: "poem",
          payload: {
            title_ar: title,
            text,
            note: note || undefined,
          },
        }),
      });
      onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : (e instanceof Error ? e.message : String(e)));
    } finally { setSaving(false); }
  }

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
         onClick={onClose}>
      <form onSubmit={save} onClick={(e) => e.stopPropagation()}
            className="w-full max-w-xl rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl p-6 relative my-auto">
        <button type="button" onClick={onClose} aria-label="Close"
          className="absolute top-3 start-3 w-8 h-8 rounded-full text-ink-muted hover:text-wine hover:bg-parchment-soft grid place-items-center">×</button>
        <h2 className="text-xl font-bold text-ink text-center mb-4" style={{ fontFamily: "var(--font-reem)" }}>
          اقترح قصيدة جديدة
        </h2>

        {err && (
          <div className="mb-3 rounded-lg border border-[color:var(--wine)] bg-wine-soft text-[color:var(--wine)] px-3 py-2 text-sm">{err}</div>
        )}

        <label className="block mb-3">
          <span className="text-[11px] text-ink-muted mb-1 block">عنوان القصيدة</span>
          <input
            required maxLength={512}
            value={title} onChange={(e) => setTitle(e.target.value)}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-base focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft"
            style={{ fontFamily: "var(--font-amiri)" }}
          />
        </label>

        <label className="block mb-3">
          <span className="text-[11px] text-ink-muted mb-1 block">نصّ القصيدة</span>
          <textarea
            required minLength={8}
            value={text} onChange={(e) => setText(e.target.value)}
            rows={8}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-lg leading-loose focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft resize-y"
            style={{ fontFamily: "var(--font-amiri)" }}
          />
        </label>

        <label className="block mb-4">
          <span className="text-[11px] text-ink-muted mb-1 block">ملاحظة للمراجع (اختياري)</span>
          <textarea
            maxLength={1000}
            value={note} onChange={(e) => setNote(e.target.value)}
            rows={2}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-sm focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft resize-y"
          />
        </label>

        <div className="flex justify-end gap-2">
          <button type="button" onClick={onClose}
            className="px-4 py-2 rounded-full text-sm text-ink-muted hover:text-ink">إلغاء</button>
          <button type="submit" disabled={saving}
            className="px-5 py-2 rounded-lg bg-[color:var(--wine)] hover:brightness-90 text-white text-sm font-semibold disabled:opacity-60 disabled:cursor-wait">
            {saving ? "…جاري الإرسال" : "قدّم للمراجعة"}
          </button>
        </div>
      </form>
    </div>
  );
}
