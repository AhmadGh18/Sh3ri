"use client";

/**
 * Client-side favorites store. Loads the user's favorites once per sign-in
 * and tracks them in Sets so any component can check `isFavorited()` in O(1).
 * Toggles are optimistic — UI flips immediately, request goes out in the
 * background, rolls back on failure.
 *
 * Same event-emitter pattern as `lib/session.ts` so it plays with SSR and
 * React's useSyncExternalStore.
 */

import { useSyncExternalStore } from "react";
import { session } from "./session";
import { apiClient } from "./apiClient";

type FavKind = "poem" | "verse";

interface State {
  poems: Set<string>;   // poem slugs
  verses: Set<string>;  // verse uuids
  loaded: boolean;      // did we fetch /me/favorites yet this session?
  loading: boolean;
}

const listeners = new Set<() => void>();
let state: State = { poems: new Set(), verses: new Set(), loaded: false, loading: false };

function emit() { listeners.forEach(fn => fn()); }
function snapshot(): State { return state; }

function reset() {
  state = { poems: new Set(), verses: new Set(), loaded: false, loading: false };
  emit();
}

/** Load or refresh the user's favorites list from the backend. */
async function load(force = false) {
  if (!session.isSignedIn()) { reset(); return; }
  if (state.loading) return;
  if (state.loaded && !force) return;

  state = { ...state, loading: true };
  emit();
  try {
    // Backend caps per_page at 50 by design; we page through so a heavy
    // user's UI is still accurate.
    const poems = new Set<string>();
    const verses = new Set<string>();
    let cursor: string | null = null;
    let safety = 40; // hard-cap 2000 items — big enough for MVP, safe.
    while (safety-- > 0) {
      const q = cursor ? `?per_page=50&cursor=${encodeURIComponent(cursor)}` : `?per_page=50`;
      const page = await apiClient<{
        data: { type: FavKind; poem?: { slug: string } | null; verse?: { uuid: string } | null }[];
        meta: { next_cursor: string | null };
      }>(`/me/favorites${q}`);
      for (const f of page.data) {
        if (f.type === "poem"  && f.poem?.slug)  poems.add(f.poem.slug);
        if (f.type === "verse" && f.verse?.uuid) verses.add(f.verse.uuid);
      }
      if (!page.meta.next_cursor) break;
      cursor = page.meta.next_cursor;
    }
    state = { poems, verses, loaded: true, loading: false };
  } catch {
    state = { ...state, loading: false };
  }
  emit();
}

export const favorites = {
  get: snapshot,
  isFavorited(kind: FavKind, id: string): boolean {
    return kind === "poem" ? state.poems.has(id) : state.verses.has(id);
  },
  async ensureLoaded() { await load(false); },
  async refresh() { await load(true); },
  reset,

  /** Optimistic toggle — flips locally, hits API, rolls back on failure. */
  async toggle(kind: FavKind, id: string): Promise<boolean> {
    if (!session.isSignedIn()) return false;

    const set = kind === "poem" ? state.poems : state.verses;
    const wasFav = set.has(id);
    // optimistic
    if (wasFav) set.delete(id); else set.add(id);
    state = { ...state };
    emit();

    try {
      if (kind === "poem") {
        await apiClient(`/poems/${encodeURIComponent(id)}/favorite`,
          { method: wasFav ? "DELETE" : "POST" });
      } else {
        await apiClient(`/verses/${encodeURIComponent(id)}/favorite`,
          { method: wasFav ? "DELETE" : "POST" });
      }
      return !wasFav;
    } catch (e) {
      // rollback
      if (wasFav) set.add(id); else set.delete(id);
      state = { ...state };
      emit();
      throw e;
    }
  },
};

// Stable server snapshot — useSyncExternalStore compares by identity, so a
// fresh Set/object every call would put React in an infinite-render loop.
const SERVER_SNAPSHOT: State = Object.freeze({
  poems: new Set<string>(),
  verses: new Set<string>(),
  loaded: false,
  loading: false,
}) as State;
const subscribeFavorites = (fn: () => void) => {
  listeners.add(fn);
  return () => { listeners.delete(fn); };
};

/** React hook. Also lazily triggers `load()` on first use once signed-in. */
export function useFavorites(): State {
  const snap = useSyncExternalStore(subscribeFavorites, snapshot, () => SERVER_SNAPSHOT);
  // Kick off the load on first hook consumption post-sign-in.
  if (typeof window !== "undefined" && session.isSignedIn() && !snap.loaded && !snap.loading) {
    void load();
  }
  return snap;
}

// Clear the cache whenever the session goes away.
if (typeof window !== "undefined") {
  session.subscribe(() => { if (!session.isSignedIn()) reset(); });
}
