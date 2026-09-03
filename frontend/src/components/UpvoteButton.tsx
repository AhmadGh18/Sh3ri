"use client";

import { useState } from "react";
import { useSession } from "@/lib/session";
import { apiClient } from "@/lib/apiClient";
import AuthModal from "./AuthModal";

interface Props {
  uuid: string;
  initialCount: number;
  initialUpvoted?: boolean;
  className?: string;
}

/**
 * Reddit-style upvote toggle. Optimistic: count flips instantly, request
 * settles in the background. On failure we roll back the local count.
 */
export default function UpvoteButton({ uuid, initialCount, initialUpvoted, className }: Props) {
  const { token } = useSession();
  const [count, setCount] = useState(initialCount);
  const [up, setUp] = useState(!!initialUpvoted);
  const [busy, setBusy] = useState(false);
  const [askAuth, setAskAuth] = useState(false);

  async function onClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (!token) { setAskAuth(true); return; }
    if (busy) return;
    setBusy(true);
    const wasUp = up;
    setUp(!wasUp);
    setCount((c) => c + (wasUp ? -1 : 1));
    try {
      const res = await apiClient<{ data: { upvoted_by_me: boolean; upvote_count: number } }>(
        `/community/user-poems/${encodeURIComponent(uuid)}/upvote`,
        { method: "POST" },
      );
      // Trust server for the authoritative count.
      setUp(res.data.upvoted_by_me);
      setCount(res.data.upvote_count);
    } catch {
      // rollback
      setUp(wasUp);
      setCount((c) => c + (wasUp ? 1 : -1));
    } finally { setBusy(false); }
  }

  return (
    <>
      <button
        onClick={onClick}
        disabled={busy}
        aria-pressed={up}
        title={up ? "أزل التصويت" : "صوّت لهذه القصيدة"}
        className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition
          ${up
            ? "bg-wine-soft text-[color:var(--wine)] border-[color:var(--wine)]"
            : "bg-parchment-elev text-ink-muted border-border hover:border-[color:var(--wine)] hover:text-[color:var(--wine)]"}
          ${busy ? "opacity-70 cursor-wait" : "cursor-pointer"}
          ${className ?? ""}`}
      >
        <span className={`text-[13px] leading-none transition-transform ${up ? "scale-110" : ""}`}>▲</span>
        <span>{count}</span>
      </button>
      {askAuth && <AuthModal onClose={() => setAskAuth(false)} />}
    </>
  );
}
