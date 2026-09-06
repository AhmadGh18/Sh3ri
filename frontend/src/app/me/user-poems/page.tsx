"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useSession } from "@/lib/session";
import { apiClient, ApiError } from "@/lib/apiClient";
import Breadcrumbs from "@/components/Breadcrumbs";
import SectionTitle from "@/components/SectionTitle";
import AuthModal from "@/components/AuthModal";

interface Taxonomy { id?: number; slug: string; name_ar: string }
interface UserPoem {
  uuid: string;
  title_ar: string;
  raw_text: string;
  status: "draft" | "published";
  visibility: "private" | "public";
  era: Taxonomy | null;
  category: Taxonomy | null;
  published_at: string | null;
  created_at: string;
  updated_at: string;
}

export default function MyUserPoemsPage() {
  const { token } = useSession();
  const [items, setItems] = useState<UserPoem[] | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [askAuth, setAskAuth] = useState(false);
  const [composing, setComposing] = useState(false);
  const [editing, setEditing] = useState<UserPoem | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [categories, setCategories] = useState<Taxonomy[]>([]);

  const load = useCallback(async () => {
    if (!token) return;
    try {
      const res = await apiClient<{ data: UserPoem[] }>("/user-poems?per_page=50");
      setItems(res.data);
      setErr(null);
    } catch (e) {
      setErr(e instanceof Error ? e.message : String(e));
    }
  }, [token]);

  useEffect(() => {
    if (!token) { setAskAuth(true); return; }
    void load();
    // Category is the poem's *genre* (غزل، مدح، حكمة…) — makes sense for
    // any poem, personal or historical. We deliberately don't ask for
    // `era` here: literary eras (الجاهلي، الأموي…) are a property of the
    // canonical corpus, not of a user's own writing.
    apiClient<{ data: (Taxonomy & { id: number })[] }>("/categories")
      .then(c => setCategories(c.data))
      .catch(() => {});
  }, [token, load]);

  async function togglePublish(p: UserPoem) {
    setBusy(p.uuid);
    try {
      const action = p.status === "published" ? "unpublish" : "publish";
      await apiClient(`/user-poems/${p.uuid}/${action}`, { method: "POST" });
      await load();
    } catch (e) {
      alert(e instanceof Error ? e.message : String(e));
    } finally { setBusy(null); }
  }

  async function remove(p: UserPoem) {
    if (!confirm(`حذف قصيدة «${p.title_ar}»؟ لا يمكن التراجع.`)) return;
    setBusy(p.uuid);
    try {
      await apiClient(`/user-poems/${p.uuid}`, { method: "DELETE" });
      await load();
    } catch (e) {
      alert(e instanceof Error ? e.message : String(e));
    } finally { setBusy(null); }
  }

  if (!token) {
    return (
      <>
        <main className="max-w-4xl mx-auto px-4 py-16 text-center">
          <p className="mb-4 text-ink-muted">يجب تسجيل الدخول للوصول إلى قصائدك.</p>
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
    <main className="max-w-5xl mx-auto px-4 py-6">
      <Breadcrumbs items={[{ href: "/", label: "الرئيسية" }, { label: "✎ قصائدي" }]} />

      <div className="flex items-center gap-3 mb-4">
        <SectionTitle glyph="✎" hint={items ? String(items.length) : undefined}>قصائدي</SectionTitle>
        <button
          onClick={() => { setEditing(null); setComposing(true); }}
          className="ms-auto rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white px-4 py-1.5 text-sm font-medium"
        >
          + اكتب قصيدة
        </button>
      </div>

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
          <div className="text-ink-muted mb-2">لم تكتب قصيدة بعد.</div>
          <button
            onClick={() => setComposing(true)}
            className="text-sm text-[color:var(--wine)] underline hover:no-underline"
          >
            ابدأ قصيدتك الأولى ↩
          </button>
        </div>
      )}

      {items && items.length > 0 && (
        <ul className="space-y-3">
          {items.map(p => (
            <li key={p.uuid} className="rounded-xl border border-border bg-parchment-elev p-4">
              <div className="flex items-start gap-3">
                <div className="flex-1 min-w-0">
                  <h3 className="text-lg font-bold text-ink mb-1" style={{ fontFamily: "var(--font-amiri)" }}>
                    {p.title_ar}
                  </h3>
                  <div className="flex gap-1.5 flex-wrap text-[11px] mb-2">
                    <Chip tone={p.status === "published" ? "gold" : "muted"}>
                      {p.status === "published" ? "منشورة" : "مسودّة"}
                    </Chip>
                    <Chip tone={p.visibility === "public" ? "wine" : "muted"}>
                      {p.visibility === "public" ? "عامّة" : "خاصّة"}
                    </Chip>
                    {p.era && <Chip tone="gold">{p.era.name_ar}</Chip>}
                    {p.category && <Chip tone="muted">{p.category.name_ar}</Chip>}
                  </div>
                  <p className="text-sm text-ink/85 leading-loose whitespace-pre-line line-clamp-3"
                     style={{ fontFamily: "var(--font-amiri)" }}>
                    {p.raw_text}
                  </p>
                </div>
                <div className="flex flex-col gap-1.5 items-stretch shrink-0">
                  <button
                    onClick={() => { setEditing(p); setComposing(true); }}
                    disabled={busy === p.uuid}
                    className="rounded-full border border-border bg-parchment-elev text-ink text-xs px-3 py-1 hover:border-wine hover:text-wine"
                  >تحرير</button>
                  <button
                    onClick={() => togglePublish(p)}
                    disabled={busy === p.uuid}
                    className={`rounded-full text-xs px-3 py-1 ${
                      p.status === "published"
                        ? "border border-border text-ink-muted hover:border-ink hover:text-ink"
                        : "bg-[color:var(--wine)] text-white hover:brightness-90 border border-[color:var(--wine)]"
                    }`}
                  >
                    {p.status === "published" ? "إخفاء" : "نشر"}
                  </button>
                  <button
                    onClick={() => remove(p)}
                    disabled={busy === p.uuid}
                    className="rounded-full border border-border text-ink-muted text-xs px-3 py-1 hover:border-[color:var(--wine)] hover:text-[color:var(--wine)]"
                  >حذف</button>
                </div>
              </div>
            </li>
          ))}
        </ul>
      )}

      {composing && (
        <ComposeModal
          categories={categories}
          initial={editing ?? undefined}
          onClose={() => { setComposing(false); setEditing(null); }}
          onSaved={async () => { await load(); setComposing(false); setEditing(null); }}
        />
      )}
    </main>
  );
}

function Chip({ tone = "muted", children }: { tone?: "muted" | "wine" | "gold"; children: React.ReactNode }) {
  const c = {
    muted: "bg-parchment-soft text-ink-muted",
    wine:  "bg-wine-soft text-[color:var(--wine)]",
    gold:  "bg-gold-soft text-[color:var(--gold)]",
  }[tone];
  return <span className={`rounded-full px-2 py-0.5 ${c}`}>{children}</span>;
}

interface ComposeProps {
  categories: Taxonomy[];
  initial?: UserPoem;
  onClose: () => void;
  onSaved: () => void | Promise<void>;
}
function ComposeModal({ categories, initial, onClose, onSaved }: ComposeProps) {
  const [title, setTitle] = useState(initial?.title_ar ?? "");
  const [text, setText] = useState(initial?.raw_text ?? "");
  // Default to public so newly composed poems land on the community feed
  // as soon as the author hits publish. Editing keeps whatever it was.
  const [visibility, setVisibility] = useState<"private" | "public">(initial?.visibility ?? "public");
  const [catId, setCatId] = useState<string>(String((initial?.category as { id?: number } | null)?.id ?? ""));
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
    const payload: Record<string, unknown> = {
      title_ar: title,
      raw_text: text,
      visibility,
    };
    if (catId) payload.category_id = Number(catId);
    try {
      if (initial) {
        await apiClient(`/user-poems/${initial.uuid}`, {
          method: "PATCH",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
      } else {
        await apiClient(`/user-poems`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
      }
      await onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : (e instanceof Error ? e.message : String(e)));
    } finally { setSaving(false); }
  }

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
      onClick={onClose}
    >
      <form
        onSubmit={save}
        onClick={(e) => e.stopPropagation()}
        className="w-full max-w-xl rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl p-6 relative my-auto"
      >
        <button type="button" onClick={onClose} aria-label="Close"
          className="absolute top-3 start-3 w-8 h-8 rounded-full text-ink-muted hover:text-wine hover:bg-parchment-soft grid place-items-center">×</button>
        <h2 className="text-xl font-bold text-ink text-center mb-4" style={{ fontFamily: "var(--font-reem)" }}>
          {initial ? "تحرير القصيدة" : "قصيدة جديدة"}
        </h2>

        {err && (
          <div className="mb-3 rounded-lg border border-[color:var(--wine)] bg-wine-soft text-[color:var(--wine)] px-3 py-2 text-sm">{err}</div>
        )}

        <label className="block mb-3">
          <span className="text-[11px] text-ink-muted mb-1 block">العنوان</span>
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
            rows={10}
            placeholder={"عَلَى قَدْرِ أَهْلِ العَزْمِ تَأْتِي العَزَائِمُ * وَتَأْتِي عَلَى قَدْرِ الكِرَامِ المَكَارِمُ\nوَتَعْظُمُ في عَينِ الصَّغيرِ صِغَارُها * وَتَصْغُرُ في عَينِ العَظِيمِ العَظائِمُ"}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-lg leading-loose focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft resize-y"
            style={{ fontFamily: "var(--font-amiri)" }}
          />
          <span className="block text-[10px] text-ink-dim mt-1">
            سطر لكل بيت. افصل بين الشطرين بـ &nbsp;<code className="font-mono">*</code>&nbsp; أو <code className="font-mono">#</code> أو مسافات متعدّدة.
          </span>
        </label>

        <label className="block mb-3">
          <span className="text-[11px] text-ink-muted mb-1 block">التصنيف (اختياري)</span>
          <select value={catId} onChange={(e) => setCatId(e.target.value)}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-sm">
            <option value="">— بدون تصنيف —</option>
            {categories.map(c => <option key={c.slug} value={String((c as { id: number }).id)}>{c.name_ar}</option>)}
          </select>
        </label>

        <label className="flex items-center gap-3 mb-4 text-sm text-ink-muted">
          <span>الظهور:</span>
          <span className="inline-flex rounded-full border border-border overflow-hidden">
            {(["private", "public"] as const).map(v => (
              <button
                key={v}
                type="button"
                onClick={() => setVisibility(v)}
                className={`px-3 py-1 text-xs transition ${
                  visibility === v
                    ? "bg-[color:var(--wine)] text-white"
                    : "text-ink-muted hover:bg-parchment-soft"
                }`}
              >
                {v === "public" ? "عامّة" : "خاصّة"}
              </button>
            ))}
          </span>
        </label>

        <div className="flex justify-end gap-2 mt-2">
          <button type="button" onClick={onClose}
            className="px-4 py-2 rounded-full text-sm text-ink-muted hover:text-ink">إلغاء</button>
          <button type="submit" disabled={saving}
            className="px-5 py-2 rounded-lg bg-[color:var(--wine)] hover:brightness-90 text-white text-sm font-semibold disabled:opacity-60 disabled:cursor-wait">
            {saving ? "…جاري الحفظ" : (initial ? "حفظ التعديلات" : "حفظ كمسودّة")}
          </button>
        </div>
      </form>
    </div>
  );
}
