"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useSession } from "@/lib/session";
import { apiClient, ApiError } from "@/lib/apiClient";
import type { CommunityComment } from "@/lib/api";
import AuthModal from "./AuthModal";

/**
 * Comment thread for one community poem. Loads on mount, posts + deletes
 * hit the backend. Public users can read; posting/deleting requires auth.
 */
export default function CommentSection({ uuid, initialCount }: { uuid: string; initialCount: number }) {
  const { token, user } = useSession();
  const [comments, setComments] = useState<CommunityComment[] | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [body, setBody] = useState("");
  const [posting, setPosting] = useState(false);
  const [askAuth, setAskAuth] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const res = await apiClient<{ data: CommunityComment[] }>(
          `/community/user-poems/${encodeURIComponent(uuid)}/comments`,
        );
        setComments(res.data);
      } catch (e) {
        setErr(e instanceof Error ? e.message : String(e));
      }
    })();
  }, [uuid]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    const trimmed = body.trim();
    if (!trimmed) return;
    if (!token) { setAskAuth(true); return; }
    setPosting(true); setErr(null);
    try {
      const res = await apiClient<{ data: CommunityComment }>(
        `/community/user-poems/${encodeURIComponent(uuid)}/comments`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ body: trimmed }),
        },
      );
      setComments((prev) => (prev ? [res.data, ...prev] : [res.data]));
      setBody("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : String(e));
    } finally { setPosting(false); }
  }

  async function remove(c: CommunityComment) {
    if (!confirm("حذف هذا التعليق؟")) return;
    try {
      await apiClient(`/community/user-poems/${encodeURIComponent(uuid)}/comments/${c.uuid}`,
        { method: "DELETE" });
      setComments((prev) => prev?.filter(x => x.uuid !== c.uuid) ?? null);
    } catch (e) {
      alert(e instanceof Error ? e.message : String(e));
    }
  }

  const count = comments?.length ?? initialCount;

  return (
    <section className="mt-10 pt-6 border-t border-border">
      <h2 className="text-lg font-bold text-ink mb-4 flex items-center gap-2"
          style={{ fontFamily: "var(--font-reem)" }}>
        <span className="text-gold">💬</span>
        التعليقات
        <span className="text-sm text-ink-muted font-normal">({count})</span>
      </h2>

      {/* Compose */}
      {token ? (
        <form onSubmit={submit} className="mb-5">
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            placeholder={`اكتب تعليقًا بصفتك ${user?.name ?? "أنت"}…`}
            rows={3}
            maxLength={2000}
            className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-sm focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft resize-y"
          />
          <div className="flex items-center gap-3 mt-2">
            <span className="text-[11px] text-ink-dim">{body.length} / 2000</span>
            <button
              type="submit"
              disabled={posting || !body.trim()}
              className="ms-auto rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white text-sm font-medium px-4 py-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {posting ? "…جاري النشر" : "أرسل"}
            </button>
          </div>
          {err && <div className="mt-2 text-xs text-[color:var(--wine)]">{err}</div>}
        </form>
      ) : (
        <div className="mb-5 rounded-lg border border-dashed border-border bg-parchment-elev/50 p-4 text-center text-sm text-ink-muted">
          <Link href="#" onClick={(e) => { e.preventDefault(); setAskAuth(true); }} className="text-[color:var(--wine)] font-semibold">
            سجّل الدخول
          </Link>
          {" "}لتكتب تعليقًا.
        </div>
      )}

      {/* List */}
      {comments === null && !err && (
        <div className="text-ink-dim text-center py-6">جاري تحميل التعليقات…</div>
      )}
      {comments && comments.length === 0 && (
        <div className="text-ink-muted text-center py-6 text-sm">لا توجد تعليقات بعد. كن أوّل من يعلّق.</div>
      )}
      {comments && comments.length > 0 && (
        <ul className="space-y-3">
          {comments.map(c => (
            <li key={c.uuid} className="rounded-lg border border-border bg-parchment-elev p-3">
              <div className="flex items-center gap-2 mb-1.5">
                <span className="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gold-soft text-[color:var(--gold)] text-xs font-bold"
                      style={{ fontFamily: "var(--font-reem)" }}>
                  {(c.author?.name ?? "؟").trim().split(/\s+/)[0]?.[0] ?? "؟"}
                </span>
                <span className="text-sm font-semibold text-ink">{c.author?.name ?? "—"}</span>
                <span className="text-[11px] text-ink-dim">
                  {new Date(c.created_at).toLocaleString("ar-EG", { dateStyle: "medium", timeStyle: "short" })}
                </span>
                {c.can_delete && (
                  <button
                    onClick={() => remove(c)}
                    className="ms-auto text-[11px] text-ink-dim hover:text-[color:var(--wine)]"
                    title="حذف"
                  >حذف</button>
                )}
              </div>
              <p className="text-sm text-ink whitespace-pre-line leading-relaxed">{c.body}</p>
            </li>
          ))}
        </ul>
      )}

      {askAuth && <AuthModal onClose={() => setAskAuth(false)} />}
    </section>
  );
}
