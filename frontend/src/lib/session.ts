"use client";

/**
 * Client-side session store — a tiny event-emitter pattern (no external deps)
 * that mirrors the Sanctum session in localStorage and lets components
 * subscribe via `useSession()`.
 *
 * Persisted under `sh3ri.session` — reused across page reloads. Falls back
 * silently when localStorage isn't available (SSR, private-mode browsers).
 */

import { useEffect, useSyncExternalStore } from "react";

export interface SessionUser {
  uuid: string;
  name: string;
  email: string;
  locale: string;
  avatar_url?: string | null;
  roles?: string[];
  email_verified_at?: string | null;
  has_google?: boolean;
}

interface SessionState {
  token: string | null;
  user: SessionUser | null;
}

const STORAGE_KEY = "sh3ri.session";
const listeners = new Set<() => void>();

let state: SessionState = load();

function load(): SessionState {
  if (typeof window === "undefined") return { token: null, user: null };
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return { token: null, user: null };
    const parsed = JSON.parse(raw) as SessionState;
    return { token: parsed.token ?? null, user: parsed.user ?? null };
  } catch {
    return { token: null, user: null };
  }
}

function persist(next: SessionState) {
  state = next;
  try {
    if (next.token) localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    else localStorage.removeItem(STORAGE_KEY);
  } catch { /* private mode etc. */ }
  listeners.forEach(fn => fn());
}

export const session = {
  get: () => state,
  isSignedIn: () => !!state.token,
  set(token: string, user: SessionUser) { persist({ token, user }); },
  clear() { persist({ token: null, user: null }); },
  subscribe(fn: () => void) {
    listeners.add(fn);
    return () => listeners.delete(fn);
  },
};

// Stable references — useSyncExternalStore compares snapshots by identity.
// A fresh object every call makes React think the value keeps changing and
// throws "The result of getServerSnapshot should be cached to avoid an
// infinite loop". Define once at module scope.
const SERVER_SNAPSHOT: SessionState = Object.freeze({ token: null, user: null }) as SessionState;
const subscribeSession = (fn: () => void) => session.subscribe(fn);
const getSnapshot = () => state;

/** React hook — re-renders on session changes. */
export function useSession(): SessionState {
  const snap = useSyncExternalStore(subscribeSession, getSnapshot, () => SERVER_SNAPSHOT);
  // Sync from other tabs (storage events fire cross-tab).
  useEffect(() => {
    function onStorage(e: StorageEvent) {
      if (e.key === STORAGE_KEY) {
        state = load();
        listeners.forEach(fn => fn());
      }
    }
    window.addEventListener("storage", onStorage);
    return () => window.removeEventListener("storage", onStorage);
  }, []);
  return snap;
}
