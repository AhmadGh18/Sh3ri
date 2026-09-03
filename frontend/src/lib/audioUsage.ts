"use client";

/**
 * Tiny event-store for the caller's audio-quota state. Multiple audio
 * UIs (PoemReader, VerseReaderModal, a header pill) all read the same
 * snapshot — one refresh updates every mounted consumer.
 *
 * We refetch after every successful play so the number the user sees
 * matches what the server thinks. On 402 (quota exceeded) callers
 * report the failure back here so the store also updates.
 */

import { useSyncExternalStore } from "react";
import type { AudioUsage } from "./api";
import { apiClient } from "./apiClient";
import { session } from "./session";

interface State {
  usage: AudioUsage | null;
  loading: boolean;
  loaded: boolean;
}

const listeners = new Set<() => void>();
let state: State = { usage: null, loading: false, loaded: false };
let inflight: Promise<void> | null = null;

function emit() { listeners.forEach(fn => fn()); }
function snapshot(): State { return state; }

async function refresh(): Promise<void> {
  if (inflight) return inflight;
  state = { ...state, loading: true };
  emit();
  inflight = (async () => {
    try {
      // Endpoint works for BOTH guests (returns guest tier) and users
      // (returns their entitled tier). No `revalidate` — we always want fresh.
      const res = await apiClient<{ data: AudioUsage }>(`/me/audio-usage`);
      state = { usage: res.data, loading: false, loaded: true };
    } catch {
      state = { ...state, loading: false, loaded: true };
    } finally {
      inflight = null;
      emit();
    }
  })();
  return inflight;
}

/** Called by audio UIs after a successful play so the pill count decrements. */
function reportPlayed() {
  // Optimistic decrement so the pill flickers immediately, then a refresh
  // reconciles with the server-side truth.
  if (state.usage && !state.usage.plan.is_unlimited && state.usage.remaining !== null) {
    state = {
      ...state,
      usage: {
        ...state.usage,
        used_today: state.usage.used_today + 1,
        remaining: Math.max(0, state.usage.remaining - 1),
      },
    };
    emit();
  }
  void refresh();
}

/** Called on any 402 so the pill snaps to zero even if the server response was cached. */
function reportBlocked() {
  if (state.usage && !state.usage.plan.is_unlimited) {
    state = { ...state, usage: { ...state.usage, remaining: 0 } };
    emit();
  }
  void refresh();
}

export const audioUsage = {
  get: snapshot,
  refresh,
  reportPlayed,
  reportBlocked,
};

const serverSnapshot: State = Object.freeze({ usage: null, loading: false, loaded: false }) as State;
const subscribe = (fn: () => void) => { listeners.add(fn); return () => { listeners.delete(fn); }; };

/** React hook. Auto-loads on first mount. */
export function useAudioUsage(): State {
  const snap = useSyncExternalStore(subscribe, snapshot, () => serverSnapshot);
  if (typeof window !== "undefined" && !snap.loaded && !snap.loading) {
    void refresh();
  }
  return snap;
}

// Refresh whenever the auth session changes — plan tier may have too.
if (typeof window !== "undefined") {
  session.subscribe(() => { void refresh(); });
}
