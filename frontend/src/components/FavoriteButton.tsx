"use client";

import { useEffect, useState } from "react";
import { favorites, useFavorites } from "@/lib/favorites";
import { useSession } from "@/lib/session";
import AuthModal from "./AuthModal";
import { HeartIcon } from "./Icon";

interface Props {
  kind: "poem" | "verse";
  id: string; // poem slug or verse uuid
  /** If true, renders as a labeled pill instead of icon-only. */
  labeled?: boolean;
  /** Optional CSS class override for the outer button. */
  className?: string;
  /** Optional aria-label / tooltip text override. */
  title?: string;
}

/**
 * Heart button that toggles a poem/verse favorite. If the user isn't
 * signed in, clicking opens the auth modal instead. Backed by
 * lib/favorites so any FavoriteButton on the page reflects the shared
 * state (open two poems in tabs → favoriting one shows a full heart on
 * the other card too after refresh).
 */
export default function FavoriteButton({ kind, id, labeled, className, title }: Props) {
  const { token } = useSession();
  const favs = useFavorites();
  const [busy, setBusy] = useState(false);
  const [askAuth, setAskAuth] = useState(false);

  const isFav = kind === "poem" ? favs.poems.has(id) : favs.verses.has(id);

  useEffect(() => {
    if (token) void favorites.ensureLoaded();
  }, [token]);

  async function onClick(e: React.MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    if (!token) { setAskAuth(true); return; }
    if (busy) return;
    setBusy(true);
    try { await favorites.toggle(kind, id); }
    catch { /* rolled back inside the store */ }
    finally { setBusy(false); }
  }

  const label = isFav ? "إزالة من المفضلة" : "أضف إلى المفضلة";

  const base = className ??
    (labeled
      ? "inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition"
      : "inline-flex items-center justify-center rounded-full w-9 h-9 border transition");

  return (
    <>
      <button
        onClick={onClick}
        disabled={busy}
        aria-pressed={isFav}
        aria-label={title ?? label}
        title={title ?? label}
        className={`${base} ${
          isFav
            ? "bg-wine-soft text-[color:var(--wine)] border-[color:var(--wine)]"
            : "bg-parchment-elev text-ink-muted border-border hover:border-[color:var(--wine)] hover:text-[color:var(--wine)]"
        } ${busy ? "opacity-70 cursor-wait" : "cursor-pointer"}`}
      >
        <HeartIcon size={16} filled={isFav} className={`transition-transform ${isFav ? "scale-110" : ""}`} />
        {labeled && <span>{isFav ? "محفوظة" : "احفظ"}</span>}
      </button>
      {askAuth && <AuthModal onClose={() => setAskAuth(false)} />}
    </>
  );
}
